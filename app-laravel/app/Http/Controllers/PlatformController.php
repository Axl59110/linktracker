<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    /**
     * Display a listing of platforms.
     */
    public function index()
    {
        $platforms = Platform::latest()->paginate(15);

        return view('pages.platforms.index', compact('platforms'));
    }

    /**
     * Show the form for creating a new platform.
     */
    public function create()
    {
        return view('pages.platforms.create');
    }

    /**
     * Store a newly created platform in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:marketplace,direct,other',
            'is_active' => 'boolean',
        ]);

        $platform = Platform::create($validated);

        return redirect()
            ->route('platforms.index')
            ->with('success', 'Plateforme créée avec succès.');
    }

    /**
     * Show the form for editing the specified platform.
     */
    public function edit(Platform $platform)
    {
        return view('pages.platforms.edit', compact('platform'));
    }

    /**
     * Update the specified platform in storage.
     */
    public function update(Request $request, Platform $platform)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:marketplace,direct,other',
            'is_active' => 'boolean',
        ]);

        $platform->update($validated);

        return redirect()
            ->route('platforms.index')
            ->with('success', 'Plateforme mise à jour avec succès.');
    }

    /**
     * Remove the specified platform from storage.
     */
    public function destroy(Platform $platform)
    {
        $platform->delete();

        return redirect()
            ->route('platforms.index')
            ->with('success', 'Plateforme supprimée avec succès.');
    }
}
