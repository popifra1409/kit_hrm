<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Messagerie
 */
class ConversationController extends Controller
{
    /**
     * Lister mes conversations (1-à-1 et groupes), triées par activité récente
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $user->id)->whereNull('left_at'))
            ->with(['users:id,name', 'latestMessage.sender:id,name'])
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json([
            'conversations' => $conversations->map(fn($c) => $this->formatConversation($c, $user->id)),
        ]);
    }

    /**
     * Démarrer (ou récupérer) une conversation directe avec un autre utilisateur
     *
     * @bodyParam user_id integer required ID de l'utilisateur avec qui discuter.
     *
     * @response 200 {"conversation": {"...": "..."}}
     */
    public function startDirect(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        if ((int) $request->user_id === $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas démarrer une conversation avec vous-même.'], 422);
        }

        $existing = Conversation::findDirectBetween($user->id, (int) $request->user_id);

        if ($existing) {
            return response()->json(['conversation' => $this->formatConversation($existing, $user->id)]);
        }

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
            'created_by' => $user->id,
            'last_message_at' => now(),
        ]);

        $conversation->participants()->createMany([
            ['user_id' => $user->id, 'role' => 'member', 'joined_at' => now()],
            ['user_id' => $request->user_id, 'role' => 'member', 'joined_at' => now()],
        ]);

        return response()->json([
            'conversation' => $this->formatConversation($conversation->fresh('users'), $user->id),
        ], 201);
    }

    /**
     * Créer un groupe manuellement
     *
     * @bodyParam name string required Nom du groupe.
     * @bodyParam user_ids array required Liste des IDs des membres à ajouter (en plus de vous-même).
     *
     * @response 201 {"conversation": {"...": "..."}}
     */
    public function createGroup(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'name' => $request->name,
            'created_by' => $user->id,
            'last_message_at' => now(),
        ]);

        $conversation->participants()->create([
            'user_id' => $user->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $memberIds = collect($request->user_ids)->unique()->reject(fn($id) => (int) $id === $user->id);

        foreach ($memberIds as $memberId) {
            $conversation->participants()->create([
                'user_id' => $memberId,
                'role' => 'member',
                'joined_at' => now(),
            ]);
        }

        return response()->json([
            'conversation' => $this->formatConversation($conversation->fresh('users'), $user->id),
        ], 201);
    }

    /**
     * Ajouter des membres à un groupe (admin du groupe uniquement)
     *
     * @bodyParam user_ids array required IDs des membres à ajouter.
     */
    public function addMembers(Request $request, int $id)
    {
        $user = $request->user();
        $conversation = $this->findOwnGroup($user->id, $id);

        if (!$conversation) {
            return response()->json(['message' => 'Groupe introuvable.'], 404);
        }

        $participant = $conversation->participants()->where('user_id', $user->id)->first();

        if (!$participant->isAdmin()) {
            return response()->json(['message' => 'Seul un administrateur du groupe peut ajouter des membres.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        foreach ($request->user_ids as $memberId) {
            $conversation->participants()->firstOrCreate(
                ['user_id' => $memberId],
                ['role' => 'member', 'joined_at' => now()]
            );
        }

        return response()->json([
            'conversation' => $this->formatConversation($conversation->fresh('users'), $user->id),
        ]);
    }

    /**
     * Quitter un groupe
     */
    public function leaveGroup(Request $request, int $id)
    {
        $user = $request->user();
        $conversation = $this->findOwnGroup($user->id, $id);

        if (!$conversation) {
            return response()->json(['message' => 'Groupe introuvable.'], 404);
        }

        $conversation->participants()->where('user_id', $user->id)->update(['left_at' => now()]);

        return response()->json(['message' => 'Vous avez quitté le groupe.']);
    }

    private function findOwnGroup(int $userId, int $conversationId): ?Conversation
    {
        return Conversation::where('id', $conversationId)
            ->where('type', Conversation::TYPE_GROUP)
            ->whereHas('participants', fn($q) => $q->where('user_id', $userId)->whereNull('left_at'))
            ->first();
    }

    private function formatConversation(Conversation $conversation, int $currentUserId): array
    {
        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'name' => $conversation->isGroup()
                ? $conversation->name
                : $conversation->users->firstWhere('id', '!=', $currentUserId)?->name,
            'participants' => $conversation->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
            'last_message' => $conversation->latestMessage ? [
                'body' => $conversation->latestMessage->body,
                'sender_name' => $conversation->latestMessage->sender?->name,
                'created_at' => $conversation->latestMessage->created_at?->toIso8601String(),
            ] : null,
            'unread_count' => $conversation->unreadCountFor($currentUserId),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        ];
    }
}
