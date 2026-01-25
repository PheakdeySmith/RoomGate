<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthIdentity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'telegram'];

    public function redirect(string $provider)
    {
        $this->ensureProvider($provider);

        if ($provider === 'telegram') {
            $target = $this->normalizeTelegramRedirectTarget();
            if ($target) {
                return redirect()->to($target);
            }

            $botToken = config('services.telegram.client_secret');
            $botId = $botToken ? explode(':', $botToken, 2)[0] : null;

            return view('auth.telegram-redirect', [
                'botId' => $botId,
                'botUsername' => config('services.telegram.bot'),
                'redirectUrl' => config('services.telegram.redirect'),
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    private function normalizeTelegramRedirectTarget(): ?string
    {
        $appUrl = config('app.url');
        if (!$appUrl) {
            return null;
        }

        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $appScheme = parse_url($appUrl, PHP_URL_SCHEME);
        $requestHost = request()->getHost();

        if (!$appHost || !$requestHost) {
            return null;
        }

        if ($requestHost !== $appHost && $appScheme === 'https') {
            return rtrim($appUrl, '/').'/auth/telegram/redirect';
        }

        return null;
    }

    public function callback(string $provider, Request $request)
    {
        $this->ensureProvider($provider);

        if ($provider === 'telegram') {
            return $this->handleTelegramCallback($request);
        }

        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (\InvalidArgumentException $exception) {
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Telegram sign-in was cancelled or invalid. Please try again.']);
        }
        $payload = $this->payloadFromUser($oauthUser);
        return $this->finalizeOauthLogin(
            $provider,
            (string) $oauthUser->getId(),
            $oauthUser->getEmail() ?: data_get($oauthUser->user, 'email'),
            $oauthUser->getName() ?: $oauthUser->getNickname() ?: 'User',
            $payload,
            $request
        );
    }

    public function showEmailForm(string $provider, Request $request)
    {
        $this->ensureProvider($provider);

        if (!$request->session()->has($this->pendingSessionKey($provider))) {
            return redirect()->route('login');
        }

        return view('auth.social-email', ['provider' => $provider]);
    }

    public function storeEmail(string $provider, Request $request)
    {
        $this->ensureProvider($provider);

        $sessionKey = $this->pendingSessionKey($provider);
        $pending = $request->session()->get($sessionKey);

        if (!$pending) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $user = $this->resolveUser(
            $provider,
            $pending['provider_user_id'],
            $validated['email'],
            $pending['name'],
            $pending
        );

        $request->session()->forget($sessionKey);

        Auth::login($user, true);

        return redirect()->intended('/core/crm-dashboard');
    }

    private function resolveUser(
        string $provider,
        string $providerUserId,
        string $email,
        string $name,
        array $payload
    ): User {
        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Str::random(32),
            ]);
        }

        if ($this->providerEmailVerified($provider, $payload) && !$user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        AuthIdentity::firstOrCreate(
            [
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
            ],
            [
                'user_id' => $user->id,
                'email' => $email,
                'access_token' => data_get($payload, 'token'),
                'refresh_token' => data_get($payload, 'refreshToken'),
                'expires_at' => data_get($payload, 'expiresIn')
                    ? now()->addSeconds((int) data_get($payload, 'expiresIn'))
                    : null,
                'meta_json' => [
                    'name' => $name,
                    'nickname' => data_get($payload, 'nickname'),
                ],
                'raw_profile_json' => data_get($payload, 'raw_profile'),
            ]
        );

        return $user;
    }

    private function providerEmailVerified(string $provider, array $payload): bool
    {
        if ($provider !== 'google') {
            return false;
        }

        return (bool) (data_get($payload, 'raw_profile.email_verified')
            ?? data_get($payload, 'raw_profile.verified_email'));
    }

    private function ensureProvider(string $provider): void
    {
        if (!in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }
    }

    private function storePendingOauth(
        Request $request,
        string $provider,
        string $providerUserId,
        string $name,
        array $payload
    ): void {
        $request->session()->put($this->pendingSessionKey($provider), [
            'provider_user_id' => $providerUserId,
            'name' => $name,
            'token' => data_get($payload, 'token'),
            'refreshToken' => data_get($payload, 'refreshToken'),
            'expiresIn' => data_get($payload, 'expiresIn'),
            'nickname' => data_get($payload, 'nickname'),
            'raw_profile' => data_get($payload, 'raw_profile'),
        ]);
    }

    private function payloadFromUser($oauthUser): array
    {
        return [
            'token' => $oauthUser->token ?? null,
            'refreshToken' => $oauthUser->refreshToken ?? null,
            'expiresIn' => $oauthUser->expiresIn ?? null,
            'nickname' => method_exists($oauthUser, 'getNickname') ? $oauthUser->getNickname() : null,
            'raw_profile' => $oauthUser->user ?? null,
        ];
    }

    private function handleTelegramCallback(Request $request)
    {
        $data = $request->all();
        \Log::info('[telegram] callback payload', ['payload' => $data]);

        if (!isset($data['id'], $data['auth_date'], $data['hash'])) {
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Telegram sign-in was cancelled or invalid. Please try again.']);
        }

        $token = (string) config('services.telegram.client_secret');
        if ($token === '') {
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Telegram configuration is missing.']);
        }

        $dataCheck = collect($data)
            ->except('hash')
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->sort()
            ->join("\n");

        $secretKey = hash('sha256', $token, true);
        $hash = hash_hmac('sha256', $dataCheck, $secretKey);

        if (!hash_equals($hash, (string) $data['hash'])) {
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Telegram sign-in could not be verified. Please try again.']);
        }

        $name = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        if ($name === '') {
            $name = $data['username'] ?? 'User';
        }

        $payload = [
            'token' => null,
            'refreshToken' => null,
            'expiresIn' => null,
            'nickname' => $data['username'] ?? null,
            'raw_profile' => $data,
        ];

        return $this->finalizeOauthLogin(
            'telegram',
            (string) $data['id'],
            $data['email'] ?? null,
            $name,
            $payload,
            $request
        );
    }

    private function finalizeOauthLogin(
        string $provider,
        string $providerUserId,
        ?string $email,
        string $name,
        array $payload,
        Request $request
    ) {
        if ($providerUserId === '') {
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Unable to authenticate with provider.']);
        }

        $identity = AuthIdentity::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($identity) {
            Auth::login($identity->user, true);

            return $this->redirectAfterLogin($identity->user);
        }

        if (!$email) {
            $this->storePendingOauth($request, $provider, $providerUserId, $name, $payload);

            return redirect()->route('oauth.email', $provider);
        }

        $user = $this->resolveUser($provider, $providerUserId, $email, $name, $payload);

        Auth::login($user, true);

        return $this->redirectAfterLogin($user);
    }

    private function pendingSessionKey(string $provider): string
    {
        return "oauth_pending.{$provider}";
    }

    private function redirectAfterLogin(User $user)
    {
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['platform_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->tenants()->exists()) {
            return redirect()->intended('/core/crm-dashboard');
        }

        return redirect()->route('core.onboarding');
    }
}
