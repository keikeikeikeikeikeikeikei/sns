<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the settings form.
     */
    public function edit(): View
    {
        $user = Auth::user();
        return view('settings.edit', [
            'user' => $user,
            'settings' => $user->settings ?? [
                'theme' => 'light',
                'font_size' => 'medium',
                'layout_density' => 'comfortable',
            ],
        ]);
    }

    /**
     * Update the user's settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,blue,contrast',
            'font_size' => 'required|in:small,medium,large',
            'layout_density' => 'required|in:compact,comfortable',
        ]);

        $user = Auth::user();
        $user->settings = $validated;
        $user->save();

        return back()->with('status', 'settings-updated');
    }
}
