<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\AccountDeletion;
use App\Services\AccountDeletionService;


/**
 * @tags Authentification
 */
class AuthController extends Controller
{
    /** Durée de validité de la session d'activation entre l'étape 1 et l'étape 2. */
    private const ACTIVATION_SESSION_MINUTES = 15;

    /** Échecs de vérification tolérés par compte avant blocage temporaire. */
    private const ACTIVATION_MAX_FAILURES = 5;

    private const ACTIVATION_LOCK_MINUTES = 30;

    /**
     * Activer un compte — étape 1/2 : identifiants et nouveau mot de passe
     *
     * L'employé saisit son matricule, le mot de passe temporaire communiqué par
     * les Ressources Humaines, puis choisit son mot de passe définitif. Le compte
     * n'est PAS encore activé à ce stade : une seconde vérification (date de
     * recrutement + petit calcul) est exigée via POST /auth/activate/verify.
     * Le nouveau mot de passe n'est enregistré qu'à l'issue de cette seconde
     * étape ; tant qu'elle n'est pas réussie, le mot de passe temporaire reste valable.
     *
     * Si aucune date de recrutement n'est enregistrée pour l'employé, l'activation
     * est refusée (il doit contacter les RH).
     *
     * @unauthenticated
     *
     * @bodyParam matricule string required Le matricule de l'employé. Example: 98240812A
     * @bodyParam temporary_password string required Le mot de passe temporaire donné par les RH.
     * @bodyParam password string required Le nouveau mot de passe définitif (8 caractères minimum). Example: MonNouveauMotDePasse123
     * @bodyParam password_confirmation string required Confirmation du nouveau mot de passe.
     *
     * @response 200 scenario="Identifiants vérifiés" {
     *   "message": "Identifiants vérifiés. Une dernière vérification est nécessaire pour activer votre compte.",
     *   "verification_token": "k3J9xQ...(64 caractères)",
     *   "expires_in": 900,
     *   "captcha": {"id": "Zx81Ab...", "question": "7 + 12 = ?"}
     * }
     * @response 401 scenario="Mot de passe temporaire incorrect" {"message": "Mot de passe temporaire incorrect."}
     * @response 404 scenario="Matricule inconnu" {"message": "Aucun compte n'est associé à ce matricule. Contactez les Ressources Humaines."}
     * @response 409 scenario="Déjà activé" {"message": "Ce compte a déjà été activé. Utilisez la connexion normale."}
     * @response 422 scenario="Date de recrutement non enregistrée" {"message": "Votre date de recrutement n'est pas enregistrée dans le système. Veuillez contacter les Ressources Humaines pour activer votre compte."}
     * @response 429 scenario="Compte temporairement bloqué" {"message": "Trop de tentatives échouées. Réessayez dans 30 minutes ou contactez les Ressources Humaines."}
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

        if ($this->activationLocked($user)) {
            return $this->activationLockedResponse();
        }

        if (!Hash::check($request->temporary_password, $user->password)) {
            return response()->json([
                'message' => 'Mot de passe temporaire incorrect.',
            ], 401);
        }

        if (!$user->employee->recruitment_date) {
            return response()->json([
                'message' => "Votre date de recrutement n'est pas enregistrée dans le système. Veuillez contacter les Ressources Humaines pour activer votre compte.",
            ], 422);
        }

        // Le mot de passe choisi est conservé (haché) le temps de la seconde
        // vérification, mais N'EST PAS encore appliqué au compte.
        $verificationToken = Str::random(64);

        Cache::put(
            $this->activationCacheKey($verificationToken),
            [
                'user_id' => $user->id,
                'password_hash' => Hash::make($request->password),
            ],
            now()->addMinutes(self::ACTIVATION_SESSION_MINUTES)
        );

        return response()->json([
            'message' => 'Identifiants vérifiés. Une dernière vérification est nécessaire pour activer votre compte.',
            'verification_token' => $verificationToken,
            'expires_in' => self::ACTIVATION_SESSION_MINUTES * 60,
            'captcha' => $this->makeActivationCaptcha($verificationToken),
        ]);
    }

    /**
     * Activer un compte — nouveau calcul
     *
     * Génère un nouveau petit calcul si le précédent n'est pas souhaité. Chaque
     * calcul n'est utilisable qu'une seule fois.
     *
     * @unauthenticated
     *
     * @bodyParam verification_token string required Jeton reçu à l'étape 1.
     *
     * @response 200 {"captcha": {"id": "Zx81Ab...", "question": "9 × 4 = ?"}}
     * @response 410 scenario="Session expirée" {"message": "La session d'activation a expiré. Veuillez recommencer."}
     */
    public function refreshActivationCaptcha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'verification_token' => ['required', 'string', 'size:64', 'alpha_num'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Cache::has($this->activationCacheKey($request->verification_token))) {
            return $this->activationExpiredResponse();
        }

