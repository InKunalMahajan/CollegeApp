<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\FeeCharge;
use App\Models\FeePayment;
use App\Models\LibraryBook;
use App\Models\LibraryCopy;
use App\Models\LibraryLoan;
use App\Models\School;
use App\Services\SchoolAuditTrail;
use App\Services\SchoolDataScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolFinanceLibraryController extends Controller
{
    private function enrollmentIds(School $school)
    {
        return app(SchoolDataScope::class)->enrollmentIds($school);
    }

    private function chargeIds(School $school)
    {
        return app(SchoolDataScope::class)->chargeIds($school);
    }

    private function scopedCharge(School $school, int $chargeId): FeeCharge
    {
        return FeeCharge::query()->whereKey($chargeId)
            ->whereIn('id',$this->chargeIds($school))
            ->firstOrFail();
    }

    private function scopedCopy(School $school, int $copyId): LibraryCopy
    {
        return LibraryCopy::query()->whereKey($copyId)
            ->whereHas('book', fn ($q) => $q->where('school_id', $school->id))->firstOrFail();
    }

    private function paise(string $value): int
    {
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages(['amount'=>'Enter a valid INR amount with up to 2 decimals.']);
        }
        [$rupees, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $amount = (int) $rupees * 100 + (int) str_pad($fraction, 2, '0');
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount'=>'Amount must be greater than zero.']);
        }
        return $amount;
    }

    public function fees(School $school): View
    {
        $charges=FeeCharge::with('structure','enrollment.student')
            ->withSum('payments','amount_paise')
            ->whereIn('id',$this->chargeIds($school))
            ->latest()->paginate(30);

        return view('school-finance.fees',compact('school','charges'));
    }

    public function payment(Request $request, School $school, FeeCharge $charge, SchoolAuditTrail $audit): RedirectResponse
    {
        // Authorization checks apply before input validation to avoid leaking external records.
        $charge = $this->scopedCharge($school, $charge->id);
        $data = $request->validate([
            'amount'=>['required','string'],
            'paid_on'=>['required','date','before_or_equal:today'],
            'method'=>['required',Rule::in(['cash','upi','bank_transfer','card','other'])],
            'reference'=>['nullable','string','max:100',Rule::unique('fee_payments','reference')],
        ]);
        $amount = $this->paise($data['amount']);
        DB::transaction(function () use ($school,$charge,$data,$amount,$audit) {
            $locked=FeeCharge::whereKey($charge->id)->lockForUpdate()->firstOrFail();
            // Repeat scope checks inside transaction.
            $this->scopedCharge($school,$locked->id);
            $paid=(int)FeePayment::where('fee_charge_id',$locked->id)->sum('amount_paise');
            if ($amount > $locked->amount_paise-$paid) {
                throw ValidationException::withMessages(['amount'=>'Payment exceeds remaining balance.']);
            }
            $payment=FeePayment::create([
                'fee_charge_id'=>$locked->id,'amount_paise'=>$amount,'paid_on'=>$data['paid_on'],
                'method'=>$data['method'],'reference'=>$data['reference'] ?? null,
                'recorded_by'=>auth()->id(),
            ]);
            $audit->record($school->id,auth()->id(),'fee.payment.recorded','fee_payment',$payment->id,[
                'fee_charge_id'=>$locked->id,'amount_paise'=>$amount,
            ]);
        });
        return to_route('school-finance.fees',$school)->with('success','Payment recorded.');
    }

    public function library(School $school): View
    {
        $books=LibraryBook::with('copies')->where('school_id',$school->id)->orderBy('title')->limit(100)->get();
        $copies=LibraryCopy::with('book')
            ->whereHas('book',fn ($q)=>$q->where('school_id',$school->id))
            ->orderBy('barcode')->get();
        $enrollments=ClassEnrollment::with('student')
            ->whereIn('id',$this->enrollmentIds($school))
            ->limit(200)->get();
        $loans=LibraryLoan::with('copy.book','enrollment.student')
            ->whereHas('copy.book',fn ($q)=>$q->where('school_id',$school->id))
            ->whereHas('enrollment',fn ($q)=>$q->whereIn('id',$this->enrollmentIds($school)))
            ->latest()->paginate(30);
        return view('school-finance.library',compact('school','books','copies','enrollments','loans'));
    }

    public function issue(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data=$request->validate([
            'library_copy_id'=>['required','integer',Rule::exists('library_copies','id')],
            'class_enrollment_id'=>['required','integer',Rule::exists('class_enrollments','id')],
            'due_on'=>['required','date','after:today'],
        ]);
        $copy=$this->scopedCopy($school,(int)$data['library_copy_id']);
        $enrollment=ClassEnrollment::query()->whereKey($data['class_enrollment_id'])
            ->whereIn('id',$this->enrollmentIds($school))->firstOrFail();

        DB::transaction(function () use ($school,$copy,$enrollment,$data,$audit) {
            $locked=LibraryCopy::whereKey($copy->id)->lockForUpdate()->firstOrFail();
            $this->scopedCopy($school,$locked->id);
            abort_unless(ClassEnrollment::whereKey($enrollment->id)
                ->whereIn('id',$this->enrollmentIds($school))->exists(),404);
            if ($locked->status !== 'available' ||
                LibraryLoan::where('library_copy_id',$locked->id)->whereNull('returned_on')->exists()) {
                throw ValidationException::withMessages(['library_copy_id'=>'Copy is unavailable.']);
            }
            $loan=LibraryLoan::create([
                'library_copy_id'=>$locked->id,
                'class_enrollment_id'=>$enrollment->id,
                'issued_on'=>now()->toDateString(),
                'due_on'=>$data['due_on'],'issued_by'=>auth()->id(),
            ]);
            $locked->update(['status'=>'borrowed']);
            $audit->record($school->id,auth()->id(),'library.loan.issued','library_loan',$loan->id,[
                'library_copy_id'=>$locked->id,
            ]);
        });
        return to_route('school-finance.library',$school)->with('success','Book issued.');
    }

    public function returnBook(School $school, LibraryLoan $loan, SchoolAuditTrail $audit): RedirectResponse
    {
        // Both copy and borrower's enrollment must belong to this school.
        $valid=LibraryLoan::whereKey($loan->id)
            ->whereIn('id',app(SchoolDataScope::class)->loanIds($school))
            ->exists();
        abort_unless($valid,404);

        DB::transaction(function () use ($school,$loan,$audit) {
            $copy=LibraryCopy::whereKey($loan->library_copy_id)->lockForUpdate()->firstOrFail();
            $this->scopedCopy($school,$copy->id);
            $current=LibraryLoan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            abort_unless(LibraryLoan::whereKey($current->id)
                ->whereIn('id',app(SchoolDataScope::class)->loanIds($school))->exists(),404);
            if ($current->returned_on) {
                throw ValidationException::withMessages(['loan'=>'This book was already returned.']);
            }
            $current->update(['returned_on'=>now()->toDateString(),'returned_by'=>auth()->id()]);
            $copy->update(['status'=>'available']);
            $audit->record($school->id,auth()->id(),'library.loan.returned','library_loan',$current->id,[
                'library_copy_id'=>$copy->id,
            ]);
        });
        return to_route('school-finance.library',$school)->with('success','Book returned.');
    }
}
