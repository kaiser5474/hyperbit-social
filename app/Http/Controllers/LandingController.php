<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        return view('landing.index');
    }

    public function privacy(): View
    {
        return view('landing.privacy');
    }

    public function terms(): View
    {
        return view('landing.terms');
    }

    public function dataDeletion(): View
    {
        return view('landing.data-deletion');
    }

    public function setLocale(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['en', 'es'])) {
            session(['locale' => $locale]);
            App::setLocale($locale);
        }

        return redirect()->back();
    }
}