        return response()->json([
            'captcha' => $this->makeActivationCaptcha($request->verification_token),
        ]);
    }

    /**
     * Activer un compte — étape 2/2 : date de recrutement + calcul
     *
     * L'employé confirme sa date de recrutement et résout le petit calcul. Si
     * tout est correct, le nouveau mot de passe est enregistré, le compte est
     * définitivement activé et un token est retourné (comme à la connexion).
     * Après 5 échecs, le compte est bloqué 30 minutes pour l'activation.
     *
     * @unauthenticated
     *
     * @bodyParam verification_token string required Jeton reçu à l'étape 1.
     * @bodyParam recruitment_date string required Date de recrutement (AAAA-MM-JJ). Example: 2015-09-01
     * @bodyParam captcha_id string required Identifiant du calcul affiché.
     * @bodyParam captcha_answer integer required Résultat du calcul. Example: 19
     *
     * @response 200 scenario="Activation réussie" {
     *   "message": "Compte activé avec succès.",
     *   "token": "1|abcdef123456...",
     *   "user": {
     *     "id": 1,
     *     "name": "Jean Dupont",
     *     "email": "jean.dupont@example.com",
     *     "roles": ["employee"],
     *     "employee": {"id": 12, "matricule": "98240812A", "full_name": "DUPONT Jean", "photo": null}
     *   }
     * }
     * @response 410 scenario="Session expirée" {"message": "La session d'activation a expiré. Veuillez recommencer."}
     * @response 422 scenario="Calcul ou date incorrects" {"message": "La date de recrutement saisie ne correspond pas à nos enregistrements.", "remaining_attempts": 3, "captcha": {"id": "Qw27Zp...", "question": "15 - 6 = ?"}}
     * @response 429 scenario="Compte temporairement bloqué" {"message": "Trop de tentatives échouées. Réessayez dans 30 minutes ou contactez les Ressources Humaines."}
     */
    public function verifyActivation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'verification_token' => ['required', 'string', 'size:64', 'alpha_num'],
            'recruitment_date' => ['required', 'date_format:Y-m-d'],
            'captcha_id' => ['required', 'string', 'size:32', 'alpha_num'],
            'captcha_answer' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sessionKey = $this->activationCacheKey($request->verification_token);
        $pending = Cache::get($sessionKey);

        if (!$pending) {
            return $this->activationExpiredResponse();
        }

        $user = User::with('employee')->find($pending['user_id']);

        if (!$user || !$user->employee) {
            Cache::forget($sessionKey);
            return $this->activationExpiredResponse();
        }

        if ($user->isActivated()) {
            Cache::forget($sessionKey);
            return response()->json([
                'message' => 'Ce compte a déjà été activé. Utilisez la connexion normale.',
            ], 409);
        }

        if ($this->activationLocked($user)) {
            Cache::forget($sessionKey);
            return $this->activationLockedResponse();
        }

        // Contrôle A — le calcul — vérifié en premier pour ne jamais permettre de "tester"
        //    des dates sans l'avoir résolu. Chaque calcul est à usage unique.
        $captcha = Cache::pull($this->activationCaptchaKey($request->captcha_id));

        $captchaOk = $captcha
            && hash_equals((string) $captcha['token'], (string) $request->verification_token)
            && (int) $captcha['answer'] === (int) $request->captcha_answer;

        if (!$captchaOk) {
            $this->registerActivationFailure($user);

            return $this->activationFailureResponse('Le résultat du calcul est incorrect.', $request->verification_token, $user);
        }

        // Contrôle B — la date de recrutement
        $recorded = $user->employee->recruitment_date?->toDateString();

        if (!$recorded || $recorded !== $request->recruitment_date) {
            $this->registerActivationFailure($user);

            return $this->activationFailureResponse(
                'La date de recrutement saisie ne correspond pas à nos enregistrements.',
                $request->verification_token,
                $user
            );
        }

        // Tout est correct : on applique enfin le mot de passe choisi et on active.
        $user->password = $pending['password_hash'];
        $user->activated_at = now();
        $user->save();

        // Rôle par défaut "employee" si aucun rôle n'a été assigné par les RH
        if ($user->roles()->count() === 0) {
            $user->assignRole('employee');
        }

        Cache::forget($sessionKey);
        Cache::forget($this->activationFailuresKey($user));

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Compte activé avec succès.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    // ------------------------------------------------------------------
    // Outils de l'activation en deux étapes
    // ------------------------------------------------------------------

    private function activationCacheKey(string $verificationToken): string
    {
        return "activation:{$verificationToken}";
    }

    private function activationCaptchaKey(string $captchaId): string
    {
        return "activation_captcha:{$captchaId}";
    }

    private function activationFailuresKey(User $user): string
    {
        return "activation_failures:{$user->id}";
    }

    private function activationFailureCount(User $user): int
    {
        return (int) Cache::get($this->activationFailuresKey($user), 0);
    }

    private function activationLocked(User $user): bool
    {
        return $this->activationFailureCount($user) >= self::ACTIVATION_MAX_FAILURES;
    }

    private function registerActivationFailure(User $user): void
    {
        $key = $this->activationFailuresKey($user);

        Cache::add($key, 0, now()->addMinutes(self::ACTIVATION_LOCK_MINUTES));
        Cache::increment($key);
    }

    private function activationLockedResponse()
    {
        return response()->json([
            'message' => 'Trop de tentatives échouées. Réessayez dans ' . self::ACTIVATION_LOCK_MINUTES . ' minutes ou contactez les Ressources Humaines.',
        ], 429);
    }

    private function activationExpiredResponse()
    {
        return response()->json([
            'message' => "La session d'activation a expiré. Veuillez recommencer.",
        ], 410);
    }

    /**
     * Réponse d'échec de vérification : nouveau calcul fourni, ou blocage si le
     * quota d'échecs vient d'être atteint (la session est alors détruite).
     */
    private function activationFailureResponse(string $message, string $verificationToken, User $user)
    {
        if ($this->activationLocked($user)) {
            Cache::forget($this->activationCacheKey($verificationToken));

            return $this->activationLockedResponse();
        }

        return response()->json([
            'message' => $message,
            'remaining_attempts' => max(0, self::ACTIVATION_MAX_FAILURES - $this->activationFailureCount($user)),
            'captcha' => $this->makeActivationCaptcha($verificationToken),
        ], 422);
    }

    /**
     * Petit calcul à usage unique, lié à la session d'activation en cours.
     * La réponse n'est jamais envoyée au client : elle reste côté serveur (cache).
     */
    private function makeActivationCaptcha(string $verificationToken): array
    {
        $operator = ['+', '-', '×'][random_int(0, 2)];

        if ($operator === '+') {
            $a = random_int(2, 20);
            $b = random_int(2, 20);
            $answer = $a + $b;
        } elseif ($operator === '-') {
            $a = random_int(10, 30);
            $b = random_int(1, $a - 1);
            $answer = $a - $b;
        } else {
            $a = random_int(2, 9);
            $b = random_int(2, 9);
            $answer = $a * $b;
        }

        $id = Str::random(32);

        Cache::put(
            $this->activationCaptchaKey($id),
            ['answer' => $answer, 'token' => $verificationToken],
            now()->addMinutes(10)
        );

        return [
            'id' => $id,
            'question' => "{$a} {$operator} {$b} = ?",
        ];
    }

    /**
     * Se connecter
     *
     * Connexion standard pour un compte déjà activé, via matricule + mot de passe.
     * Retourne un token Sanctum à utiliser dans l'en-tête
     * Authorization: Bearer {token} pour toutes les routes protégées.
     *
     * @unauthenticated
     *
     * @bodyParam matricule string required Le matricule de l'employé. Example: 98240812A
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
            'matricule' => ['required', 'string'],
            'password' => ['required', 'string'],
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

    /**
     * Changer mon mot de passe
     *
     * Nécessite le mot de passe actuel pour confirmation. Les autres sessions/
     * appareils connectés (autres tokens) ne sont pas affectés.
     *
     * @bodyParam current_password string required Mot de passe actuel. Example: MonAncienMotDePasse
     * @bodyParam password string required Nouveau mot de passe (8 caractères minimum). Example: MonNouveauMotDePasse123
     * @bodyParam password_confirmation string required Confirmation du nouveau mot de passe.
     *
     * @response 200 {"message": "Mot de passe mis à jour avec succès."}
     * @response 401 scenario="Mot de passe actuel incorrect" {"message": "Le mot de passe actuel est incorrect."}
     * @response 422 scenario="Validation échouée" {"message": "Données invalides.", "errors": {"password": ["Le mot de passe doit contenir au moins 8 caractères."]}}
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 401);
        }

        $user->password = $request->password;
        $user->save();

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès.',
        ]);
    }

    /**
     * Suppression du compte utilisateur par l'employé lui-même (auto-service).
     *
     * L'employé reste en base avec toutes ses informations — seul son compte de
     * connexion (login mobile/web) est supprimé. Nécessite la confirmation du mot
     * de passe actuel pour éviter toute suppression accidentelle.
     *
     * @bodyParam password string required Mot de passe actuel, pour confirmation.
     * @bodyParam reason string required resignation|death|retirement|other
     * @bodyParam notes string Précisions optionnelles.
     *
     * @response 200 {"message": "Votre compte a été supprimé."}
     * @response 422 scenario="Mot de passe incorrect" {"message": "Mot de passe incorrect."}
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
            'reason' => ['required', 'in:resignation,death,retirement,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Données invalides.', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Mot de passe incorrect.'], 422);
        }

        try {
            app(AccountDeletionService::class)->delete(
                user: $user,
                reason: $request->reason,
                notes: $request->notes,
                initiatedBy: 'self',
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['message' => 'Votre compte a été supprimé.']);
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
