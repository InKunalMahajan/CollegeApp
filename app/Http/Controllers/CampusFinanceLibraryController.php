<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\FeeCharge;
use App\Models\FeePayment;
use App\Models\LibraryLoan;
use App\Models\School;
use Illuminate\Contracts\View\View;

class CampusFinanceLibraryController extends Controller
{
    /**
     * Verified students and class sections must both belong to the campus.
     * Library inventory is school-wide, so the ledger is intentionally
     * read-only and excludes inventory administration/issuance.
     */
    private function enrollmentScope(School $school, Campus $campus): \Closure
    {
        return fn ($enrollment) => $enrollment
            ->whereHas('student', fn ($student) => $student
                ->where('school_id', $school->id)->where('campus_id', $campus->id))
            ->whereHas('classSection', fn ($section) => $section
                ->where('campus_id', $campus->id)
                ->whereHas('academicYear', fn ($year) => $year->where('school_id', $school->id)));
    }

    public function index(School $school, Campus $campus): View
    {
        $scope = $this->enrollmentScope($school, $campus);
        $charges = FeeCharge::with(['enrollment.student','structure'])
            ->whereHas('enrollment', $scope)
            ->whereHas('structure.classSection', fn ($q) => $q
                ->where('campus_id', $campus->id)
                ->whereHas('academicYear', fn ($y) => $y->where('school_id', $school->id)))
            ->latest()->paginate(20, ['*'], 'charges_page');
        $payments = FeePayment::with(['charge.enrollment.student'])
            ->whereHas('charge', fn ($charge) => $charge
                ->whereHas('enrollment', $scope)
                ->whereHas('structure.classSection', fn ($section) => $section
                    ->where('campus_id', $campus->id)
                    ->whereHas('academicYear', fn ($year) => $year->where('school_id', $school->id))))
            ->latest()->paginate(20, ['*'], 'payments_page');
        $loans = LibraryLoan::with(['enrollment.student','copy.book'])
            ->whereHas('enrollment', $scope)
            ->whereHas('copy.book', fn ($book) => $book->where('school_id', $school->id))
            ->latest()->paginate(20, ['*'], 'loans_page');

        return view('school-campuses.finance-library', compact(
            'school', 'campus', 'charges', 'payments', 'loans'
        ));
    }
}
