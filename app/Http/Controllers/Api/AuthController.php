<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Authentification
 */
class AuthController extends Controller
{
    /**
     * Activer un compte (première connexion)
     *
     * L'employé saisit son matricule et le mot de passe temporaire communiqué
     * par les Ressources Humaines, puis choisit son mot de passe définitif.
     * Le rôle "employee" est attribué automatiquement si aucun rôle n'a été
     * assigné par les RH lors de la création du compte.
     *
     * @unauthenticated
     *
     * @bodyParam matricule string required Le matricule de l'employé. Example: 98240812A
     * @bodyParam temporary_password string required Le mot de passe temporaire donné par les RH.
     * @bodyParam password string required Le nouveau mot de passe définitif (8 caractères minimum). Example: MonNouveauMotDePasse123
     * @bodyParam password_confirmation string required Confirmation du nouveau mot de passe.
     *
     * @response 200 scenario="Activation réussie" {
     *   "message": "Compte activé avec succès.",
     *   "token": "1|abcdef123456...",
     *   "user": {
     *     "id": 1,
     *     "name": "Jean Dupont",
     *     "email": "jean.dupont@example.com",
     *     "roles": ["employee"],
     *     "employee": {
     *       "id": 12,
     *       "matricule": "98240812A",
     *       "full_name": "DUPONT Jean",
     *       "photo": null
     *     }
     *   }
     * }
     * @response 401 scenario="Mot de passe temporaire incorrect" {"message": "Mot de passe temporaire incorrect."}
     * @response 404 scenario="Matricule inconnu" {"message": "Aucun compte n'est associé à ce matricule. Contactez les Ressources Humaines."}
     * @response 409 scenario="Déjà activé" {"message": "Ce compte a déjà été activé. Utilisez la connexion normale."}
     * @response 422 scenario="Validation échouée" {"message": "Données invalides.", "errors": {"password": ["Le mot de passe doit contenir au moins 8 caractères."]}}
     */
    public function activate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matricule' => ['required', 'string'],
            'temporary_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::whereHas('employee', function ($query) use ($request) {
            $query->where('matricule', $request->matricule);
        })->first();

        if (!$user) {
            return response()->json([
                'message' => "Aucun compte n'est associé à ce matricule. Contactez les Ressources Humaines.",
            ], 404);
        }

        if ($user->isActivated()) {
            return response()->json([
                'message' => 'Ce compte a déjà été activé. Utilisez la connexion normale.',
            ], 409);
        }

        if (!Hash::check($request->temporary_password, $user->password)) {
            return response()->json([
                'message' => 'Mot de passe temporaire incorrect.',
            ], 401);
        }

        $user->password = $request->password;
        $user->activated_at = now();
        $user->save();

        // Rôle par défaut "employee" si aucun rôle n'a été assigné par les RH
        if ($user->roles()->count() === 0) {
            $user->assignRole('employee');
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Compte activé avec succès.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Se connecter
     *
     * Connexion standard pour un compte déjà activé. Retourne un token
     * Sanctum à utiliser dans l'en-tête Authorization: Bearer {token}
     * pour toutes les routes protégées.
     *
     * @unauthenticated
     *
     * @bodyParam email string required L'adresse email du compte. Example: jean.dupont@example.com
     * @bodyParam password string required Le mot de passe. Example: MonMotDePasse123
     *
     * @response 200 scenario="Connexion réussie" {
     *   "message": "Connexion réussie.",
     *   "token": "2|xyz789...",
     *   "user": {
     *     "id": 1,
     *     "name": "Jean Dupont",
     *     "email": "jean.dupont@example.com",
     *     "roles": ["employee"],
     *     "employee": {"id": 12, "matricule": "98240812A", "full_name": "DUPONT Jean", "photo": null}
     *   }
     * }
     * @response 401 scenario="Identifiants incorrects" {"message": "Identifiants incorrects."}
     * @response 403 scenario="Compte non activé" {"message": "Ce compte n'a pas encore été activé. Utilisez d'abord l'écran d'activation avec votre matricule."}
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        if (!$user->isActivated()) {
            return response()->json([
                'message' => "Ce compte n'a pas encore été activé. Utilisez d'abord l'écran d'activation avec votre matricule.",
            ], 403);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Profil de l'utilisateur connecté
     *
     * Retourne les informations du compte et de l'employé lié.
     *
     * @response 200 {
     *   "user": {
     *     "id": 1,
     *     "name": "Jean Dupont",
     *     "email": "jean.dupont@example.com",
     *     "roles": ["employee"],
     *     "employee": {"id": 12, "matricule": "98240812A", "full_name": "DUPONT Jean", "photo": null}
     *   }
     * }
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    /**
     * Se déconnecter
     *
     * Révoque uniquement le token utilisé pour cette requête (les autres
     * sessions/appareils connectés restent actifs).
     *
     * @response 200 {"message": "Déconnecté avec succès."}
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnecté avec succès.',
        ]);
    }

    private function formatUser(User $user): array
    {
        $user->loadMissing('employee', 'roles');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
            'employee' => $user->employee ? [
                'id' => $user->employee->id,
                'matricule' => $user->employee->matricule,
                'full_name' => $user->employee->full_name,
                'photo' => $user->employee->photo,
            ] : null,
        ];
    }
}
