<?php

namespace Botble\CaptchaPro\Services;

use Botble\Theme\FormFrontManager;
use Illuminate\Support\Str;

class CaptchaManager
{
    public const RECAPTCHA_CLIENT_API_URL = 'https://www.google.com/recaptcha/api.js';

    public const RECAPTCHA_VERIFY_API_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public const RECAPTCHA_INPUT_NAME = 'g-recaptcha-response';

    protected array $forms = [];

    protected array $formRequests = [];

    public function __construct(protected ProviderRegistry $registry) {}

    public function verify(string $response, ?string $clientIp = null, array $options = []): bool
    {
        return $this->registry->active()->verify($response, $clientIp, $options);
    }

    public function display(array $attributes = [], array $options = []): ?string
    {
        return $this->registry->active()->display($attributes, $options);
    }

    public function rules(): array
    {
        if (! $this->reCaptchaEnabled()) {
            return [];
        }

        return [$this->registry->active()->inputName() => ['required', 'captcha']];
    }

    public function isEnabled(): bool
    {
        return $this->reCaptchaEnabled();
    }

    public function reCaptchaEnabled(): bool
    {
        return (bool) setting('enable_captcha') && $this->registry->active()->isEnabled();
    }

    public function captchaType(): string
    {
        return $this->reCaptchaType();
    }

    public function reCaptchaType(): string
    {
        if (setting('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA) === ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA) {
            return setting('captcha_type', 'v2') ?: 'v2';
        }

        return 'v2';
    }

    public function attributes(): array
    {
        return [
            'captcha' => trans('plugins/captcha-pro::captcha-pro.captcha'),
            'math-captcha' => trans('plugins/captcha-pro::captcha-pro.math_captcha'),
            $this->registry->active()->inputName() => trans('plugins/captcha-pro::captcha-pro.captcha'),
        ];
    }

    public function mathCaptchaEnabled(): bool
    {
        return (bool) setting('enable_math_captcha');
    }

    public function mathCaptchaRules(): array
    {
        return ['math-captcha' => ['required', 'string', 'math_captcha']];
    }

    public function scores(): array
    {
        $scores = [];

        foreach (range(1, 9) as $i) {
            $key = $i / 10;
            $scores[(string) $key] = (string) $key;
        }

        return $scores;
    }

    public function registerFormSupport(string $form, string $request, string $title): static
    {
        $this->forms[$form] = $title;
        $this->formRequests[$form] = $request;

        return $this;
    }

    public function getFormsSupport(): array
    {
        if (class_exists(FormFrontManager::class)) {
            foreach (FormFrontManager::forms() as $form) {
                $this->registerFormSupport($form, FormFrontManager::formRequestOf($form), $form::formTitle());
            }
        }

        return $this->forms;
    }

    public function formByRequest(string $request): ?string
    {
        $form = array_search($request, $this->formRequests, true);

        return $form === false ? null : $form;
    }

    public function formSettingKey(string $form, string $key): string
    {
        return $key.'_'.str_replace('\\', '', Str::snake($form));
    }

    public function formSetting(string $form, string $key, mixed $default = false): mixed
    {
        return setting($this->formSettingKey($form, $key), $default);
    }
}
