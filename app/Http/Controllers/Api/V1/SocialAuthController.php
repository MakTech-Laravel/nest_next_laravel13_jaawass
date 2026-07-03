<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Auth\IssuePersonalAccessTokenAction;
use App\Actions\Api\V1\Auth\RecordLoginHistoryAction;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Models\UserFactoryImage;
use App\Models\Company;
use App\Services\Manufacturer\ManufacturerRegistrationNotificationService;
use App\Services\ManufacturerAccountGate;
use App\Support\Manufacturer\ManufacturerProfileRelations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response as HttpStatus;
use Throwable;

class SocialAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    private const PROVIDERS = [
        'google' => 'google_id',
        'facebook' => 'facebook_id',
    ];

    /** Minutes the setup_token remains valid for profile completion. */
    private const SETUP_TOKEN_TTL = 30;

    private const CACHE_SETUP_PREFIX = 'social_setup:';

    private const CACHE_STATE_PREFIX = 'social_state:';

    // Google's official token verification endpoint
    private const GOOGLE_TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';

    private const GOOGLE_USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    // Facebook Graph API endpoints
    private const FACEBOOK_GRAPH_URL = 'https://graph.facebook.com/v19.0';

    private const FACEBOOK_DEBUG_URL = 'https://graph.facebook.com/v19.0/debug_token';

    /*
    |--------------------------------------------------------------------------
    | Public Endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/v1/auth/{provider}/redirect?role=buyer|manufacturer
     *
     * Returns the provider's OAuth authorization URL.
     * Frontend opens this URL in the browser to begin the redirect flow.
     * Primarily a fallback — most modern frontends use the token flow instead.
     */
    public function redirectUrl(string $provider, Request $request): JsonResponse
    {
        if (! $this->isValidProvider($provider)) {
            return $this->invalidProviderResponse();
        }

        $role = $request->query('role', UserRole::BUYER->value);

        // Step 1 — Reject completely unknown role values (not in the enum at all)
        if (! $this->isValidEnumRole($role)) {
            return sendResponse(
                status: false,
                message: 'The selected role is invalid.',
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Step 2 — Reject roles that exist but are not permitted for social login (e.g. admin)
        if (! $this->isAllowedSocialRole($role)) {
            return sendResponse(
                status: false,
                message: 'Social login is not available for the admin role.',
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        // Encode role into the state parameter so the callback can recover it
        $stateKey = Str::uuid()->toString();
        Cache::put(
            self::CACHE_STATE_PREFIX.$stateKey,
            ['role' => $role],
            now()->addMinutes(10)
        );

        $redirectUrl = Socialite::driver($provider)
            ->stateless()
            ->with(['state' => $stateKey])
            ->redirect()
            ->getTargetUrl();

        return sendResponse(
            status: true,
            message: 'Social redirect URL generated.',
            data: ['redirect_url' => $redirectUrl],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /**
     * GET /api/v1/auth/{provider}/callback
     *
     * The provider redirects here after user consent (redirect flow).
     * Returns a Bearer token or setup_token as JSON.
     */
    public function handleCallback(string $provider, Request $request): JsonResponse
    {
        if (! $this->isValidProvider($provider)) {
            return $this->invalidProviderResponse();
        }

        if ($request->has('error')) {
            return sendResponse(
                status: false,
                message: 'Social login was cancelled or access was denied.',
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Recover role from the state key we stored before the redirect
        $stateKey = $request->query('state', '');
        $stateData = filled($stateKey) ? Cache::pull(self::CACHE_STATE_PREFIX.$stateKey) : null;
        $role = is_array($stateData) ? ($stateData['role'] ?? UserRole::BUYER->value) : UserRole::BUYER->value;

        try {
            $socialiteUser = Socialite::driver($provider)->stateless()->user();
        } catch (Throwable $e) {
            Log::error("Social [{$provider}] callback error: {$e->getMessage()}");

            return sendResponse(
                status: false,
                message: 'Failed to authenticate with the provider. The authorization code may have expired.',
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        Log::info('Socialite user', ['socialiteUser' => $socialiteUser]);

        return $this->loginOrRegister(
            providerId: $socialiteUser->getId(),
            email: $socialiteUser->getEmail(),
            name: $socialiteUser->getName(),
            avatar: $socialiteUser->getAvatar(),
            provider: $provider,
            role: $role,
            request: $request
        );
    }

    /**
     * POST /api/v1/auth/google/token
     *
     * Accepts either:
     *   - An ID token  (JWT, from @react-oauth/google GoogleLogin button / mobile SDKs)
     *   - An access token (ya29.xxx, from useGoogleLogin implicit flow)
     *
     * Auto-detected from token format. Verified directly with Google's API.
     *
     * Body: { "token": "...", "role": "buyer|manufacturer", "device_name": "web" }
     */
    public function googleTokenLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            // Step 1 — must be a valid UserRole enum value
            'role' => ['required', 'string', Rule::enum(UserRole::class)],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return sendResponse(
                status: false,
                message: $validator->errors()->first(),
                data: $validator->errors()->toArray(),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $token = $request->string('token')->toString();
        $role = $request->string('role')->toString();

        // Step 2 — must be an allowed role for social login (not admin)
        if (! $this->isAllowedSocialRole($role)) {
            return sendResponse(
                status: false,
                message: 'Social login is not available for the admin role.',
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        // ID tokens are JWTs — they have three base64url segments separated by dots.
        // Access tokens are opaque strings (Google access tokens begin with "ya29.").
        return $this->looksLikeJwt($token)
            ? $this->loginWithGoogleIdToken($token, $role, $request)
            : $this->loginWithGoogleAccessToken($token, $role, $request);
    }

    /**
     * POST /api/v1/auth/facebook/token
     *
     * Accepts a Facebook user access token from the Facebook JS SDK or mobile SDK.
     * Token is verified against Facebook's debug_token endpoint before use.
     *
     * Body: { "token": "EAAxxxxx...", "role": "buyer|manufacturer", "device_name": "web" }
     */
    public function facebookTokenLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            // Step 1 — must be a valid UserRole enum value
            'role' => ['required', 'string', Rule::enum(UserRole::class)],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return sendResponse(
                status: false,
                message: $validator->errors()->first(),
                data: $validator->errors()->toArray(),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $token = $request->string('token')->toString();
        $role = $request->string('role')->toString();

        // Step 2 — must be an allowed role for social login (not admin)
        if (! $this->isAllowedSocialRole($role)) {
            return sendResponse(
                status: false,
                message: 'Social login is not available for the admin role.',
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        // Step 3 — verify the token with Facebook's debug_token endpoint
        $verification = $this->verifyFacebookToken($token);

        if ($verification !== null) {
            return $verification; // returns an error JsonResponse
        }

        // Step 4 — fetch the user's profile from the Graph API
        try {
            $fbUser = Socialite::driver('facebook')->stateless()->userFromToken($token);
        } catch (Throwable $e) {
            Log::error("Facebook userFromToken error: {$e->getMessage()}");

            return sendResponse(
                status: false,
                message: 'Could not retrieve your Facebook profile. Please try again.',
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->loginOrRegister(
            providerId: $fbUser->getId(),
            email: $fbUser->getEmail(),
            name: $fbUser->getName(),
            avatar: $fbUser->getAvatar(),
            provider: 'facebook',
            role: $role,
            request: $request
        );
    }

    /**
     * POST /api/v1/auth/social/complete-profile
     *
     * Called after receiving a setup_token.
     * Completes the buyer or manufacturer profile and issues a Bearer token.
     *
     * Buyer (JSON):
     * {
     *   "setup_token":    "uuid",
     *   "company_name":   "ACME Ltd",
     *   "agreed_to_terms": true,
     *   "country":        "BD"        // optional
     * }
     *
     * Manufacturer (multipart/form-data):
     * {
     *   "setup_token":      "uuid",
     *   "company_name":     "Factory X",
     *   "agreed_to_terms":  1,
     *   "business_licence": [file],
     *   "city":             "Dhaka",
     *   "company_website":  "https://factoryx.com",
     *   "country":          "BD",          // optional
     *   "factory_images[]": [files],       // optional, up to 10
     *   "notes":            "ISO 9001"     // optional
     * }
     */
    public function completeProfile(Request $request): JsonResponse
    {
        // Resolve and validate the setup_token first
        $tokenValidator = Validator::make($request->all(), [
            'setup_token' => ['required', 'string'],
        ]);

        if ($tokenValidator->fails()) {
            return sendResponse(
                status: false,
                message: 'A valid setup_token is required.',
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $cacheKey = self::CACHE_SETUP_PREFIX.$request->string('setup_token')->toString();
        $payload = Cache::get($cacheKey);

        if (! is_array($payload) || ! isset($payload['user_id'], $payload['role'])) {
            return sendResponse(
                status: false,
                message: 'Setup token is invalid or has expired. Please sign in with your social account again.',
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user = User::query()->find($payload['user_id']);

        if (! $user) {
            Cache::forget($cacheKey);

            return sendResponse(
                status: false,
                message: 'Account not found.',
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        // Re-check in case the account was modified while the setup_token was in flight
        $blockResponse = $this->checkAccountBlocked($user);
        if ($blockResponse !== null) {
            return $blockResponse;
        }

        $role = UserRole::from($payload['role']);

        return match (true) {
            $role->isBuyer() => $this->completeBuyerProfile($request, $user, $cacheKey),
            $role->isManufacturer() => $this->completeManufacturerProfile($request, $user, $cacheKey),
            default => sendResponse(
                status: false,
                message: 'Profile completion is not supported for this account type.',
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Google Verification (private)
    |--------------------------------------------------------------------------
    */

    /**
     * Verify a Google ID token (JWT) against Google's tokeninfo endpoint.
     * Returns a JsonResponse on failure, or null on success with $payload populated.
     */
    private function loginWithGoogleIdToken(string $idToken, string $role, Request $request): JsonResponse
    {
        try {
            // Google validates the JWT signature, expiry, and audience server-side
            $response = Http::timeout(10)->get(self::GOOGLE_TOKENINFO_URL, [
                'id_token' => $idToken,
            ]);

            if (! $response->successful()) {
                Log::warning('Google ID token verification failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return sendResponse(
                    status: false,
                    message: 'Invalid or expired Google ID token.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $payload = $response->json();

            // Security: ensure the token was issued for OUR app, not a different one
            if (($payload['aud'] ?? '') !== config('services.google.client_id')) {
                Log::warning('Google ID token audience mismatch', [
                    'expected' => config('services.google.client_id'),
                    'received' => $payload['aud'] ?? 'none',
                ]);

                return sendResponse(
                    status: false,
                    message: 'Invalid Google ID token.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // Double-check expiry (Google checks it too, but belt-and-suspenders)
            if ((int) ($payload['exp'] ?? 0) < time()) {
                return sendResponse(
                    status: false,
                    message: 'Google ID token has expired.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // Email must be verified by Google
            if (($payload['email_verified'] ?? 'false') !== 'true') {
                return sendResponse(
                    status: false,
                    message: 'Your Google account email is not verified.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        } catch (Throwable $e) {
            Log::error('Google ID token verification exception: '.$e->getMessage());

            return sendResponse(
                status: false,
                message: 'Could not verify your Google ID token. Please try again.',
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $name = trim(($payload['given_name'] ?? '').' '.($payload['family_name'] ?? ''))
            ?: ($payload['name'] ?? null);

        return $this->loginOrRegister(
            providerId: $payload['sub'],
            email: $payload['email'] ?? null,
            name: $name ?: null,
            avatar: $payload['picture'] ?? null,
            provider: 'google',
            role: $role,
            request: $request
        );
    }

    /**
     * Verify a Google OAuth2 access token by fetching the userinfo endpoint.
     * Google will reject the request if the token is invalid or expired.
     */
    private function loginWithGoogleAccessToken(string $accessToken, string $role, Request $request): JsonResponse
    {
        try {
            $response = Http::timeout(10)
                ->withToken($accessToken)
                ->get(self::GOOGLE_USERINFO_URL);

            if (! $response->successful()) {
                Log::warning('Google access token userinfo failed', [
                    'status' => $response->status(),
                ]);

                return sendResponse(
                    status: false,
                    message: 'Invalid or expired Google access token.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $profile = $response->json();

            if (! isset($profile['sub'])) {
                return sendResponse(
                    status: false,
                    message: 'Could not retrieve your Google profile.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            if (($profile['email_verified'] ?? false) !== true) {
                return sendResponse(
                    status: false,
                    message: 'Your Google account email is not verified.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        } catch (Throwable $e) {
            Log::error('Google access token verification exception: '.$e->getMessage());

            return sendResponse(
                status: false,
                message: 'Could not verify your Google access token. Please try again.',
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $name = trim(($profile['given_name'] ?? '').' '.($profile['family_name'] ?? ''))
            ?: ($profile['name'] ?? null);

        return $this->loginOrRegister(
            providerId: $profile['sub'],
            email: $profile['email'] ?? null,
            name: $name ?: null,
            avatar: $profile['picture'] ?? null,
            provider: 'google',
            role: $role,
            request: $request
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Facebook Verification (private)
    |--------------------------------------------------------------------------
    */

    /**
     * Verify a Facebook user access token using the debug_token endpoint.
     *
     * Returns a JsonResponse error if verification fails, or null if the token is valid.
     * The app access token ({app_id}|{app_secret}) is used as the inspector token —
     * this is Facebook's officially documented approach.
     */
    private function verifyFacebookToken(string $userToken): ?JsonResponse
    {
        $appId = config('services.facebook.client_id');
        $appSecret = config('services.facebook.client_secret');
        $appToken = "{$appId}|{$appSecret}";

        try {
            $response = Http::timeout(10)->get(self::FACEBOOK_DEBUG_URL, [
                'input_token' => $userToken,
                'access_token' => $appToken,
            ]);

            if (! $response->successful()) {
                Log::warning('Facebook debug_token request failed', ['status' => $response->status()]);

                return sendResponse(
                    status: false,
                    message: 'Could not verify your Facebook token.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $data = $response->json('data', []);

            // Token must be valid and not expired
            if (! ($data['is_valid'] ?? false)) {
                Log::warning('Facebook token is invalid', ['error' => $data['error'] ?? null]);

                return sendResponse(
                    status: false,
                    message: 'Your Facebook access token is invalid or has expired.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // Token must belong to OUR Facebook app
            if ((string) ($data['app_id'] ?? '') !== (string) $appId) {
                Log::warning('Facebook token app_id mismatch', [
                    'expected' => $appId,
                    'received' => $data['app_id'] ?? 'none',
                ]);

                return sendResponse(
                    status: false,
                    message: 'Invalid Facebook access token.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // Token must not be expired
            if (isset($data['expires_at']) && $data['expires_at'] > 0 && $data['expires_at'] < time()) {
                return sendResponse(
                    status: false,
                    message: 'Your Facebook access token has expired.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        } catch (Throwable $e) {
            Log::error('Facebook token verification exception: '.$e->getMessage());

            return sendResponse(
                status: false,
                message: 'Could not verify your Facebook token. Please try again.',
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return null; // null = verification passed
    }

    /*
    |--------------------------------------------------------------------------
    | Core Login / Register Logic
    |--------------------------------------------------------------------------
    */

    /**
     * The single entry point for all social login/register attempts.
     * Finds or creates the user, then routes to the correct role handler.
     */
    private function loginOrRegister(
        string $providerId,
        ?string $email,
        ?string $name,
        ?string $avatar,
        string $provider,
        string $role,
        Request $request
    ): JsonResponse {
        $providerIdField = self::PROVIDERS[$provider];

        // 1. Find by social provider ID (most reliable — never changes)
        $user = User::query()->where($providerIdField, $providerId)->first();

        // 2. Fall back to email match — links social to an existing password account
        if (! $user && filled($email)) {
            $user = User::query()->where('email', $email)->first();
        }

        if ($user) {
            // ── Existing user ─────────────────────────────────────────────

            // Block admins from ever using social login
            if ($user->role->isAdmin()) {
                return sendResponse(
                    status: false,
                    message: 'Social login is not available for admin accounts.',
                    data: null,
                    statusCode: HttpStatus::HTTP_FORBIDDEN
                );
            }

            // Check deletion / permanent deletion
            $blockResponse = $this->checkAccountBlocked($user);
            if ($blockResponse !== null) {
                return $blockResponse;
            }

            // Opportunistically link provider ID and avatar if not yet stored
            $updates = [];
            if (! $user->{$providerIdField}) {
                $updates[$providerIdField] = $providerId;
            }
            if (! $user->avatar && filled($avatar)) {
                $updates['avatar'] = $avatar;
            }
            if (! empty($updates)) {
                $user->forceFill($updates)->save();
            }
        } else {
            // ── New user ──────────────────────────────────────────────────

            if (blank($email)) {
                return sendResponse(
                    status: false,
                    message: 'Could not retrieve your email from the social provider. Please ensure email permission is granted and try again.',
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            [$firstName, $lastName] = $this->splitName($name);

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'email_verified_at' => now(), // Social providers pre-verify emails
                'password' => null,  // Social-only account — no password
                'role' => $role,
                $providerIdField => $providerId,
                'avatar' => $avatar,
            ]);
        }

        // ── Route to role-specific handler ────────────────────────────────
        return match (true) {
            $user->role->isBuyer() => $this->handleBuyerLogin($user, $provider, $request),
            $user->role->isManufacturer() => $this->handleManufacturerLogin($user, $provider, $request),
            default => sendResponse(
                status: false,
                message: 'Social login is not available for this account type.',
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Buyer Flow
    |--------------------------------------------------------------------------
    */

    private function handleBuyerLogin(User $user, string $provider, Request $request): JsonResponse
    {
        $info = $user->company;
        $hasCompanyName = $info !== null && filled($info->company_name);
        $hasAgreedToTerms = (bool) $user->agreed_to_terms;

        if (! $hasCompanyName || ! $hasAgreedToTerms) {
            return $this->requiresProfileCompletion(
                user: $user,
                provider: $provider,
                required: array_values(array_filter([
                    ! $hasCompanyName ? 'company_name' : null,
                    ! $hasAgreedToTerms ? 'agreed_to_terms' : null,
                ])),
                optional: ['country'],
            );
        }

        return $this->issueTokenResponse($user, $provider, $request);
    }

    private function completeBuyerProfile(Request $request, User $user, string $cacheKey): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => ['required', 'string', 'max:255'],
            'agreed_to_terms' => ['required', 'accepted'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return sendResponse(
                status: false,
                message: $validator->errors()->first(),
                data: $validator->errors()->toArray(),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $validated = $validator->validated();

        $user->forceFill(['agreed_to_terms' => true])->save();

        Company::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $validated['company_name'],
                'country' => $validated['country'] ?? null,
            ]
        );

        // Consume setup token — single use only
        Cache::forget($cacheKey);

        $user->refresh()->loadMissing(['company', 'factoryImages']);

        return $this->issueTokenResponse($user, 'social', $request);
    }

    /*
    |--------------------------------------------------------------------------
    | Manufacturer Flow
    |--------------------------------------------------------------------------
    */

    private function handleManufacturerLogin(User $user, string $provider, Request $request): JsonResponse
    {
        $info = $user->company;

        $hasCompanyName = $info !== null && filled($info->company_name);
        $hasBizLicense = $info !== null && filled($info->bussiness_license);
        $hasCity = $info !== null && filled($info->city);
        $hasWebsite = $info !== null && filled($info->company_website);
        $hasAgreedToTerms = (bool) $user->agreed_to_terms;

        $isProfileComplete = $hasCompanyName
            && $hasBizLicense
            && $hasCity
            && $hasWebsite
            && $hasAgreedToTerms;

        if (! $isProfileComplete) {
            return $this->requiresProfileCompletion(
                user: $user,
                provider: $provider,
                required: array_values(array_filter([
                    ! $hasCompanyName ? 'company_name' : null,
                    ! $hasAgreedToTerms ? 'agreed_to_terms' : null,
                    ! $hasBizLicense ? 'business_licence' : null,
                    ! $hasCity ? 'city' : null,
                    ! $hasWebsite ? 'company_website' : null,
                ])),
                optional: ['country', 'factory_images', 'notes'],
            );
        }

        // Profile complete — check admin approval status
        return $this->evaluateManufacturerApproval($user, $request);
    }

    private function completeManufacturerProfile(Request $request, User $user, string $cacheKey): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => ['required', 'string', 'max:255'],
            'agreed_to_terms' => ['required', 'accepted'],
            'business_licence' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'city' => ['required', 'string', 'max:100'],
            'company_website' => ['required', 'url', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'factory_images' => ['sometimes', 'array', 'max:10'],
            'factory_images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return sendResponse(
                status: false,
                message: $validator->errors()->first(),
                data: $validator->errors()->toArray(),
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $validated = $validator->validated();

        // Store business licence file on the public disk
        $bizLicensePath = $request->file('business_licence')
            ->store('manufacturer/business-licenses', 'public');

        // Set approval status to pending (awaiting admin review)
        $user->forceFill([
            'agreed_to_terms' => true,
            'manufacture_status' => UserManuFactureStatus::PENDING->value,
            'manufacture_status_at' => now(),
        ])->save();

        Company::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $validated['company_name'],
                'country' => $validated['country'] ?? null,
                'bussiness_license' => $bizLicensePath, // preserving the existing typo in DB column
                'city' => $validated['city'],
                'company_website' => $validated['company_website'],
                'notes' => $validated['notes'] ?? null,
            ]
        );

        // Store optional factory photos
        if ($request->hasFile('factory_images')) {
            foreach ($request->file('factory_images') as $photo) {
                $path = $photo->store('manufacturer/factory-images', 'public');

                UserFactoryImage::create([
                    'user_id' => $user->id,
                    'path' => $path,
                    'mime_type' => $photo->getMimeType(),
                    'extension' => $photo->getClientOriginalExtension(),
                    'original_name' => $photo->getClientOriginalName(),
                ]);
            }
        }

        // Consume setup token — single use only
        Cache::forget($cacheKey);

        app(ManufacturerRegistrationNotificationService::class)->notifyAdmins(
            $user->fresh(['company', 'factoryImages']),
        );

        return sendResponse(
            status: true,
            message: __('auth.manufacturer.pending'),
            data: ['manufacture_status' => UserManuFactureStatus::PENDING->value],
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    private function evaluateManufacturerApproval(User $user, Request $request): JsonResponse
    {
        $evaluation = app(ManufacturerAccountGate::class)->evaluateLogin($user);

        if (! $evaluation['allowed']) {
            $data = null;

            if ($user->manufacture_status?->isRejected()) {
                $data = ['rejection_reason' => $evaluation['rejection_reason']];
            }

            return sendResponse(
                status: false,
                message: $evaluation['message'],
                data: $data,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        return $this->issueTokenResponse($user, 'social', $request);
    }

    /*
    |--------------------------------------------------------------------------
    | Token Issuance
    |--------------------------------------------------------------------------
    */

    private function issueTokenResponse(User $user, string $provider, Request $request): JsonResponse
    {
        if ($user->status === UserStatus::SUSPENDED) {
            return sendResponse(
                status: false,
                message: __('account.suspended'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        $deviceName = $request->string('device_name')->toString() ?: "{$provider}-oauth";

        $accessToken = app(IssuePersonalAccessTokenAction::class)->handle(
            user: $user,
            deviceName: $deviceName,
        );

        $user->loadMissing(['preferredCurrency']);
        $user = ManufacturerProfileRelations::load($user);

        app(RecordLoginHistoryAction::class)->handle($user, $request, $deviceName);

        return sendResponse(
            status: true,
            message: __('api.login_successful'),
            data: [
                'token_type' => 'Bearer',
                'access_token' => $accessToken,
                'user' => new UserResource($user),
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Profile Completion Response
    |--------------------------------------------------------------------------
    */

    /**
     * Issues a short-lived setup_token and returns a structured "profile incomplete" response.
     * The client must call POST /auth/social/complete-profile with this token + the required fields.
     */
    private function requiresProfileCompletion(
        User $user,
        string $provider,
        array $required,
        array $optional = [],
    ): JsonResponse {
        $setupToken = Str::uuid()->toString();

        Cache::put(
            self::CACHE_SETUP_PREFIX.$setupToken,
            [
                'user_id' => $user->id,
                'provider' => $provider,
                'role' => $user->role->value,
            ],
            now()->addMinutes(self::SETUP_TOKEN_TTL)
        );

        return sendResponse(
            status: true,
            message: 'Profile setup is required before you can log in.',
            data: [
                'requires_profile_completion' => true,
                'setup_token' => $setupToken,
                'setup_token_expires_minutes' => self::SETUP_TOKEN_TTL,
                'role' => $user->role->value,
                'required_fields' => $required,
                'optional_fields' => $optional,
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Guard Checks
    |--------------------------------------------------------------------------
    */

    /**
     * Returns an error JsonResponse if the account should be blocked from logging in.
     * Returns null if the account is healthy and may proceed.
     */
    private function checkAccountBlocked(User $user): ?JsonResponse
    {
        if ($user->is_permanently_deleted) {
            return sendResponse(
                status: false,
                message: __('account.permanently_deleted'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        if ($user->status === UserStatus::SUSPENDED) {
            return sendResponse(
                status: false,
                message: __('account.suspended'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        if ($user->deleted_at !== null) {
            if ($user->isWithinDeletionGracePeriod()) {
                return sendResponse(
                    status: false,
                    message: __('account.deletion_restore_login'),
                    data: [
                        'deletion_scheduled_for' => $user->deletionGraceEndsAt()->toIso8601String(),
                    ],
                    statusCode: HttpStatus::HTTP_FORBIDDEN
                );
            }

            return sendResponse(
                status: false,
                message: __('account.deletion_processing'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * JWT tokens have exactly three base64url segments separated by dots.
     * Google ID tokens are JWTs; Google access tokens are opaque (ya29.xxx).
     */
    private function looksLikeJwt(string $token): bool
    {
        $parts = explode('.', $token);

        return count($parts) === 3
            && ! empty($parts[0])
            && ! empty($parts[1])
            && ! empty($parts[2]);
    }

    private function isValidProvider(string $provider): bool
    {
        return array_key_exists($provider, self::PROVIDERS);
    }

    /**
     * Check that the raw string corresponds to any case in the UserRole enum.
     * This catches completely unknown values like "superuser" or "guest"
     * before we ever test whether the role is permitted for social login.
     */
    private function isValidEnumRole(string $role): bool
    {
        return UserRole::tryFrom($role) !== null;
    }

    /**
     * Check that a (already enum-valid) role is permitted for social login.
     * Only buyer and manufacturer are allowed — never admin.
     */
    private function isAllowedSocialRole(string $role): bool
    {
        return in_array($role, $this->allowedRoles(), true);
    }

    private function allowedRoles(): array
    {
        return [UserRole::BUYER->value, UserRole::MANUFACTURER->value];
    }

    private function invalidProviderResponse(): JsonResponse
    {
        return sendResponse(
            status: false,
            message: 'Unsupported social provider. Allowed: '.implode(', ', array_keys(self::PROVIDERS)).'.',
            data: null,
            statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /**
     * Splits "John Doe" into ["John", "Doe"].
     * Handles single-word names and null gracefully.
     *
     * @return array{0: string, 1: string|null}
     */
    private function splitName(?string $fullName): array
    {
        if (blank($fullName)) {
            return ['User', null];
        }

        $parts = explode(' ', trim($fullName), 2);

        return [$parts[0], $parts[1] ?? null];
    }
}
