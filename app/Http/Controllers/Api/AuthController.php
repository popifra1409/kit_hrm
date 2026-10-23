<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\AccountActivationRequest;
use App\Models\AccountDeletion;
use App\Services\AccountDeletionService;


/**
 * @tags Authentification
 */
class AuthController extends Controller
{
    /** Durée de validité de la session d'activation entre l'étape 1 et l'étape 2. */
    private const ACTIVATION_SESSION_MINUTES = 15;

    /**
     * Échecs tolérés avant blocage temporaire, et durée du blocage. Le même barème
     * s'applique à la connexion et à l'activation : un seul chiffre à expliquer au
     * personnel comme aux employés.
     */
    private const MAX_FAILURES = 5;

    private const LOCK_MINUTES = 45;

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
            return $this->activationLockedResponse($user);
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
            'captcha' => $this->makeCaptcha($verificationToken),
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
            'captcha' => $this->makeCaptcha($request->verification_token),
        ]);
    }

    /**
     * Activer un compte — étape 2/2 : date de recrutement + calcul
     *
     * L'employé confirme sa date de recrutement et résout le petit calcul. Si
     * tout est correct, le nouveau mot de passe est enregistré, le compte est
     * définitivement activé et un token est retourné (comme à la connexion).
     * La date est acceptée à l'année près (±1 an) : un employé qui ne se souvient
     * que de son année d'entrée peut activer son compte seul. Après 5 échecs, le
     * compte est bloqué 45 minutes pour l'activation ; l'employé peut alors passer
     * par une demande vérifiée par le Service RH (POST /auth/activate/request).
     *
     * @unauthenticated
     *
     * @bodyParam verification_token string required Jeton reçu à l'étape 1.
     * @bodyParam recruitment_date string required Date de recrutement (AAAA-MM-JJ) ; seule l'année est comparée, à ±1 an près. Example: 2015-09-01
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
     * @response 422 scenario="Calcul ou année incorrects" {"message": "Cette année ne correspond pas à nos enregistrements. Si tu ne te souviens vraiment pas, demande une vérification aux Ressources Humaines.", "remaining_attempts": 3, "captcha": {"id": "Qw27Zp...", "question": "15 - 6 = ?"}}
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
            return $this->activationLockedResponse($user);
        }

        // Contrôle A — le calcul — vérifié en premier pour ne jamais permettre de "tester"
        //    des dates sans l'avoir résolu. Chaque calcul est à usage unique.
        $captcha = Cache::pull($this->captchaKey($request->captcha_id));

        $captchaOk = $captcha
            && hash_equals((string) $captcha['token'], (string) $request->verification_token)
            && (int) $captcha['answer'] === (int) $request->captcha_answer;

        if (!$captchaOk) {
            $this->registerActivationFailure($user);

            return $this->activationFailureResponse('Le résultat du calcul est incorrect.', $request->verification_token, $user);
        }

        // Contrôle B — la date de recrutement
        $recorded = $user->employee->recruitment_date?->toDateString();
        $declared = $request->recruitment_date;

        if (!$recorded) {
            $this->registerActivationFailure($user);

            return $this->activationFailureResponse(
                'Aucune date de recrutement n\'est enregistrée pour votre compte. Contactez les Ressources Humaines.',
                $request->verification_token,
                $user
            );
        }

        if ($recorded !== $declared) {
            // La plupart des employés ne se souviennent que de l'ANNÉE de recrutement :
            // c'est ce tolérant qui rend la vérification utilisable par un employé peu
            // à l'aise avec l'informatique. Le risque reste contenu par le mot de passe
            // temporaire (étape 1), le calcul à usage unique et le blocage après 5 échecs.
            $yearMatches = abs((int) substr($declared, 0, 4) - (int) substr($recorded, 0, 4)) <= 1;

            if (!$yearMatches) {
                $this->registerActivationFailure($user);

                return $this->activationFailureResponse(
                    'Cette année ne correspond pas à nos enregistrements. Si vous ne vous rappelez vraiment pas, '
                        . 'demandez une vérification aux Ressources Humaines.',
                    $request->verification_token,
                    $user
                );
            }
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

    /**
     * Activer un compte — secours : demande de vérification au Service RH
     *
     * Pour l'employé qui ne retrouve vraiment pas sa date de recrutement : il résout
     * le calcul, une demande est enregistrée avec une référence lisible, et
     * un agent des RH ouvre le compte après avoir vérifié l'identité en personne.
     * Le mot de passe choisi à l'étape 1 n'est appliqué qu'à cette validation ;
     * en attendant, seul le mot de passe temporaire donne accès au compte.
     *
     * @unauthenticated
     *
     * @bodyParam verification_token string required Jeton reçu à l'étape 1.
     * @bodyParam captcha_id string required Identifiant du calcul affiché.
     * @bodyParam captcha_answer integer required Résultat du calcul. Example: 19
     * @bodyParam self_declared_date string nullable Ce que l'employé croit se rappeler, pour l'agent uniquement. Example: 2015-01-01
     *
     * @response 201 scenario="Demande enregistrée" {
     *   "message": "Votre demande est enregistrée...",
     *   "reference": "RH-7K4QF2",
     *   "expires_at": "2026-10-28T10:00:00+01:00"
     * }
     * @response 200 scenario="Demande déjà en attente" {"message": "...", "reference": "RH-7K4QF2", "expires_at": "..."}
     * @response 409 scenario="Compte déjà activé" {"message": "Ce compte a déjà été activé. Utilisez la connexion normale."}
     * @response 410 scenario="Session expirée" {"message": "La session d'activation a expiré. Veuillez recommencer."}
     * @response 422 scenario="Calcul incorrect" {"message": "Le résultat du calcul est incorrect.", "captcha": {"id": "Qw27Zp...", "question": "15 - 6 = ?"}}
     */
    public function requestActivationReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'verification_token' => ['required', 'string', 'size:64', 'alpha_num'],
            'captcha_id' => ['required', 'string', 'size:32', 'alpha_num'],
            'captcha_answer' => ['required', 'integer'],
            'self_declared_date' => ['nullable', 'date_format:Y-m-d'],
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

        // Le calcul reste obligatoire : il prouve qu'une personne physique est devant
        // l'écran et empêche l'engorgement de la file d'attente des RH.
        $captcha = Cache::pull($this->captchaKey($request->captcha_id));

        $captchaOk = $captcha
            && hash_equals((string) $captcha['token'], (string) $request->verification_token)
            && (int) $captcha['answer'] === (int) $request->captcha_answer;

        if (!$captchaOk) {
            // Aucun échec comptabilisé : cette route n'ouvre rien, elle enregistre juste
            // une demande qu'un agent devra refuser ou confirmer.
            return response()->json([
                'message' => 'Le résultat du calcul est incorrect.',
                'captcha' => $this->makeCaptcha($request->verification_token),
            ], 422);
        }

        $review = AccountActivationRequest::where('user_id', $user->id)
            ->where('type', AccountActivationRequest::TYPE_ACTIVATION)
            ->where('status', AccountActivationRequest::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($review) {
            // L'employé a peut-être repris l'étape 1 entre-temps : on garde la même
            // référence, mais le mot de passe enregistré est celui choisi maintenant.
            $review->forceFill([
                'requested_password_hash' => $pending['password_hash'],
                'self_declared_date' => $request->self_declared_date,
                'expires_at' => now()->addDays(AccountActivationRequest::VALIDITY_DAYS),
            ])->save();
        } else {
            $review = AccountActivationRequest::create([
                'reference' => AccountActivationRequest::generateReference(),
                'user_id' => $user->id,
                'employee_id' => $user->employee->id,
                'matricule' => $user->employee->matricule,
                'type' => AccountActivationRequest::TYPE_ACTIVATION,
                'requested_password_hash' => $pending['password_hash'],
                'self_declared_date' => $request->self_declared_date,
                'status' => AccountActivationRequest::STATUS_PENDING,
                'expires_at' => now()->addDays(AccountActivationRequest::VALIDITY_DAYS),
            ]);
        }

        return response()->json([
            'message' => 'Votre demande est enregistrée. Présentez-vous au Service RH avec cette référence '
                . 'et votre matricule : dès que l\'agent aura vérifié votre identité, votre compte s\'ouvrira avec le '
                . 'mot de passe que vous avez choisi.',
            'reference' => $review->reference,
            'expires_at' => $review->expires_at?->toIso8601String(),
        ], $review->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Réinitialisation — calcul anti-robot préalable
     *
     * @unauthenticated
     *
     * @bodyParam matricule string required Le matricule du compte à réinitialiser. Example: 98240812A
     *
     * @response 200 scenario="Calcul fourni" {"captcha": {"id": "Qw27Zp...", "question": "15 - 6 = ?"}}
     */
    public function forgotPasswordCaptcha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matricule' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return response()->json([
            'captcha' => $this->makeCaptcha($this->passwordResetCaptchaBinding($request->matricule)),
        ]);
    }

    /**
     * Réinitialisation — demande vérifiée par le Service RH
     *
     * Pour l'employé qui a oublié le mot de passe de son compte. Rien n'est modifié
     * sur le compte ici : une demande est enregistrée, l'agent vérifie l'identité de
     * la personne venue au Service RH, puis génère le mot de passe temporaire.
     *
     * @unauthenticated
     *
     * @bodyParam matricule string required Le matricule du compte à réinitialiser. Example: 98240812A
     * @bodyParam captcha_id string required Identifiant du calcul affiché.
     * @bodyParam captcha_answer integer required Résultat du calcul. Example: 19
     *
     * @response 201 scenario="Demande enregistrée" {
     *   "message": "Votre demande est enregistrée...",
     *   "reference": "RH-7K4QF2",
     *   "expires_at": "2026-10-30T10:00:00+01:00"
     * }
     * @response 200 scenario="Demande déjà en attente" {"message": "...", "reference": "RH-7K4QF2", "expires_at": "..."}
     * @response 404 scenario="Matricule inconnu" {"message": "Aucun compte n'est associé à ce matricule. Contactez les Ressources Humaines."}
     * @response 409 scenario="Compte non activé" {"message": "Ce compte n'a jamais été activé. Utilisez l'écran d'activation."}
     * @response 422 scenario="Calcul incorrect" {"message": "Le résultat du calcul est incorrect.", "captcha": {"id": "Qw27Zp...", "question": "15 - 6 = ?"}}
     */
    public function requestPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'matricule' => ['required', 'string'],
            'captcha_id' => ['required', 'string', 'size:32', 'alpha_num'],
            'captcha_answer' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $matricule = trim((string) $request->matricule);
        $binding = $this->passwordResetCaptchaBinding($matricule);

        $captcha = Cache::pull($this->captchaKey($request->captcha_id));

        $captchaOk = $captcha
            && hash_equals((string) $captcha['token'], $binding)
            && (int) $captcha['answer'] === (int) $request->captcha_answer;

        if (!$captchaOk) {
            return response()->json([
                'message' => 'Le résultat du calcul est incorrect.',
                'captcha' => $this->makeCaptcha($binding),
            ], 422);
        }

        $user = User::with('employee')->whereHas('employee', function ($query) use ($matricule) {
            $query->where('matricule', $matricule);
        })->first();

        if (!$user || !$user->employee) {
            return response()->json([
                'message' => "Aucun compte n'est associé à ce matricule. Contactez les Ressources Humaines.",
            ], 404);
        }

        // Un compte jamais ouvert n'a pas de mot de passe oublié : sa démarche est
        // l'activation, et l'agent du Service RH n'ouvrirait rien de plus.
        if (!$user->isActivated()) {
            return response()->json([
                'message' => "Ce compte n'a jamais été activé. Utilisez l'écran d'activation avec votre matricule.",
            ], 409);
        }

        $review = AccountActivationRequest::where('user_id', $user->id)
            ->where('type', AccountActivationRequest::TYPE_PASSWORD_RESET)
            ->where('status', AccountActivationRequest::STATUS_PENDING)
            ->latest('id')
            ->first();

        if (!$review) {
            $review = AccountActivationRequest::create([
                'reference' => AccountActivationRequest::generateReference(),
                'user_id' => $user->id,
                'employee_id' => $user->employee->id,
                'matricule' => $user->employee->matricule,
                'type' => AccountActivationRequest::TYPE_PASSWORD_RESET,
                'status' => AccountActivationRequest::STATUS_PENDING,
                'expires_at' => now()->addDays(AccountActivationRequest::VALIDITY_DAYS),
            ]);
        }

        return response()->json([
            'message' => 'Votre demande est enregistrée. Présentez-vous au Service RH avec cette référence et votre '
                . 'matricule : après vérification de votre identité, un agent générera un nouveau mot de passe '
                . 'temporaire que vous pourrez utiliser immédiatement.',
            'reference' => $review->reference,
            'expires_at' => $review->expires_at?->toIso8601String(),
        ], $review->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Le calcul d'une demande de réinitialisation est lié au matricule déclaré.
     */
    private function passwordResetCaptchaBinding(string $matricule): string
    {
        return 'password_reset:' . strtolower(trim($matricule));
    }

    // ------------------------------------------------------------------
    // Outils de l'activation en deux étapes
    // ------------------------------------------------------------------

    private function activationCacheKey(string $verificationToken): string
    {
        return "activation:{$verificationToken}";
    }

    private function captchaKey(string $captchaId): string
    {
        return "auth_captcha:{$captchaId}";
    }

    private function activationFailuresKey(User $user): string
    {
        return "activation_failures:{$user->id}";
    }

    private function loginFailuresKey(string $matricule): string
    {
        return 'login_failures:' . strtolower(trim($matricule));
    }

    /**
     * État du compteur d'échecs : nombre de tentatives ratées dans la fenêtre en
     * cours, et secondes restantes avant que le compte redevienne tentable.
     */
    private function failureState(string $key): array
    {
        $state = Cache::get($key);

        if (!is_array($state) || !isset($state['count'], $state['until'])) {
            return ['count' => 0, 'locked' => false, 'retry_after' => 0];
        }

        $retryAfter = max(0, $state['until'] - time());

        return [
            'count' => $state['count'],
            'locked' => $state['count'] >= self::MAX_FAILURES && $retryAfter > 0,
            'retry_after' => $retryAfter,
        ];
    }

    /**
     * Une tentative ratée de plus. La fenêtre de 45 minutes court depuis le premier
     * échec : un attaquant ne peut pas la repousser indéfiniment en attendant.
     */
    private function registerFailure(string $key): array
    {
        $state = Cache::get($key);

        if (!is_array($state) || !isset($state['count'], $state['until'])) {
            $state = ['count' => 0, 'until' => now()->addMinutes(self::LOCK_MINUTES)->timestamp];
        }

        $state['count']++;

        Cache::put($key, $state, now()->addSeconds(max(1, $state['until'] - time())));

        return $this->failureState($key);
    }

    private function minutesRemaining(int $seconds): int
    {
        return (int) ceil($seconds / 60);
    }

    private function activationFailureCount(User $user): int
    {
        return $this->failureState($this->activationFailuresKey($user))['count'];
    }

    private function activationLocked(User $user): bool
    {
        return $this->failureState($this->activationFailuresKey($user))['locked'];
    }

    private function registerActivationFailure(User $user): void
    {
        $this->registerFailure($this->activationFailuresKey($user));
    }

    private function activationLockedResponse(User $user)
    {
        $minutes = $this->minutesRemaining(
            $this->failureState($this->activationFailuresKey($user))['retry_after']
        );

        return $this->lockedResponse(
            "Trop de tentatives échouées. Réessayez dans {$minutes} minute(s) ou contactez les Ressources Humaines.",
            $minutes
        );
    }

    private function loginLockedResponse(array $state)
    {
        $minutes = $this->minutesRemaining($state['retry_after']);

        return $this->lockedResponse(
            "Trop de tentatives échouées. Ce compte est en pause pendant {$minutes} minute(s). "
            . "Vous pouvez demander l'activation ou la réinitialisation de votre mot de passe sans attendre.",
            $minutes
        );
    }

    /**
     * @param int|null $retryAfterMinutes Durée restante du blocage, quand elle est connue :
     *                                    l'écran de connexion l'affiche en propre plutôt que
     *                                    de la déduire du message.
     */
    private function lockedResponse(string $message, ?int $retryAfterMinutes = null)
    {
        return response()->json([
            'message' => $message,
            'reason' => 'locked',
            'attempts_left' => 0,
            'retry_after_minutes' => $retryAfterMinutes,
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

            return $this->activationLockedResponse($user);
        }

        return response()->json([
            'message' => $message,
            'remaining_attempts' => max(0, self::MAX_FAILURES - $this->activationFailureCount($user)),
            'captcha' => $this->makeCaptcha($verificationToken),
        ], 422);
    }

    /**
     * Petit calcul à usage unique, lié à la démarche en cours : jeton de session
     * d'activation, ou matricule pour une demande de réinitialisation.
     * La réponse n'est jamais envoyée au client : elle reste côté serveur (cache).
     */
    private function makeCaptcha(string $boundTo): array
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
            $this->captchaKey($id),
            ['answer' => $answer, 'token' => $boundTo],
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
     * @response 401 scenario="Identifiants incorrects" {"message": "Identifiants incorrects.", "reason": "invalid_credentials", "attempts_left": 3}
     * @response 403 scenario="Compte non activé" {"message": "Ce compte n'a pas encore été activé. Utilisez d'abord l'écran d'activation avec votre matricule.", "reason": "not_activated", "attempts_left": 5}
     * @response 429 scenario="Compte en pause après 5 échecs" {"message": "Trop de tentatives échouées. Ce compte est en pause pendant 45 minute(s). Vous pouvez demander l'activation ou la réinitialisation de votre mot de passe sans attendre.", "reason": "locked", "attempts_left": 0}
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

        $matricule = trim((string) $request->matricule);
        $failuresKey = $this->loginFailuresKey($matricule);

        // Le blocage est vérifié avant toute comparaison de mot de passe : un compte
        // en pause ne doit pas être une porte de sortie pour tester des mots de passe.
        $state = $this->failureState($failuresKey);

        if ($state['locked']) {
            return $this->loginLockedResponse($state);
        }

        $user = User::whereHas('employee', function ($query) use ($matricule) {
            $query->where('matricule', $matricule);
        })->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            $state = $this->registerFailure($failuresKey);

            if ($state['locked']) {
                return $this->loginLockedResponse($state);
            }

            return response()->json([
                // Volontairement identique que le matricule existe ou non : la file
                // d'attente du Service RH ne doit pas servir de carnet d'adresses.
                'message' => 'Identifiants incorrects.',
                'reason' => 'invalid_credentials',
                'attempts_left' => max(0, self::MAX_FAILURES - $state['count']),
            ], 401);
        }

        Cache::forget($failuresKey);

        if (!$user->isActivated()) {
            return response()->json([
                'message' => "Ce compte n'a pas encore été activé. Utilisez d'abord l'écran d'activation avec votre matricule.",
                'reason' => 'not_activated',
                'attempts_left' => self::MAX_FAILURES,
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
        $user->loadMissing(['employee.jobTitle', 'employee.department', 'employee.currentService', 'roles']);
        $employee = $user->employee;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
            'employee' => $employee ? [
                'id' => $employee->id,
                'matricule' => $employee->matricule,
                'full_name' => $employee->full_name,
                // Chemin relatif → URL publique, sinon le <img> de la SPA casse ;
                // et certains enregistrements pointent vers un fichier supprimé.
                'photo' => ($employee->photo && Storage::disk('public')->exists($employee->photo))
                    ? Storage::disk('public')->url($employee->photo)
                    : null,
                'job_title' => $employee->jobTitle?->name,
                'department' => $employee->department?->name,
                'service' => $employee->currentService?->name,
            ] : null,
        ];
    }
}
