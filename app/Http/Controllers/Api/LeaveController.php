<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\LeaveEntitlementService;
use App\Services\LeaveWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Congés & Permissions
 */
class LeaveController extends Controller
{
    /**
     * Lister les types de congés disponibles
     *
     * @response 200 {"leave_types": [{"id": 1, "name": "Congé Annuel", "code": "CA", "requires_document": false}]}
     */
    public function types()
    {
        $types = LeaveType::where('is_active', true)->get(['id', 'name', 'code', 'description', 'requires_document']);

        return response()->json(['leave_types' => $types]);
    }

    /**
     * Consulter mon solde pour un type de congé
     *
     * @queryParam leave_type_id integer required ID du type de congé. Example: 1
     *
     * @response 200 {"eligible": true, "service_year": 3, "entitlement": 18, "used": 5, "available": 13}
     * @response 404 scenario="Aucun employé lié" {"message": "Aucune fiche employé n'est liée à ce compte."}
     */
    public function balance(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => "Aucune fiche employé n'est liée à ce compte."], 404);
        }

        $validator = Validator::make($request->all(), [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        $leaveType = LeaveType::find($request->leave_type_id);
        $service = app(LeaveEntitlementService::class);

        if (!$service->isEligibleForLeave($employee)) {
            return response()->json([
                'eligible' => false,
                'next_eligibility_date' => $service->getNextEligibilityDate($employee)?->format('Y-m-d'),
            ]);
        }

        $balance = $service->getAvailableDays($employee, $leaveType);

        return response()->json($balance ?? ['eligible' => true, 'message' => 'Pas de solde applicable pour ce type.']);
    }

    /**
     * Lister mes demandes de congé
     */
    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => "Aucune fiche employé n'est liée à ce compte."], 404);
        }

        $leaves = Leave::where('employee_id', $employee->id)
            ->with(['leaveType', 'currentApprovalStep'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'leaves' => $leaves->map(fn($leave) => $this->formatLeaveSummary($leave)),
        ]);
    }

    /**
     * Détail d'une demande de congé, avec l'avancement dans le circuit de validation
     */
    public function show(Request $request, int $id)
    {
        $leave = $this->findOwnLeave($request, $id);

        if (!$leave) {
            return response()->json(['message' => 'Demande introuvable.'], 404);
        }

        $leave->load(['leaveType', 'approvals.step', 'approvals.resolvedUser', 'replacement.replacementEmployee']);

        return response()->json(['leave' => $this->formatLeaveDetail($leave)]);
    }

    /**
     * Soumettre une nouvelle demande de congé
     *
     * Le circuit de validation démarre automatiquement à la soumission.
     * Pour fractionner en 2 prises, renseignez is_split=true avec start_date_2/end_date_2.
     *
     * @bodyParam leave_type_id integer required ID du type de congé.
     * @bodyParam start_date string required Date de début (YYYY-MM-DD).
     * @bodyParam end_date string required Date de fin (YYYY-MM-DD).
     * @bodyParam is_split boolean Fractionner en 2 prises.
     * @bodyParam start_date_2 string Date de début de la 2ème prise (si is_split).
     * @bodyParam end_date_2 string Date de fin de la 2ème prise (si is_split).
     * @bodyParam reason string required Motif de la demande.
     * @bodyParam destination string Destination (pour Permission d'Absence).
     * @bodyParam address_during_leave string Adresse pendant le congé.
     * @bodyParam children_under_6_at_request integer Enfants < 6 ans (femme salariée).
     * @bodyParam document file Document justificatif (décision signée, planning de service, ou autre).
     *
     * @response 201 scenario="Créée" {"message": "Demande soumise, en attente de validation.", "leave": {"...": "..."}}
     * @response 422 scenario="Validation échouée" {"message": "Données invalides.", "errors": {"end_date": ["La date de fin doit être après la date de début."]}}
     */
    public function store(Request $request)
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json(['message' => "Aucune fiche employé n'est liée à ce compte."], 404);
        }

        $validator = Validator::make($request->all(), [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_split' => ['sometimes', 'boolean'],
            'start_date_2' => ['required_if:is_split,true', 'nullable', 'date', 'after_or_equal:end_date'],
            'end_date_2' => ['required_if:is_split,true', 'nullable', 'date', 'after_or_equal:start_date_2'],
            'reason' => ['required', 'string'],
            'destination' => ['nullable', 'string', 'max:255'],
            'address_during_leave' => ['nullable', 'string', 'max:255'],
            'children_under_6_at_request' => ['nullable', 'integer', 'min:0'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['employee_id'] = $employee->id;
        unset($data['document']);

        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('leave-documents', 'public');
        }

        $leave = Leave::create($data);

        app(LeaveWorkflowService::class)->submit($leave);

        return response()->json([
            'message' => 'Demande soumise, en attente de validation.',
            'leave' => $this->formatLeaveDetail($leave->fresh(['leaveType', 'approvals.step'])),
        ], 201);
    }

    private function findOwnLeave(Request $request, int $id): ?Leave
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return null;
        }

        return Leave::where('employee_id', $employee->id)->where('id', $id)->first();
    }

    private function formatLeaveSummary(Leave $leave): array
    {
        return [
            'id' => $leave->id,
            'leave_type' => $leave->leaveType?->name,
            'start_date' => $leave->start_date?->format('Y-m-d'),
            'end_date' => $leave->end_date?->format('Y-m-d'),
            'total_days' => $leave->total_days,
            'is_split' => (bool) $leave->is_split,
            'status' => $leave->status,
            'current_step' => $leave->currentApprovalStep?->name,
            'has_returned' => (bool) $leave->has_returned,
        ];
    }

    private function formatLeaveDetail(Leave $leave): array
    {
        return array_merge($this->formatLeaveSummary($leave), [
            'start_date_2' => $leave->start_date_2?->format('Y-m-d'),
            'end_date_2' => $leave->end_date_2?->format('Y-m-d'),
            'reason' => $leave->reason,
            'destination' => $leave->destination,
            'address_during_leave' => $leave->address_during_leave,
            'document_url' => $leave->document_path ? Storage::disk('public')->url($leave->document_path) : null,
            'rejection_reason' => $leave->rejection_reason,
            'replacement' => $leave->replacement?->replacementEmployee?->full_name,
            'steps' => $leave->approvals->sortBy('step_order')->values()->map(fn($approval) => [
                'name' => $approval->step->name,
                'order' => $approval->step_order,
                'status' => $approval->status,
                'resolved_by' => $approval->resolvedUser?->name,
                'comments' => $approval->comments,
                'acted_at' => $approval->acted_at?->format('Y-m-d H:i'),
            ]),
        ]);
    }
}
