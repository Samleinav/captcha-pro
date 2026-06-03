<?php

namespace Botble\CaptchaPro\Services\Providers;

use Botble\CaptchaPro\Contracts\CaptchaProvider;
use Botble\CaptchaPro\Services\ProviderRegistry;
use Illuminate\Support\Facades\Http;

class CloudflareTurnstileProvider implements CaptchaProvider
{
    public const CLIENT_API_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    public const VERIFY_API_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public const INPUT_NAME = 'cf-turnstile-response';

    protected bool $rendered = false;

    public function key(): string
    {
        return ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE;
    }

    public function label(): string
    {
        return trans('plugins/captcha-pro::captcha-pro.providers.cloudflare_turnstile');
    }

    public function inputName(): string
    {
        return self::INPUT_NAME;
    }

    public function isEnabled(): bool
    {
        return (bool) setting('captcha_pro_turnstile_site_key') && (bool) setting('captcha_pro_turnstile_secret');
    }

    public function display(array $attributes = [], array $options = []): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $footerContent = $this->footerRender();

        add_filter(['theme-front-footer', 'ecommerce_checkout_footer'], fn (?string $html): string => $html.$footerContent, 99);
        add_filter(BASE_FILTER_FOOTER_LAYOUT_TEMPLATE, fn (?string $html): string => $html.$footerContent, 99);

        $this->rendered = true;

        $captchaContent = view('plugins/captcha-pro::providers.turnstile', [
            'siteKey' => setting('captcha_pro_turnstile_site_key'),
            'theme' => setting('captcha_pro_turnstile_theme', 'auto') ?: 'auto',
            'size' => setting('captcha_pro_turnstile_size', 'normal') ?: 'normal',
        ])->render();

        if (request()->expectsJson()) {
            $captchaContent .= $footerContent;
        }

        return $captchaContent;
    }

    public function verify(string $response, ?string $clientIp = null, array $options = []): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if ($response === '') {
            return false;
        }

        $response = Http::asForm()
            ->withoutVerifying()
            ->post(self::VERIFY_API_URL, [
                'secret' => setting('captcha_pro_turnstile_secret'),
                'response' => $response,
                'remoteip' => $clientIp,
            ]);

        return (bool) $response->json('success');
    }

    protected function footerRender(): string
    {
        return view('plugins/captcha-pro::providers.turnstile-script', [
            'url' => self::CLIENT_API_URL,
            'isRendered' => $this->rendered,
        ])->render();
    }
}
