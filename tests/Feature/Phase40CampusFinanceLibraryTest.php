<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase40CampusFinanceLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_campus_financial_ledger_does_not_grant_school_finance_or_library_mutations(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $mumbai = Campus::create(['school_id'=>$school->id,'name'=>'Mumbai','code'=>'MUM']);
        $pune = Campus::create(['school_id'=>$school->id,'name'=>'Pune','code'=>'PUN']);
        $admin = User::create(['name'=>'Mumbai Admin','email'=>'m40@example.test','password'=>'password123','role'=>'staff']);
        CampusMembership::create(['campus_id'=>$mumbai->id,'user_id'=>$admin->id,'role'=>'campus_admin']);

        $this->actingAs($admin)->get(route('campus.finance-library.index',[$school,$mumbai]))
            ->assertOk()->assertSee('Viewing only');
        $this->get(route('campus.finance-library.index',[$school,$pune]))->assertForbidden();
        $this->get(route('school-finance.fees',$school))->assertForbidden();
        $this->get(route('school-finance.library',$school))->assertForbidden();
    }
}
