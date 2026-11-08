<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademicSetupController extends Controller
{
    private const MODULES = [
        'schools' => ['model' => School::class, 'title' => 'Schools', 'fields' => ['name', 'code', 'email']],
        'campuses' => ['model' => Campus::class, 'title' => 'Campuses', 'fields' => ['school_id', 'name', 'code', 'city']],
        'academic-years' => ['model' => AcademicYear::class, 'title' => 'Academic years', 'fields' => ['school_id', 'name', 'starts_on', 'ends_on']],
        'subjects' => ['model' => Subject::class, 'title' => 'Subjects', 'fields' => ['school_id', 'name', 'code']],
        'class-sections' => ['model' => ClassSection::class, 'title' => 'Class sections', 'fields' => ['campus_id', 'academic_year_id', 'name', 'code']],
    ];

    private function definition(string $module): array
    {
        abort_unless(isset(self::MODULES[$module]), 404);
        return self::MODULES[$module];
    }

    public function index(string $module): View
    {
        $definition = $this->definition($module);
        $records = $definition['model']::query()->latest()->paginate(15);
        return view('setup.index', compact('module', 'definition', 'records'));
    }

    public function create(string $module): View
    {
        $definition = $this->definition($module);
        return view('setup.form', [
            'module' => $module,
            'definition' => $definition,
            'record' => null,
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $definition = $this->definition($module);
        $data = $this->validated($request, $module);
        $definition['model']::create($data);
        return to_route('setup.index', $module)->with('success', 'Record created.');
    }

    public function edit(string $module, int $record): View
    {
        $definition = $this->definition($module);
        $item = $definition['model']::findOrFail($record);
        return view('setup.form', [
            'module' => $module,
            'definition' => $definition,
            'record' => $item,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, string $module, int $record): RedirectResponse
    {
        $definition = $this->definition($module);
        $item = $definition['model']::findOrFail($record);
        $item->update($this->validated($request, $module, $item->id));
        return to_route('setup.index', $module)->with('success', 'Record updated.');
    }

    public function destroy(string $module, int $record): RedirectResponse
    {
        $definition = $this->definition($module);
        $item = $definition['model']::findOrFail($record);
        try {
            $item->delete();
        } catch (QueryException $e) {
            // FK constraints may prevent deletion while a record is referenced.
            return to_route('setup.index', $module)->withErrors([
                'delete' => 'This record is being used and cannot be deleted.',
            ]);
        }
        return to_route('setup.index', $module)->with('success', 'Record deleted.');
    }

    private function options(): array
    {
        return [
            'school_id' => School::orderBy('name')->pluck('name', 'id')->all(),
            'campus_id' => Campus::orderBy('name')->get()->mapWithKeys(fn ($campus) => [
                $campus->id => $campus->school_id.' · '.$campus->name,
            ])->all(),
            'academic_year_id' => AcademicYear::orderByDesc('starts_on')->get()->mapWithKeys(fn ($year) => [
                $year->id => $year->school_id.' · '.$year->name,
            ])->all(),
        ];
    }

    private function validated(Request $request, string $module, ?int $ignoreId = null): array
    {
        $rules = match ($module) {
            'schools' => [
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:30', Rule::unique('schools', 'code')->ignore($ignoreId)],
                'email' => ['nullable', 'email', 'max:255'],
            ],
            'campuses' => [
                'school_id' => ['required', 'exists:schools,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:30', Rule::unique('campuses', 'code')->where('school_id', $request->input('school_id'))->ignore($ignoreId)],
                'city' => ['nullable', 'string', 'max:255'],
            ],
            'academic-years' => [
                'school_id' => ['required', 'exists:schools,id'],
                'name' => ['required', 'string', 'max:30', Rule::unique('academic_years', 'name')->where('school_id', $request->input('school_id'))->ignore($ignoreId)],
                'starts_on' => ['required', 'date'],
                'ends_on' => ['required', 'date', 'after:starts_on'],
            ],
            'subjects' => [
                'school_id' => ['required', 'exists:schools,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:30', Rule::unique('subjects', 'code')->where('school_id', $request->input('school_id'))->ignore($ignoreId)],
            ],
            'class-sections' => [
                'campus_id' => ['required', 'exists:campuses,id'],
                'academic_year_id' => ['required', 'exists:academic_years,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:30',
                    Rule::unique('class_sections', 'code')
                        ->where('campus_id', $request->input('campus_id'))
                        ->where('academic_year_id', $request->input('academic_year_id'))
                        ->ignore($ignoreId),
                ],
            ],
        };

        $data = $request->validate($rules);
        if ($module === 'class-sections') {
            $campusSchool = Campus::whereKey($data['campus_id'])->value('school_id');
            $yearSchool = AcademicYear::whereKey($data['academic_year_id'])->value('school_id');
            if ($campusSchool !== $yearSchool) {
                throw ValidationException::withMessages([
                    'academic_year_id' => 'The academic year and campus must belong to the same school.',
                ]);
            }
        }

        return $data;
    }
}
