<?php

namespace Botble\CaptchaPro\Http\Requests\Settings;

use Botble\Base\Rules\OnOffRule;
use Botble\CaptchaPro\Services\ProviderRegistry;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CaptchaProSettingRequest extends Request
{
    public function rules(): array
    {
        $onOffRule = new OnOffRule;
        $registry = new ProviderRegistry;
        $provider = $registry->currentProvider($this->input('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA));
        $enabled = $this->input('enable_captcha') === '1' || $this->input('enable_captcha') === 1;
        $googleProviderEnabled = $enabled && $provider === ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA;
        $turnstileProviderEnabled = $enabled && $provider === ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE;
        $hCaptchaProviderEnabled = $enabled && $provider === ProviderRegistry::PROVIDER_HCAPTCHA;
        $capCaptchaProviderEnabled = $enabled && $provider === ProviderRegistry::PROVIDER_CAPTCHA;

        $rules = [
            'enable_captcha' => $onOffRule,
            'captcha_pro_provider' => ['nullable', Rule::in(array_keys($registry->choices())), Rule::requiredIf($enabled)],
            'captcha_type' => ['nullable', 'in:v2,v3', Rule::requiredIf($googleProviderEnabled)],
            'captcha_hide_badge' => $onOffRule,
            'captcha_show_disclaimer' => $onOffRule,
            'captcha_site_key' => ['nullable', 'string', 'max:120', Rule::requiredIf($googleProviderEnabled)],
            'captcha_secret' => ['nullable', 'string', 'max:120', Rule::requiredIf($googleProviderEnabled)],
            'recaptcha_score' => ['nullable', Rule::in(app('captcha')->scores()), Rule::requiredIf($googleProviderEnabled && $this->input('captcha_type') === 'v3')],
            'captcha_pro_turnstile_site_key' => ['nullable', 'string', 'max:255', Rule::requiredIf($turnstileProviderEnabled)],
            'captcha_pro_turnstile_secret' => ['nullable', 'string', 'max:255', Rule::requiredIf($turnstileProviderEnabled)],
            'captcha_pro_turnstile_theme' => ['nullable', Rule::in(['auto', 'light', 'dark'])],
            'captcha_pro_turnstile_size' => ['nullable', Rule::in(['normal', 'compact', 'flexible'])],
            'captcha_pro_hcaptcha_site_key' => ['nullable', 'string', 'max:255', Rule::requiredIf($hCaptchaProviderEnabled)],
            'captcha_pro_hcaptcha_secret' => ['nullable', 'string', 'max:255', Rule::requiredIf($hCaptchaProviderEnabled)],
            'captcha_pro_hcaptcha_theme' => ['nullable', Rule::in(['light', 'dark'])],
            'captcha_pro_hcaptcha_size' => ['nullable', Rule::in(['normal', 'compact'])],
            'captcha_pro_cap_site_url' => ['nullable', 'url', 'max:255', Rule::requiredIf($capCaptchaProviderEnabled)],
            'captcha_pro_cap_site_key' => ['nullable', 'string', 'max:255', Rule::requiredIf($capCaptchaProviderEnabled)],
            'captcha_pro_cap_secret' => ['nullable', 'string', 'max:255', Rule::requiredIf($capCaptchaProviderEnabled)],
            'enable_math_captcha' => $onOffRule,
            ...$this->formSelectorRules('enable_math_captcha'),
            ...$this->formSelectorRules('enable_recaptcha'),
        ];

        return apply_filters('captcha_pro_settings_validation_rules', $rules);
    }

    protected function formSelectorRules(string $key): array
    {
        $rules = [];

        $captcha = app('captcha');

        foreach (array_keys($captcha->getFormsSupport()) as $form) {
            $rules[$captcha->formSettingKey($form, $key)] = new OnOffRule;
        }

        return $rules;
    }
}
