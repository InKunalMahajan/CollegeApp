<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\FeeCharge;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FeeController extends Controller
{
    // Keep monetary values in paise; never use floats for financial storage.
    private function paise(string $value): int
    {
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages(['amount' => 'Enter a valid INR amount with up to two decimals.']);
        }
        [$rupees, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $amount = (int) $rupees * 100 + (int) str_pad($fraction, 2, '0');
        if ($amount < 1) throw ValidationException::withMessages(['amount' => 'Amount must be positive.']);
        return $amount;
    }

    public function index(): View
    {
        return view('fees.index', [
            'sections' => ClassSection::with('campus')->orderBy('name')->get(),
            'structures' => FeeStructure::with('classSection')->latest()->get(),
            'charges' => FeeCharge::with(['structure', 'enrollment.student'])
                ->withSum('payments', 'amount_paise')->latest()->paginate(40),
        ]);
    }

    public function storeStructure(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_section_id' => ['required', 'integer', Rule::exists('class_sections','id')],
            'name' => ['required', 'string', 'max:150',
                Rule::unique('fee_structures','name')->where('class_section_id', $request->input('class_section_id'))],
            'amount' => ['required', 'string'],
            'due_date' => ['required', 'date'],
        ]);
        FeeStructure::create([
            'class_section_id' => $data['class_section_id'],
            'name' => $data['name'],
            'amount_paise' => $this->paise($data['amount']),
            'due_date' => $data['due_date'],
        ]);
        return to_route('fees.index')->with('success', 'Fee structure created.');
    }

    public function issue(FeeStructure $structure): RedirectResponse
    {
        $enrollments = ClassEnrollment::where('class_section_id', $structure->class_section_id)->get();
        foreach ($enrollments as $enrollment) {
            FeeCharge::firstOrCreate(
                ['fee_structure_id' => $structure->id, 'class_enrollment_id' => $enrollment->id],
                ['amount_paise' => $structure->amount_paise]
            );
        }
        return to_route('fees.index')->with('success', 'Charges issued to class enrollments without duplicating existing charges.');
    }

    public function recordPayment(Request $request, FeeCharge $charge, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required','string'],
            'paid_on' => ['required','date','before_or_equal:today'],
            'method' => ['required',Rule::in(['cash','upi','bank_transfer','card','other'])],
            'reference' => ['nullable','string','max:100', Rule::unique('fee_payments','reference')],
        ]);
        $amount = $this->paise($data['amount']);
        DB::transaction(function () use ($charge, $data, $amount, $audit) {
            $locked = FeeCharge::whereKey($charge->id)->lockForUpdate()->firstOrFail();
            $paid = (int) FeePayment::where('fee_charge_id', $locked->id)->sum('amount_paise');
            if ($amount > $locked->amount_paise - $paid) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the outstanding balance.']);
            }
            $payment = FeePayment::create([
                'fee_charge_id' => $locked->id, 'amount_paise' => $amount,
                'paid_on' => $data['paid_on'], 'method' => $data['method'],
                'reference' => $data['reference'] ?? null, 'recorded_by' => auth()->id(),
            ]);
            $schoolId = $locked->enrollment->classSection->campus->school_id;
            $audit->record($schoolId, auth()->id(), 'fee.payment.recorded', 'fee_payment', $payment->id, [
                'fee_charge_id' => $locked->id,
                'amount_paise' => $amount,
            ]);
        });
        return to_route('fees.index')->with('success', 'Manual payment recorded.');
    }

    public function receipt(FeePayment $payment): View
    {
        return view('fees.receipt', ['payment' => $payment->load('charge.structure','charge.enrollment.student','recorder')]);
    }
}
