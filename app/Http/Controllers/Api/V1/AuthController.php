<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\V1\Auth\IssuePersonalAccessTokenAction;
use App\Actions\Api\V1\Auth\RecordLoginHistoryAction;
use App\Actions\Api\V1\Auth\RegisterUserAction;
use App\Actions\Api\V1\Auth\RevokePassportTokensAction;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\ResendEmailVerificationRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Requests\Api\V1\TwoFactor\TwoFactorChallengeRequest;
use App\Http\Requests\Api\V1\VerifyEmailRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Enums\MailTemplate;
use App\Exceptions\Auth\EmailVerificationException;
use App\Services\Auth\EmailVerificationService;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;
use App\Models\User;
use App\Services\ManufacturerAccountGate;
use App\Services\Platform\PlatformSettingsService;
use App\Support\Manufacturer\ManufacturerProfileRelations;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AuthController extends Controller
{

    public function __construct(private readonly PlatformSettingsService $platformSettings)
    {
    }
    public function register(Request $request, RegisterUserAction $registerUserAction): JsonResponse
    {
        $result = $registerUserAction->handle($request);

        if ($result['manufacturer_pending'] ?? false) {
            $verificationData = null;

            if ($this->platformSettings->requiresEmailVerification()) {
                $verificationData = [
                    'verification_token' => $result['verification_token'],
                    'code_expiry_time' => $result['code_expiry_time'],
                ];
            }

            return sendResponse(
                status: true,
                message: __('auth.manufacturer.pending'),
                data: $verificationData,
                statusCode: HttpStatus::HTTP_CREATED,
                additional: [
                    'manufacture_status' => UserManuFactureStatus::PENDING->value,
                ]
            );
        }

        $user = ManufacturerProfileRelations::load(
            $result['user']->loadMissing(['preferredCurrency'])
        );

        if ($this->platformSettings->requiresEmailVerification()) {
            return sendResponse(
                status: true,
                message: __('api.email_verification_required'),
                data: [
                    'verification_token' => $result['verification_token'],
                    'code_expiry_time' => $result['code_expiry_time'],
                ],
                statusCode: HttpStatus::HTTP_CREATED,
            );
        }


        
        return sendResponse(
            status: true,
            message: __('api.registration_successful'),
            data: [
                'token_type' => $result['token_type'],
                'access_token' => $result['access_token'],
                'user' => new UserResource($user),
            ],
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function verifyEmail(
        VerifyEmailRequest $request,
        EmailVerificationService $emailVerificationService,
        IssuePersonalAccessTokenAction $issuePersonalAccessTokenAction,
    ): JsonResponse {
        try {
            $validated = $request->validated();
            $user = $emailVerificationService->verify(
                $validated['verification_token'],
                $validated['otp'],
            );
        } catch (EmailVerificationException $exception) {
            return sendResponse(
                status: false,
                message: $exception->getMessage(),
                data: $exception->data,
                statusCode: $exception->httpStatus,
            );
        }

        $user = ManufacturerProfileRelations::load(
            $user->loadMissing(['company', 'factoryImages', 'preferredCurrency'])
        );

        $accessToken = $issuePersonalAccessTokenAction->handle($user, $validated['device_name'] ?? null);

        return sendResponse(
            status: true,
            message: __('api.email_verification_successful'),
            data: [
                'token_type' => 'Bearer',
                'access_token' => $accessToken,
                'user' => new UserResource($user),
            ],
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function resendEmailVerification(
        ResendEmailVerificationRequest $request,
        EmailVerificationService $emailVerificationService,
    ): JsonResponse {
        try {
            $result = $emailVerificationService->resend($request->validated('verification_token'));
        } catch (EmailVerificationException $exception) {
            return sendResponse(
                status: false,
                message: $exception->getMessage(),
                data: $exception->data,
                statusCode: $exception->httpStatus,
            );
        }

        return sendResponse(
            status: true,
            message: __('account.email_verification_sent'),
            data: $result,
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $usernameField = config('fortify.username', 'email');
        $username = $request->string($usernameField)->toString();

        $user = User::query()->where($usernameField, $username)->where('role', $request->string('role')->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return sendResponse(status: false, message: __('auth.invalid_credentials'), statusCode: HttpStatus::HTTP_UNAUTHORIZED);
        }

        if ($user->is_permanently_deleted) {
            return sendResponse(
                status: false,
                message: __('account.permanently_deleted'),
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
                        // normall human readable date and time like isoString
                        // 'deletion_scheduled_for' => Carbon::parse($user->deletionGraceEndsAt())->format('Y-m-d H:i:s'),
                        'deletion_scheduled_for' => Carbon::parse($user->deletionGraceEndsAt())->toIso8601String(),
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

        if ($user->status === UserStatus::SUSPENDED) {
            return sendResponse(
                status: false,
                message: __('account.suspended'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        $evaluation = app(ManufacturerAccountGate::class)->evaluateLogin($user);

        if (! $evaluation['allowed']) {
            $data = null;
            if ($user->role->isManufacturer() && $user->manufacture_status?->isRejected()) {
                $data = [
                    'rejection_reason' => $evaluation['rejection_reason'],
                ];
            }

            return sendResponse(
                status: false,
                message: $evaluation['message'],
                data: $data,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $plainToken = (string) Str::uuid();
            Cache::put('api_2fa_login:'.$plainToken, [
                'user_id' => $user->id,
                'device_name' => $request->string('device_name')->toString(),
            ], now()->addMinutes(config('account.two_factor_login_token_ttl_minutes')));

            return sendResponse(
                status: true,
                message: __('account.two_factor.required_when_login'),
                data: [
                    'two_factor' => true,
                    'two_factor_token' => $plainToken,
                ],
                statusCode: HttpStatus::HTTP_OK
            );
        }

        return $this->issueLoginTokenResponse($request, $user);
    }

    public function twoFactorChallenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $token = $request->string('two_factor_token')->toString();
        $cacheKey = 'api_2fa_login:'.$token;
        $payload = Cache::get($cacheKey);

        if (! is_array($payload) || ! isset($payload['user_id'])) {
            return sendResponse(
                status: false,
                message: __('account.two_factor.invalid_challenge'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user = User::query()->find($payload['user_id']);

        if (! $user || ! $user->hasEnabledTwoFactorAuthentication()) {
            Cache::forget($cacheKey);

            return sendResponse(
                status: false,
                message: __('account.two_factor.invalid_challenge'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($user->status === UserStatus::SUSPENDED) {
            Cache::forget($cacheKey);

            return sendResponse(
                status: false,
                message: __('account.suspended'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        $provider = app(TwoFactorAuthenticationProvider::class);

        if ($request->filled('recovery_code')) {
            $recovery = $request->string('recovery_code')->toString();
            $codes = $user->recoveryCodes();
            $match = collect($codes)->first(fn (string $c): bool => hash_equals($c, $recovery));

            if ($match === null) {
                return sendResponse(
                    status: false,
                    message: __('account.two_factor.invalid_code'),
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $user->replaceRecoveryCode($match);
        } else {
            $code = $request->string('code')->toString();
            $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

            if (! $provider->verify($secret, $code)) {
                return sendResponse(
                    status: false,
                    message: __('account.two_factor.invalid_code'),
                    data: null,
                    statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        Cache::forget($cacheKey);

        $deviceName = $request->has('device_name')
            ? $request->string('device_name')->toString()
            : (string) ($payload['device_name'] ?? '');

        return $this->issueLoginTokenResponse($request, $user, $deviceName);
    }

    protected function issueLoginTokenResponse(Request $request, User $user, ?string $deviceName = null): JsonResponse
    {
        $resolvedDevice = $deviceName ?? $request->string('device_name')->toString();

        $accessToken = app(IssuePersonalAccessTokenAction::class)->handle(
            user: $user,
            deviceName: $resolvedDevice !== '' ? $resolvedDevice : null
        );
        $user->loadMissing(['preferredCurrency']);
        $user = ManufacturerProfileRelations::load($user);

        app(RecordLoginHistoryAction::class)->handle($user, $request, $resolvedDevice !== '' ? $resolvedDevice : null);

        return sendResponse(status: true, message: __('api.login_successful'), data: [
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
            'user' => new UserResource($user),

        ], statusCode: HttpStatus::HTTP_OK);
    }

    public function logout(Request $request, RevokePassportTokensAction $revokePassportTokensAction): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return sendResponse(
                status: false,
                message: __('api.unauthenticated'),
                data: null,
                statusCode: HttpStatus::HTTP_UNAUTHORIZED
            );
        }

        $revokePassportTokensAction->handle($user);

        return sendResponse(status: true, message: __('api.logout_successful'), data: null, statusCode: HttpStatus::HTTP_OK);
    }

    public function forgotPassword(ForgotPasswordRequest $request, MailingService $mailingService): JsonResponse
    {
        $email = $request->validated('email');
        $genericMessage = __('api.password_reset_otp_sent_generic');

        $user = User::query()->where('email', $email)->first();

        if (! $user || $user->is_permanently_deleted) {
            return sendResponse(
                status: true,
                message: $genericMessage,
                data: null,
                statusCode: HttpStatus::HTTP_OK
            );
        }

        $cooldownKey = $this->passwordResetOtpCooldownCacheKey($user->id);

        if (Cache::has($cooldownKey)) {
            $availableAtTs = (int) Cache::get($cooldownKey);
            $retryAfterSeconds = max(0, $availableAtTs - now()->timestamp);
            $availableAt = Carbon::createFromTimestamp($availableAtTs);

            return sendResponse(
                status: false,
                message: __('account.password_reset_otp_resend_wait'),
                data: [
                    'retry_after_seconds' => $retryAfterSeconds,
                    'available_at' => $availableAt->toIso8601String(),
                ],
                statusCode: HttpStatus::HTTP_TOO_MANY_REQUESTS
            );
        }

        $otp = (string) random_int(100000, 999999);
        $cacheKey = $this->passwordResetOtpCacheKey($user->id);
        Cache::put($cacheKey, Hash::make($otp), now()->addMinutes(config('account.password_reset_otp_ttl_minutes')));

        Log::info('Sending password reset OTP to email: '.$user->email);
        $mailingService->send(
            $user->email,
            MailTemplate::PasswordResetOtp,
            MailNotificationHelper::otpMailPayload(
                $otp,
                'mail.password_reset_otp',
                __('mail.password_reset_otp.expires', ['minutes' => config('account.password_reset_otp_ttl_minutes')]),
            ),
        );

        $resendSeconds = config('account.password_reset_otp_resend_seconds');
        $availableAt = now()->addSeconds($resendSeconds);
        Cache::put($cooldownKey, $availableAt->timestamp, $resendSeconds);

        return sendResponse(
            status: true,
            message: $genericMessage,
            data: null,
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user) {
            return sendResponse(
                status: false,
                message: __('account.password_reset_invalid_otp'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $cacheKey = $this->passwordResetOtpCacheKey($user->id);
        $hash = Cache::get($cacheKey);

        if (! is_string($hash) || ! Hash::check($validated['otp'], $hash)) {
            return sendResponse(
                status: false,
                message: __('account.password_reset_invalid_otp'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        Cache::forget($cacheKey);
        Cache::forget($this->passwordResetOtpCooldownCacheKey($user->id));

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        return sendResponse(
            status: true,
            message: __('passwords.reset'),
            data: null,
            statusCode: HttpStatus::HTTP_OK
        );
    }

    protected function passwordResetOtpCacheKey(int $userId): string
    {
        return 'password_reset_otp:'.$userId;
    }

    protected function passwordResetOtpCooldownCacheKey(int $userId): string
    {
        return 'password_reset_otp_cooldown:'.$userId;
    }
}
