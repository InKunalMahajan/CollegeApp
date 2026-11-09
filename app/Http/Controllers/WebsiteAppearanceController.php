<?php

namespace App\Http\Controllers;

use App\Models\SaasPlatformAppearance;
use App\Services\AppearanceSettings;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteAppearanceController extends Controller
{
    public function index(): View
    {
        $appearance = SaasPlatformAppearance::query()->where('setting_key', 'default')->first();

        return view('website-owner.appearance', compact('appearance'));
    }

    public function update(Request $request, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate(AppearanceSettings::rules());

        DB::transaction(function () use ($request, $data, $audit) {
            // Lock the singleton row if it exists. The settings page is Owner-only.
            $appearance = SaasPlatformAppearance::query()->where('setting_key', 'default')->lockForUpdate()->first();
            if ($appearance) {
                $appearance->update($data);
            } else {
                $appearance = SaasPlatformAppearance::create($data);
            }

            $audit->record(null, $request->user()->id, 'website.appearance.updated',
                'saas_platform_appearance', $appearance->id, [
                    'font_family' => $data['font_family'],
                    'font_size' => $data['font_size'],
                    'density' => $data['density'],
                    'primary_color' => $data['primary_color'],
                ]);
        });

        return to_route('website-owner.appearance.index')
            ->with('success', 'Website appearance saved. Refresh another open page to see the changes.');
    }
}
