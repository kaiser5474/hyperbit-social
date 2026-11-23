<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    protected array $timezones = [
        'America/New_York' => 'Eastern Time (US & Canada)',
        'America/Chicago' => 'Central Time (US & Canada)',
        'America/Denver' => 'Mountain Time (US & Canada)',
        'America/Los_Angeles' => 'Pacific Time (US & Canada)',
        'America/Mexico_City' => 'Mexico City',
        'America/Bogota' => 'Bogota',
        'America/Lima' => 'Lima',
        'America/Buenos_Aires' => 'Buenos Aires',
        'America/Sao_Paulo' => 'Sao Paulo',
        'Europe/London' => 'London',
        'Europe/Paris' => 'Paris',
        'Europe/Berlin' => 'Berlin',
        'Europe/Madrid' => 'Madrid',
        'Europe/Rome' => 'Rome',
        'Asia/Tokyo' => 'Tokyo',
        'Asia/Shanghai' => 'Shanghai',
        'Asia/Singapore' => 'Singapore',
        'Australia/Sydney' => 'Sydney',
        'Pacific/Auckland' => 'Auckland',
        'UTC' => 'UTC',
    ];

    public function index(): View
    {
        $user = auth()->user();

        return view('settings.index', [
            'user' => $user,
            'timezones' => $this->timezones,
            'locales' => [
                'es' => 'Español',
                'en' => 'English',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'timezone' => 'required|string|timezone',
            'locale' => 'required|string|in:en,es',
        ]);

        $user = auth()->user();

        $user->update([
            'timezone' => $request->timezone,
            'locale' => $request->locale,
        ]);

        session(['locale' => $request->locale]);

        return redirect()->route('settings')
            ->with('success', __('Settings updated successfully.'));
    }
}
