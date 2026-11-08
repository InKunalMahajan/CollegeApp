<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ModuleCatalogController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.index', [
            'modules' => config('college_modules'),
        ]);
    }
}
