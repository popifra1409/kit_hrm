<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayrollPDFController extends Controller
{
    public function download(Payroll $payroll)
    {
        $data = [
            'payroll' => $payroll,
            'employee' => $payroll->employee,
            'lines' => $payroll->lines()->orderBy('display_order')->get(),
        ];

        $pdf = Pdf::loadView('pdfs.payroll', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'Bulletin_Paie_' . $payroll->employee->matricule . '_' .
            $payroll->month_name . '_' . $payroll->year . '.pdf';

        return $pdf->download($filename);
    }

    public function view(Payroll $payroll)
    {
        $data = [
            'payroll' => $payroll,
            'employee' => $payroll->employee,
            'lines' => $payroll->lines()->orderBy('display_order')->get(),
        ];

        return view('pdfs.payroll', $data);
    }
}
