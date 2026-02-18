<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('pages.settings.index', compact('user'));
    }

    public function updateMonitoring(Request $request)
    {
        $validated = $request->validate([
            'check_frequency'      => ['required', 'in:hourly,daily,weekly'],
            'http_timeout'         => ['required', 'integer', 'min:5', 'max:120'],
            'email_alerts_enabled' => ['sometimes', 'boolean'],
        ]);

        $validated['email_alerts_enabled'] = $request->boolean('email_alerts_enabled');

        auth()->user()->update($validated);

        return back()->with('success', 'Paramètres de monitoring sauvegardés.');
    }

    public function updateSeo(Request $request)
    {
        $validated = $request->validate([
            'seo_provider' => ['required', 'in:moz,custom'],
            'seo_api_key'  => ['nullable', 'string', 'max:500'],
        ]);

        $updateData = ['seo_provider' => $validated['seo_provider']];

        if (! empty($validated['seo_api_key'])) {
            $updateData['seo_api_key_encrypted'] = Crypt::encryptString($validated['seo_api_key']);
        }

        auth()->user()->update($updateData);

        return back()->with('success', 'Configuration API SEO sauvegardée.');
    }

    public function testSeoConnection(Request $request)
    {
        $user = auth()->user();

        if ($user->seo_provider === 'custom' || empty($user->seo_api_key_encrypted)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun provider configuré. Sélectionnez Moz et entrez votre clé API.',
            ]);
        }

        try {
            $apiKey = Crypt::decryptString($user->seo_api_key_encrypted);
            [$accessId, $secretKey] = array_pad(explode(':', $apiKey, 2), 2, '');

            $response = Http::withBasicAuth($accessId, $secretKey)
                ->timeout(10)
                ->post('https://lsapi.seomoz.com/v2/url_metrics', [
                    'targets' => ['google.com'],
                    'metrics' => ['domain_authority'],
                ]);

            if ($response->successful()) {
                $da = $response->json('results.0.domain_authority');
                return response()->json([
                    'success' => true,
                    'message' => "Connexion réussie ! DA de google.com = " . round($da),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Erreur API HTTP {$response->status()}. Vérifiez vos identifiants.",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ]);
        }
    }
}
