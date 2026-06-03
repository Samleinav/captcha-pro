<?php

namespace Botble\CaptchaPro\Services\Providers;

use Botble\CaptchaPro\Contracts\CaptchaProvider;
use Botble\CaptchaPro\Services\ProviderRegistry;
use Illuminate\Support\Facades\Http;

class CapCaptchaProvider implements CaptchaProvider
{
    public const CLIENT_API_URL = 'https://cdn.jsdelivr.net/npm/cap-widget';

    public const INPUT_NAME = 'cap-token';

    protected bool $rendered = false;

    public function key(): string
    {
        return ProviderRegistry::PROVIDER_CAPTCHA;
    }

    public function label(): string
    {
        return trans('plugins/captcha-pro::captcha-pro.providers.cap_captcha');
    }

    public function inputName(): string
    {
        return self::INPUT_NAME;
    }

    public function isEnabled(): bool
    {
        return (bool) setting('captcha_pro_cap_site_url')
            && (bool) setting('captcha_pro_cap_site_key')
            && (bool) setting('captcha_pro_cap_secret');
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

        $captchaContent = view('plugins/captcha-pro::providers.cap-captcha', [
            'endpoint' => $this->endpoint(),
            'inputName' => self::INPUT_NAME,
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

        $response = Http::withoutVerifying()
            ->post($this->siteverifyEndpoint(), [
                'secret' => setting('captcha_pro_cap_secret'),
                'response' => $response,
            ]);

        return (bool) $response->json('success');
    }

    protected function endpoint(): string
    {
        return sprintf(
            '%s/%s/',
            rtrim((string) setting('captcha_pro_cap_site_url'), '/'),
            trim((string) setting('captcha_pro_cap_site_key'), '/')
        );
    }

    protected function siteverifyEndpoint(): string
    {
        return $this->endpoint().'siteverify';
    }

    protected function footerRender(): string
    {
        return view('plugins/captcha-pro::providers.cap-captcha-script', [
            'url' => self::CLIENT_API_URL,
            'isRendered' => $this->rendered,
        ])->render();
    }
}
