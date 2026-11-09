<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase36CampusEnrollmentsTest extends TestCase
{
    use RefreshDatabase;

    private function setupSchool(string $code): array
    {
        $school = School::create(['name' => $code, 'code' => $code]);
        $campus = Campus::create(['school_id' => $school->id, 'name' => $code.' Mumbai', 'code' => 'MUMBAI']);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026-27',
            'starts_on' => '2026-06-01', 'ends_on' => '2027-05-31',
        ]);
        $section = ClassSection::create([
            'campus_id' => $campus->id, 'academic_year_id' => $year->id,
            'name' => 'FYJC A', 'code' => 'FYJCA',
        ]);
        return [$school, $campus, $section];
    }

    private function student(School $school, Campus $campus, string $number, string $status = 'active'): Student
    {
        return Student::create([
            'school_id' => $school->id, 'campus_id' => $campus->id,
            'admission_number' => $number, 'first_name' => 'Test', 'last_name' => $number,
            'admission_date' => '2026-06-01', 'status' => $status,
        ]);
    }

    private function campusAdmin(Campus $campus): User
    {
        $user = User::create([
            'name' => 'Campus Admin', 'email' => 'campus36@example.test',
            'password' => 'phase36-test-password', 'role' => 'staff',
        ]);
        CampusMembership::create([
            'campus_id' => $campus->id, 'user_id' => $user->id, 'role' => 'campus_admin',
        ]);
        return $user;
    }

    public function test_campus_admin_enrolls_own_active_student_exactly_once(): void
    {
        [$school, $campus, $section] = $this->setupSchool('TEACH');
        $student = $this->student($school, $campus, 'ST36');
        $admin = $this->campusAdmin($campus);
        $payload = ['student_id' => $student->id, 'class_section_id' => $section->id];

        $this->actingAs($admin)->get(route('campus.enrollments.index', [$school, $campus]))
            ->assertOk()->assertSee('Campus Enrollments');
        $this->post(route('campus.enrollments.store', [$school, $campus]), $payload)->assertRedirect();
        $this->post(route('campus.enrollments.store', [$school, $campus]), $payload)->assertRedirect();

        $this->assertSame(1, ClassEnrollment::where($payload)->count());
        $this->assertDatabaseHas('school_audit_events', [
            'school_id' => $school->id, 'action' => 'campus.academics.student.enrolled',
        ]);
        $this->assertSame(1, \App\Models\SchoolAuditEvent::where('action', 'campus.academics.student.enrolled')->count());
    }

    public function test_campus_admin_cannot_enroll_foreign_or_unassigned_students_or_sections(): void
    {
        [$school, $campus, $section] = $this->setupSchool('TEACH');
        [$other, $foreignCampus, $foreignSection] = $this->setupSchool('OTHER');
        $admin = $this->campusAdmin($campus);
        $valid = $this->student($school, $campus, 'VALID36');
        $foreign = $this->student($other, $foreignCampus, 'FOREIGN36');
        $inactive = $this->student($school, $campus, 'INACTIVE36', 'inactive');
        $unassigned = $this->student($school, $campus, 'UNASSIGNED36');
        $unassigned->update(['campus_id' => null]);

        foreach ([
            [$foreign->id, $section->id],
            [$inactive->id, $section->id],
            [$unassigned->id, $section->id],
            [$valid->id, $foreignSection->id],
        ] as [$studentId, $sectionId]) {
            $this->actingAs($admin)->post(route('campus.enrollments.store', [$school, $campus]), [
                'student_id' => $studentId, 'class_section_id' => $sectionId,
                'school_id' => $other->id, 'campus_id' => $foreignCampus->id,
            ])->assertNotFound();
        }
        $this->assertDatabaseCount('class_enrollments', 0);
    }

    public function test_other_campus_and_unrelated_admin_cannot_access_enrollments(): void
    {
        [$school, $campus] = $this->setupSchool('TEACH');
        $pune = Campus::create(['school_id' => $school->id, 'name' => 'TEACH Pune', 'code' => 'PUNE']);
        [$other, $foreignCampus] = $this->setupSchool('OTHER');
        $admin = $this->campusAdmin($campus);
        $this->actingAs($admin)
            ->get(route('campus.enrollments.index', [$school, $pune]))->assertForbidden();
        $this->get(route('campus.enrollments.index', [$other, $foreignCampus]))->assertForbidden();
        $this->get(route('campus.enrollments.index', [$school, $foreignCampus]))->assertNotFound();

        $headOffice = User::create([
            'name' => 'Head Office', 'email' => 'head36@example.test',
            'password' => 'phase36-test-password', 'role' => 'staff',
        ]);
        SchoolMembership::create([
            'school_id' => $school->id, 'user_id' => $headOffice->id, 'role' => 'school_admin',
        ]);
        $this->actingAs($headOffice)
            ->get(route('campus.enrollments.index', [$school, $pune]))->assertOk();
    }
}
