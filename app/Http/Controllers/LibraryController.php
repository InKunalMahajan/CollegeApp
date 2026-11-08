<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\LibraryBook;
use App\Models\LibraryCopy;
use App\Models\LibraryLoan;
use App\Models\School;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LibraryController extends Controller
{
    public function index(): View
    {
        return view('library.index', [
            'schools' => School::orderBy('name')->get(),
            'books' => LibraryBook::with('school', 'copies')->latest()->get(),
            'copies' => LibraryCopy::with('book')->orderBy('barcode')->get(),
            'enrollments' => ClassEnrollment::with('student', 'classSection.campus')->get(),
            'loans' => LibraryLoan::with('copy.book','enrollment.student')->latest()->paginate(40),
        ]);
    }

    public function storeBook(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_id' => ['required','integer',Rule::exists('schools','id')],
            'title' => ['required','string','max:200'],
            'author' => ['nullable','string','max:200'],
            'isbn' => ['nullable','string','max:20'],
        ]);
        LibraryBook::create($data);
        return to_route('library.index')->with('success', 'Book added to catalog.');
    }

    public function storeCopy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'library_book_id' => ['required','integer',Rule::exists('library_books','id')],
            'barcode' => ['required','string','max:80',Rule::unique('library_copies','barcode')],
        ]);
        LibraryCopy::create($data + ['status' => 'available']);
        return to_route('library.index')->with('success', 'Book copy registered.');
    }

    public function issue(Request $request, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'library_copy_id' => ['required','integer',Rule::exists('library_copies','id')],
            'class_enrollment_id' => ['required','integer',Rule::exists('class_enrollments','id')],
            'due_on' => ['required','date','after:today'],
        ]);
        DB::transaction(function () use ($data, $audit) {
            $copy = LibraryCopy::with('book')->whereKey($data['library_copy_id'])->lockForUpdate()->firstOrFail();
            $enrollment = ClassEnrollment::with('classSection.campus')->findOrFail($data['class_enrollment_id']);
            if ($copy->book->school_id !== $enrollment->classSection->campus->school_id) {
                throw ValidationException::withMessages(['class_enrollment_id'=>'Borrower and book must belong to the same school.']);
            }
            if ($copy->status !== 'available' || LibraryLoan::where('library_copy_id', $copy->id)->whereNull('returned_on')->exists()) {
                throw ValidationException::withMessages(['library_copy_id'=>'This copy is already on loan.']);
            }
            $loan = LibraryLoan::create([
                'library_copy_id' => $copy->id,
                'class_enrollment_id' => $enrollment->id,
                'issued_on' => now()->toDateString(),
                'due_on' => $data['due_on'],
                'issued_by' => auth()->id(),
            ]);
            $copy->update(['status' => 'borrowed']);
            $audit->record($copy->book->school_id, auth()->id(), 'library.loan.issued', 'library_loan', $loan->id, [
                'library_copy_id' => $copy->id,
            ]);
        });
        return to_route('library.index')->with('success', 'Book issued.');
    }

    public function returnBook(LibraryLoan $loan, SchoolAuditTrail $audit): RedirectResponse
    {
        DB::transaction(function () use ($loan, $audit) {
            $copy = LibraryCopy::whereKey($loan->library_copy_id)->lockForUpdate()->firstOrFail();
            $currentLoan = LibraryLoan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            if ($currentLoan->returned_on !== null) {
                throw ValidationException::withMessages(['loan' => 'Book was already returned.']);
            }
            $currentLoan->update(['returned_on' => now()->toDateString(), 'returned_by' => auth()->id()]);
            $copy->update(['status' => 'available']);
            $audit->record($copy->book->school_id, auth()->id(), 'library.loan.returned', 'library_loan', $currentLoan->id, [
                'library_copy_id' => $copy->id,
            ]);
        });
        return to_route('library.index')->with('success', 'Book returned.');
    }
}
