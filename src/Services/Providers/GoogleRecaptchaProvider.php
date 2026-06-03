<?php

namespace Botble\CaptchaPro\Services\Providers;

use Botble\CaptchaPro\Contracts\CaptchaProvider;
use Botble\CaptchaPro\Services\CaptchaManager;
use Botble\CaptchaPro\Services\ProviderRegistry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GoogleRecaptchaProvider implements CaptchaProvider
{
    protected bool $rendered = false;

    public function key(): string
    {
        return ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA;
    }

    public function label(): string
    {
        return trans('plugins/captcha-pro::captcha-pro.providers.google_recaptcha');
    }

    public function inputName(): string
    {
        return CaptchaManager::RECAPTCHA_INPUT_NAME;
    }

    public function isEnabled(): bool
    {
        return (bool) setting('captcha_site_key') && (bool) setting('captcha_secret');
    }

    public function display(array $attributes = [], array $options = []): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return setting('captcha_type') === 'v3'
            ? $this->displayV3($attributes, $options)
            : $this->displayV2();
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
            ->post(CaptchaManager::RECAPTCHA_VERIFY_API_URL, [
                'secret' => setting('captcha_secret'),
                'response' => $response,
                'remoteip' => $clientIp,
            ]);

        $data = $response->json();

        if (! is_array($data) || ! ($data['success'] ?? false)) {
            return false;
        }

        if (setting('captcha_type') !== 'v3') {
            return true;
        }

        if ($options === []) {
            $options = ['form', (float) setting('recaptcha_score', 0.6)];
        }

        $action = $options[0] ?? null;
        $minScore = isset($options[1]) ? (float) $options[1] : 0.6;

        if ($action && (! isset($data['action']) || $action !== $data['action'])) {
            return false;
        }

        $score = $data['score'] ?? false;

        return $score && $score >= $minScore;
    }

    protected function displayV2(): ?string
    {
        $name = 'captcha_'.md5(uniqid((string) rand(), true));
        $footerContent = $this->v2FooterRender($name);

        add_filter(['theme-front-footer', 'ecommerce_checkout_footer'], fn (?string $html): string => $html.$footerContent, 99);
        add_filter(BASE_FILTER_FOOTER_LAYOUT_TEMPLATE, fn (?string $html): string => $html.$footerContent, 99);

        $this->rendered = true;

        $captchaContent = view('plugins/captcha-pro::providers.recaptcha-v2', [
            'name' => $name,
            'siteKey' => setting('captcha_site_key'),
        ])->render();

        if (request()->expectsJson()) {
            $captchaContent .= $footerContent;
        }

        return $captchaContent;
    }

    protected function displayV3(array $attributes, array $options): ?string
    {
        $name = Arr::get($options, 'name', CaptchaManager::RECAPTCHA_INPUT_NAME);
        $uniqueId = uniqid($name.'-');
        $headContent = view('plugins/captcha-pro::providers.recaptcha-v3-head')->render();
        $footerContent = $this->v3FooterRender($uniqueId, $attributes);

        add_filter(['theme-front-footer', 'ecommerce_checkout_footer'], fn (?string $html): string => $html.$headContent.$footerContent, 99);
        add_filter(BASE_FILTER_FOOTER_LAYOUT_TEMPLATE, fn (?string $html): string => $html.$headContent.$footerContent, 99);

        $captchaContent = view('plugins/captcha-pro::providers.recaptcha-v3', compact('name', 'uniqueId'))->render();

        $this->rendered = true;

        if (request()->expectsJson()) {
            $captchaContent .= $footerContent;
        }

        return $captchaContent;
    }

    protected function v2FooterRender(string $name): string
    {
        $isRendered = $this->rendered;

        $url = CaptchaManager::RECAPTCHA_CLIENT_API_URL.'?'.http_build_query([
            'onload' => 'onloadCallback',
            'render' => 'explicit',
            'hl' => app()->getLocale(),
        ]);

        return view('plugins/captcha-pro::providers.recaptcha-v2-script', compact('url', 'isRendered', 'name'))->render();
    }

    protected function v3FooterRender(string $uniqueId, array $attributes): string
    {
        $action = Arr::get($attributes, 'action', 'form');
        $isRendered = $this->rendered;

        $url = CaptchaManager::RECAPTCHA_CLIENT_API_URL.'?'.http_build_query([
            'onload' => 'onloadCallback',
            'render' => setting('captcha_site_key'),
            'hl' => app()->getLocale(),
        ]);

        return view('plugins/captcha-pro::providers.recaptcha-v3-script', [
            'siteKey' => setting('captcha_site_key'),
            'id' => $uniqueId,
            'action' => $action,
            'url' => $url,
            'isRendered' => $isRendered,
        ])->render();
    }
}
