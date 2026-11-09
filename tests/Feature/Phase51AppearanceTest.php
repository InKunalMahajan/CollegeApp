<?php

namespace Tests\Feature;

use App\Models\SaasBranding;
use App\Models\SaasPlatformAppearance;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Models\WebsiteOwner;
use App\Services\AppearanceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase51AppearanceTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $email): User
    {
        return User::create([
            'name' => 'Design Tester',
            'email' => $email,
            'role' => 'staff',
            'password' => 'sufficiently-long-password',
        ]);
    }

    public function test_only_explicit_website_owner_can_change_platform_design_settings(): void
    {
        $owner = $this->account('appearance-owner@example.test');
        $staff = $this->account('appearance-staff@example.test');
        WebsiteOwner::create(['user_id' => $owner->id]);

        $payload = [
            'primary_color' => '#3B2F8F',
            'font_family' => 'verdana',
            'font_size' => 18,
            'density' => 'compact',
        ];

        $this->actingAs($staff)->get(route('website-owner.appearance.index'))->assertForbidden();
        $this->put(route('website-owner.appearance.update'), $payload)->assertForbidden();

        $this->actingAs($owner)->get(route('website-owner.appearance.index'))
            ->assertOk()->assertSee('Appearance & Typography');
        $this->put(route('website-owner.appearance.update'), $payload)
            ->assertRedirect(route('website-owner.appearance.index'));
        $this->assertDatabaseHas('saas_platform_appearances', $payload);
        $this->get(route('website-owner.dashboard'))
            ->assertOk()->assertSee('font-size: 18px', false)
            ->assertSee('--app-primary: #3B2F8F', false);
    }

    public function test_color_contrast_and_font_allowlist_reject_unsafe_values(): void
    {
        $owner = $this->account('appearance-owner@example.test');
        WebsiteOwner::create(['user_id' => $owner->id]);

        $this->actingAs($owner)->put(route('website-owner.appearance.update'), [
            'primary_color' => '#FAFAFA', 'font_family' => 'system',
            'font_size' => 16, 'density' => 'comfortable',
        ])->assertSessionHasErrors('primary_color');

        $this->put(route('website-owner.appearance.update'), [
            'primary_color' => '#3B2F8F',
            'font_family' => 'url(https://example.test/font.css)',
            'font_size' => 31,
            'density' => 'unrestricted',
        ])->assertSessionHasErrors(['font_family', 'font_size', 'density']);

        $this->assertDatabaseCount('saas_platform_appearances', 0);
        $this->assertGreaterThanOrEqual(4.5, AppearanceSettings::whiteTextContrast('#3B2F8F'));
    }

    public function test_organization_design_is_isolated_from_other_schools(): void
    {
        $teach = School::create(['name'=>'TEACH', 'code'=>'TEACH']);
        $other = School::create(['name'=>'OTHER', 'code'=>'OTHER']);
        $admin = $this->account('appearance-admin@example.test');

        SchoolMembership::create([
            'school_id'=>$teach->id,'user_id'=>$admin->id,'role'=>'school_admin',
        ]);

        $payload = [
            'display_name'=>'TEACH Learning',
            'primary_color'=>'#0F766E',
            'font_family'=>'georgia',
            'font_size'=>20,
            'density'=>'comfortable',
        ];

        $this->actingAs($admin)
            ->put(route('saas.branding.update',$teach),$payload)->assertRedirect();

        $this->assertDatabaseHas('saas_brandings', [
            'school_id'=>$teach->id,
            'font_family'=>'georgia',
            'font_size'=>20,
            'primary_color'=>'#0F766E',
        ]);

        $this->get(route('school-dashboard.index',$teach))
            ->assertOk()->assertSee('--app-primary: #0F766E', false)
            ->assertSee('font-size: 20px', false);

        $this->put(route('saas.branding.update',$other),$payload)->assertForbidden();
        $this->assertDatabaseMissing('saas_brandings',['school_id'=>$other->id]);
        $this->get(route('website-owner.appearance.index'))->assertForbidden();
    }

    public function test_legacy_branding_submission_keeps_compatible_font_defaults(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $admin = $this->account('appearance-admin@example.test');
        SchoolMembership::create([
            'school_id'=>$school->id,'user_id'=>$admin->id,'role'=>'school_admin',
        ]);

        $this->actingAs($admin)->put(route('saas.branding.update',$school),[
            'display_name'=>'TEACH','primary_color'=>'#3B2F8F',
        ])->assertRedirect();

        $branding = SaasBranding::where('school_id',$school->id)->firstOrFail();
        $this->assertSame('system',$branding->font_family);
        $this->assertSame(16,(int) $branding->font_size);
    }
}
