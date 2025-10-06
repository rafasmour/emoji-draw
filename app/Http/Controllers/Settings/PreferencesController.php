<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreferencesController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $preferences = $user->preferences;
        return Inertia::render('settings/preferences', [
            'preferences' => [
                'volume' => $preferences['volume'],
                'mute' => $preferences['mute'],
            ]
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = $request->validate([
            'volume' => ['required', 'numeric', 'min:0', 'max:100'],
            'mute' => ['required', 'boolean'],
        ]);

        $request->user()->update([
            'preferences' => [
                'volume' => $validator['volume'],
                'mute' => $validator['mute'],
            ]
        ]);

        return response()->json(['message' => 'Preferences updated successfully']);
    }
}
