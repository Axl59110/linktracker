<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authentifier un utilisateur
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'L\'adresse email est requise.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
            'password.required' => 'Le mot de passe est requis.',
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Régénérer la session seulement si elle existe (pas dans les tests)
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => Auth::user(),
        ], 200);
    }

    /**
     * Déconnecter l'utilisateur actuel
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        // Invalider la session seulement si elle existe (pas dans les tests)
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ], 200);
    }

    /**
     * Récupérer l'utilisateur authentifié
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ], 200);
    }
}
