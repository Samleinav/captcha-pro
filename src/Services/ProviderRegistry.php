<?php

namespace Botble\CaptchaPro\Services;

use Botble\CaptchaPro\Contracts\CaptchaProvider;
use Botble\CaptchaPro\Services\Providers\CapCaptchaProvider;
use Botble\CaptchaPro\Services\Providers\CloudflareTurnstileProvider;
use Botble\CaptchaPro\Services\Providers\GoogleRecaptchaProvider;
use Botble\CaptchaPro\Services\Providers\HCaptchaProvider;

class ProviderRegistry
{
    public const PROVIDER_GOOGLE_RECAPTCHA = 'google_recaptcha';

    public const PROVIDER_CLOUDFLARE_TURNSTILE = 'cloudflare_turnstile';

    public const PROVIDER_HCAPTCHA = 'hcaptcha';

    public const PROVIDER_CAPTCHA = 'cap_captcha';

    protected ?array $providers = null;

    /**
     * @return array<string, CaptchaProvider>
     */
    public function providers(): array
    {
        return $this->providers ??= [
            self::PROVIDER_GOOGLE_RECAPTCHA => new GoogleRecaptchaProvider,
            self::PROVIDER_CLOUDFLARE_TURNSTILE => new CloudflareTurnstileProvider,
            self::PROVIDER_HCAPTCHA => new HCaptchaProvider,
            self::PROVIDER_CAPTCHA => new CapCaptchaProvider,
        ];
    }

    public function active(): CaptchaProvider
    {
        $providers = $this->providers();
        $provider = $this->currentProvider();

        return $providers[$provider] ?? $providers[self::PROVIDER_GOOGLE_RECAPTCHA];
    }

    public function currentProvider(?string $provider = null): string
    {
        $provider ??= setting('captcha_pro_provider', self::PROVIDER_GOOGLE_RECAPTCHA);

        return $provider === 'custom' ? self::PROVIDER_CAPTCHA : $provider;
    }

    public function choices(): array
    {
        return collect($this->providers())
            ->mapWithKeys(fn (CaptchaProvider $provider) => [$provider->key() => $provider->label()])
            ->all();
    }
}
