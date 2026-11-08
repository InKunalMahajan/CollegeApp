<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6OperationsTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $role.'-phase6@example.test',
            'password' => 'strong-password-123',
            'role' => $role,
        ]);
    }

    private function setupSchool(): array
    {
        $school = School::create(['name' => 'Example School', 'code' => 'SCH']);
        $campus = Campus::create(['school_id' => $school->id, 'name' => 'Main', 'code' => 'MAIN']);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026-27',
            'starts_on' => '2026-06-01', 'ends_on' => '2027-05-31']);
        $section = ClassSection::create(['campus_id' => $campus->id,
            'academic_year_id' => $year->id, 'name' => 'FYJC A', 'code' => 'A']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $teacherUser = $this->account('teacher');
        $teacher = TeacherProfile::create([
            'user_id' => $teacherUser->id, 'school_id' => $school->id,
            'campus_id' => $campus->id, 'employee_code' => 'T100',
        ]);
        TeacherSubjectAssignment::create([
            'teacher_profile_id' => $teacher->id,
            'class_section_id' => $section->id, 'subject_id' => $subject->id,
        ]);
        return [$school, $section, $subject, $teacher, $teacherUser];
    }

    public function test_guest_and_teacher_cannot_manage_attendance_or_timetable(): void
    {
        $this->get('/attendance')->assertRedirect('/login');
        $this->get('/timetables')->assertRedirect('/login');
        $teacher = $this->account('teacher');
        $this->actingAs($teacher)->get('/attendance')->assertForbidden();
        $this->get('/timetables')->assertForbidden();
        $this->post('/attendance/staff', [
            'teacher_profile_id' => 1, 'attendance_date' => '2026-10-01',
            'status' => 'present',
        ])->assertForbidden();
        $this->get('/my-timetable')->assertOk();
    }

    public function test_admin_enrolls_student_and_attendance_is_upserted(): void
    {
        [, $section, , $teacher] = $this->setupSchool();
        $student = Student::create([
            'admission_number' => 'A100', 'first_name' => 'Example', 'last_name' => 'Student',
            'admission_date' => '2026-06-01', 'status' => 'active',
        ]);
        $this->actingAs($this->account('admin'));
        $this->post('/attendance/enroll', [
            'student_id' => $student->id, 'class_section_id' => $section->id,
        ])->assertRedirect(route('attendance.index'));
        $enrollmentId = \App\Models\ClassEnrollment::firstOrFail()->id;
        foreach (['present', 'absent'] as $status) {
            $this->post('/attendance/student', [
                'class_enrollment_id' => $enrollmentId,
                'attendance_date' => '2026-10-01',
                'status' => $status,
            ])->assertRedirect(route('attendance.index'));
        }
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseHas('student_attendances', ['status' => 'absent']);
        $this->post('/attendance/staff', [
            'teacher_profile_id' => $teacher->id,
            'attendance_date' => '2026-10-01', 'status' => 'present',
        ])->assertRedirect(route('attendance.index'));
        $this->assertDatabaseCount('staff_attendances', 1);
    }

    public function test_timetable_rejects_overlap_and_teacher_sees_only_own_entries(): void
    {
        [, $section, $subject, $teacher, $teacherUser] = $this->setupSchool();
        $admin = $this->account('admin');
        $this->actingAs($admin);
        $input = [
            'class_section_id' => $section->id, 'teacher_profile_id' => $teacher->id,
            'subject_id' => $subject->id, 'day_of_week' => 1,
            'starts_at' => '09:00', 'ends_at' => '10:00', 'room' => '101',
        ];
        $this->post('/timetables', $input)->assertRedirect(route('timetables.index'));
        $this->assertDatabaseCount('timetable_entries', 1);
        $this->post('/timetables', array_merge($input, [
            'starts_at' => '09:30', 'ends_at' => '10:30',
        ]))->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('timetable_entries', 1);
        $this->actingAs($teacherUser)->get('/my-timetable')
            ->assertOk()->assertSee('Mathematics')->assertSee('09:00');
    }

    public function test_timetable_requires_matching_teacher_subject_assignment(): void
    {
        [$school, $section, , $teacher] = $this->setupSchool();
        $unassigned = Subject::create(['school_id' => $school->id, 'name' => 'Science', 'code' => 'SCI']);
        $this->actingAs($this->account('admin'))
            ->post('/timetables', [
                'class_section_id' => $section->id,
                'teacher_profile_id' => $teacher->id,
                'subject_id' => $unassigned->id,
                'day_of_week' => 2, 'starts_at' => '11:00', 'ends_at' => '12:00',
            ])->assertSessionHasErrors('teacher_profile_id');
        $this->assertDatabaseCount('timetable_entries', 0);
    }
}
