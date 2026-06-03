<?php

namespace Botble\CaptchaPro\Providers;

use Botble\ACL\Forms\Auth\ForgotPasswordForm;
use Botble\ACL\Forms\Auth\LoginForm;
use Botble\ACL\Forms\Auth\ResetPasswordForm;
use Botble\ACL\Http\Requests\ForgotPasswordRequest;
use Botble\ACL\Http\Requests\LoginRequest;
use Botble\ACL\Http\Requests\ResetPasswordRequest;
use Botble\Base\Facades\PanelSectionManager;
use Botble\Base\Forms\FieldOptions\RadioFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\RadioField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\PanelSections\PanelSectionItem;
use Botble\Base\Rules\OnOffRule;
use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Captcha\Forms\CaptchaSettingForm;
use Botble\CaptchaPro\Facades\Captcha as CaptchaFacade;
use Botble\CaptchaPro\Forms\Fields\MathCaptchaField;
use Botble\CaptchaPro\Forms\Fields\ReCaptchaField;
use Botble\CaptchaPro\Services\CaptchaManager;
use Botble\CaptchaPro\Services\MathCaptcha;
use Botble\CaptchaPro\Services\ProviderRegistry;
use Botble\Setting\PanelSections\SettingOthersPanelSection;
use Botble\Support\Http\Requests\Request;
use Botble\Theme\FormFront;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Routing\Events\Routing;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CaptchaProServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->forgetInstance('captcha');
        $this->app->singleton('captcha', fn () => new CaptchaManager(new ProviderRegistry));

        if (! $this->app->bound('math-captcha')) {
            $this->app->singleton('math-captcha', fn ($app) => new MathCaptcha($app['session']));
        }

        Facade::clearResolvedInstance('captcha');
        AliasLoader::getInstance()->alias('Captcha', CaptchaFacade::class);
    }

    public function boot(): void
    {
        $this
            ->setNamespace('plugins/captcha-pro')
            ->loadAndPublishConfigurations(['general'])
            ->loadAndPublishConfigurations(['permissions'])
            ->loadRoutes()
            ->loadAndPublishViews()
            ->loadAndPublishTranslations();

        $this->registerBaseFormSupport();
        $this->bootValidator();

        if ($this->hasBaseCaptchaPlugin()) {
            $this->extendBaseCaptchaSettings();

            return;
        }

        $this->registerStandaloneSettingsPanel();
        $this->registerStandaloneFormHooks();
    }

    protected function hasBaseCaptchaPlugin(): bool
    {
        return function_exists('is_plugin_active') && is_plugin_active('captcha');
    }

    protected function registerBaseFormSupport(): void
    {
        if (class_exists(LoginForm::class) && class_exists(LoginRequest::class)) {
            app('captcha')->registerFormSupport(LoginForm::class, LoginRequest::class, trans('plugins/captcha-pro::captcha-pro.admin_login_form'));
        }

        if (class_exists(ForgotPasswordForm::class) && class_exists(ForgotPasswordRequest::class)) {
            app('captcha')->registerFormSupport(ForgotPasswordForm::class, ForgotPasswordRequest::class, trans('plugins/captcha-pro::captcha-pro.admin_forgot_password_form'));
        }

        if (class_exists(ResetPasswordForm::class) && class_exists(ResetPasswordRequest::class)) {
            app('captcha')->registerFormSupport(ResetPasswordForm::class, ResetPasswordRequest::class, trans('plugins/captcha-pro::captcha-pro.admin_reset_password_form'));
        }
    }

    protected function registerStandaloneSettingsPanel(): void
    {
        PanelSectionManager::default()->beforeRendering(function (): void {
            PanelSectionManager::registerItem(
                SettingOthersPanelSection::class,
                fn () => PanelSectionItem::make('captcha')
                    ->setTitle(trans('plugins/captcha-pro::captcha-pro.settings.title'))
                    ->withIcon('ti ti-shield-check')
                    ->withPriority(150)
                    ->withDescription(trans('plugins/captcha-pro::captcha-pro.settings.panel_description'))
                    ->withPermission('captcha.settings')
                    ->withRoute('captcha.settings')
            );
        });
    }

    protected function extendBaseCaptchaSettings(): void
    {
        if (class_exists(CaptchaSettingForm::class)) {
            CaptchaSettingForm::extend(function (FormAbstract $form): void {
                $this->extendCaptchaSettingForm($form);
            }, 20);
        }

        add_filter('captcha_settings_validation_rules', function (array $rules): array {
            return $this->extendCaptchaSettingRules($rules);
        });
    }

    protected function extendCaptchaSettingForm(FormAbstract $form): void
    {
        if ($form->has('captcha_pro_provider')) {
            return;
        }

        $registry = new ProviderRegistry;
        $provider = $registry->currentProvider(old('captcha_pro_provider', setting('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA)));

        foreach ([
            'captcha_setting_warning',
            'captcha_type',
            'captcha_hide_badge',
            'captcha_show_disclaimer',
            'recaptcha_score',
            'captcha_site_key',
            'captcha_secret',
        ] as $field) {
            $this->collapseExistingField($form, $field, ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA, $provider);
        }

        $form
            ->addAfter(
                $form->has('captcha_setting_warning') ? 'captcha_setting_warning' : 'enable_captcha',
                'captcha_pro_provider',
                RadioField::class,
                RadioFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.provider'))
                    ->choices($registry->choices())
                    ->selected($provider)
                    ->helperText(trans('plugins/captcha-pro::captcha-pro.settings.provider_help'))
            )
            ->addAfter(
                'captcha_pro_provider',
                'captcha_pro_turnstile_site_key',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.turnstile_site_key'))
                    ->value(setting('captcha_pro_turnstile_site_key'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE, $provider)
                    ->maxLength(255)
            )
            ->addAfter(
                'captcha_pro_turnstile_site_key',
                'captcha_pro_turnstile_secret',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.turnstile_secret'))
                    ->value(setting('captcha_pro_turnstile_secret'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE, $provider)
                    ->maxLength(255)
            )
            ->addAfter(
                'captcha_pro_turnstile_secret',
                'captcha_pro_turnstile_theme',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.turnstile_theme'))
                    ->choices([
                        'auto' => trans('plugins/captcha-pro::captcha-pro.settings.turnstile_theme_auto'),
                        'light' => trans('plugins/captcha-pro::captcha-pro.settings.turnstile_theme_light'),
                        'dark' => trans('plugins/captcha-pro::captcha-pro.settings.turnstile_theme_dark'),
                    ])
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE, $provider)
                    ->selected(setting('captcha_pro_turnstile_theme', 'auto'))
            )
            ->addAfter(
                'captcha_pro_turnstile_theme',
                'captcha_pro_turnstile_size',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.turnstile_size'))
                    ->choices([
                        'normal' => trans('plugins/captcha-pro::captcha-pro.settings.turnstile_size_normal'),
                        'compact' => trans('plugins/captcha-pro::captcha-pro.settings.turnstile_size_compact'),
                        'flexible' => trans('plugins/captcha-pro::captcha-pro.settings.turnstile_size_flexible'),
                    ])
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE, $provider)
                    ->selected(setting('captcha_pro_turnstile_size', 'normal'))
            )
            ->addAfter(
                'captcha_pro_turnstile_size',
                'captcha_pro_hcaptcha_site_key',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_site_key'))
                    ->value(setting('captcha_pro_hcaptcha_site_key'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_HCAPTCHA, $provider)
                    ->maxLength(255)
            )
            ->addAfter(
                'captcha_pro_hcaptcha_site_key',
                'captcha_pro_hcaptcha_secret',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_secret'))
                    ->value(setting('captcha_pro_hcaptcha_secret'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_HCAPTCHA, $provider)
                    ->maxLength(255)
            )
            ->addAfter(
                'captcha_pro_hcaptcha_secret',
                'captcha_pro_hcaptcha_theme',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_theme'))
                    ->choices([
                        'light' => trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_theme_light'),
                        'dark' => trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_theme_dark'),
                    ])
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_HCAPTCHA, $provider)
                    ->selected(setting('captcha_pro_hcaptcha_theme', 'light'))
            )
            ->addAfter(
                'captcha_pro_hcaptcha_theme',
                'captcha_pro_hcaptcha_size',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_size'))
                    ->choices([
                        'normal' => trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_size_normal'),
                        'compact' => trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_size_compact'),
                    ])
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_HCAPTCHA, $provider)
                    ->selected(setting('captcha_pro_hcaptcha_size', 'normal'))
            )
            ->addAfter(
                'captcha_pro_hcaptcha_size',
                'captcha_pro_cap_site_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.cap_site_url'))
                    ->helperText(trans('plugins/captcha-pro::captcha-pro.settings.cap_site_url_help'))
                    ->value(setting('captcha_pro_cap_site_url'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CAPTCHA, $provider)
                    ->maxLength(255)
            )
            ->addAfter(
                'captcha_pro_cap_site_url',
                'captcha_pro_cap_site_key',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.cap_site_key'))
                    ->value(setting('captcha_pro_cap_site_key'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CAPTCHA, $provider)
                    ->maxLength(255)
            )
            ->addAfter(
                'captcha_pro_cap_site_key',
                'captcha_pro_cap_secret',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.cap_secret'))
                    ->value(setting('captcha_pro_cap_secret'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CAPTCHA, $provider)
                    ->maxLength(255)
            );
    }

    protected function collapseExistingField(FormAbstract $form, string $field, string $provider, string $currentProvider): void
    {
        if (! $form->has($field)) {
            return;
        }

        $formField = $form->getField($field);
        $options = $formField->getOptions();
        $wrapper = $options['wrapper'] ?? [];

        if ($wrapper === false) {
            $wrapper = [];
        }

        $styles = array_filter([
            $wrapper['style'] ?? null,
            $provider === $currentProvider ? null : 'display: none',
        ]);

        $options['wrapper'] = [
            ...$wrapper,
            'data-bb-collapse' => 'true',
            'data-bb-trigger' => '[name=captcha_pro_provider]',
            'data-bb-value' => $provider,
            'style' => $styles ? implode(';', $styles) : '',
        ];

        $formField->setOptions($options);
    }

    protected function extendCaptchaSettingRules(array $rules): array
    {
        $onOffRule = new OnOffRule;
        $registry = new ProviderRegistry;
        $provider = $registry->currentProvider(request()->input('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA));
        $enabled = request()->input('enable_captcha') === '1' || request()->input('enable_captcha') === 1;
        $googleProviderEnabled = $enabled && $provider === ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA;
        $turnstileProviderEnabled = $enabled && $provider === ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE;
        $hCaptchaProviderEnabled = $enabled && $provider === ProviderRegistry::PROVIDER_HCAPTCHA;
        $capCaptchaProviderEnabled = $enabled && $provider === ProviderRegistry::PROVIDER_CAPTCHA;

        return [
            ...$rules,
            'captcha_pro_provider' => ['nullable', Rule::in(array_keys($registry->choices())), Rule::requiredIf($enabled)],
            'captcha_type' => ['nullable', 'in:v2,v3', Rule::requiredIf($googleProviderEnabled)],
            'captcha_site_key' => ['nullable', 'string', 'max:120', Rule::requiredIf($googleProviderEnabled)],
            'captcha_secret' => ['nullable', 'string', 'max:120', Rule::requiredIf($googleProviderEnabled)],
            'recaptcha_score' => ['nullable', Rule::in(app('captcha')->scores()), Rule::requiredIf($googleProviderEnabled && request()->input('captcha_type') === 'v3')],
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
        ];
    }

    protected function registerStandaloneFormHooks(): void
    {
        FormAbstract::beforeRendering(function (FormAbstract $form): void {
            if (! app('captcha')->isEnabled() && ! app('captcha')->mathCaptchaEnabled()) {
                return;
            }

            $fieldKey = 'submit';

            $attributes = [
                'colspan' => $form->getColumns('lg'),
            ];

            if ($form instanceof FormFront) {
                $fieldKey = $form->getFormEndKey() ?: ($form->has($fieldKey) ? $fieldKey : array_key_last($form->getFields()));

                if ($form->getFormInputWrapperClass()) {
                    $attributes['wrapper'] = ['class' => $form->getFormInputWrapperClass()];
                }

                if ($form->getFormLabelClass()) {
                    $attributes['label_attr'] = ['class' => $form->getFormLabelClass()];
                }

                if ($form->getFormInputClass()) {
                    $attributes['attr'] = ['class' => $form->getFormInputClass()];
                }
            }

            if (app('captcha')->reCaptchaEnabled() && ! $form->has('recaptcha') && app('captcha')->formSetting($form::class, 'enable_recaptcha')) {
                $form->addBefore($fieldKey, 'recaptcha', ReCaptchaField::class, $attributes);
            }

            if (app('captcha')->mathCaptchaEnabled() && ! $form->has('math_captcha') && app('captcha')->formSetting($form::class, 'enable_math_captcha')) {
                $form->addBefore($fieldKey, 'math_captcha', MathCaptchaField::class, $attributes);
            }
        });

        $this->app['events']->listen(Routing::class, function (): void {
            add_filter('core_request_rules', function (array $rules, Request $request) {
                if (! app('captcha')->isEnabled() && ! app('captcha')->mathCaptchaEnabled()) {
                    return $rules;
                }

                app('captcha')->getFormsSupport();

                $form = app('captcha')->formByRequest($request::class);

                if (! $form) {
                    return $rules;
                }

                if (app('captcha')->reCaptchaEnabled() && app('captcha')->formSetting($form, 'enable_recaptcha')) {
                    $rules = [...$rules, ...app('captcha')->rules()];
                }

                if (app('captcha')->mathCaptchaEnabled() && app('captcha')->formSetting($form, 'enable_math_captcha')) {
                    $rules = [...$rules, ...app('captcha')->mathCaptchaRules()];
                }

                return $rules;
            }, 128, 2);
        });

        add_filter('form_extra_fields_render', function (?string $fields = null, ?string $form = null): ?string {
            if (! app('captcha')->isEnabled() && ! app('captcha')->mathCaptchaEnabled()) {
                return $fields;
            }

            return $fields.view('plugins/captcha-pro::forms.old-version-support', compact('form'))->render();
        }, 128, 2);

        add_action('form_extra_fields_validate', function (IlluminateRequest $request, ?string $form = null): void {
            if (! app('captcha')->isEnabled() && ! app('captcha')->mathCaptchaEnabled()) {
                return;
            }

            if (
                app('captcha')->reCaptchaEnabled()
                && (! $form || ! class_exists($form) || app('captcha')->formSetting($form, 'enable_recaptcha'))
                && ! $request instanceof Request
            ) {
                Validator::validate($request->input(), app('captcha')->rules());
            }

            if (app('captcha')->mathCaptchaEnabled() && (! $form || ! class_exists($form) || app('captcha')->formSetting($form, 'enable_math_captcha'))) {
                Validator::validate($request->input(), app('captcha')->mathCaptchaRules());
            }
        }, 999, 2);

        add_filter('core_request_messages', fn (array $messages): array => [
            ...$messages,
            'captcha' => trans('plugins/captcha-pro::captcha-pro.verification_failed'),
            'math_captcha' => trans('plugins/captcha-pro::captcha-pro.math_verification_failed'),
        ], 999);

        add_filter('core_request_attributes', fn (array $attributes): array => [
            ...$attributes,
            ...app('captcha')->attributes(),
        ], 999);
    }

    public function bootValidator(): void
    {
        $validator = $this->app['validator'];

        $validator->extend('captcha', function ($attribute, $value, $parameters): bool {
            if (! app('captcha')->reCaptchaEnabled()) {
                return true;
            }

            if (! is_string($value)) {
                return false;
            }

            return app('captcha')->verify($value, $this->app['request']->getClientIp(), $parameters);
        }, trans('plugins/captcha-pro::captcha-pro.verification_failed'));

        $validator->extend('math_captcha', function ($attribute, $value): bool {
            if (! is_string($value)) {
                return false;
            }

            return $this->app['math-captcha']->verify($value);
        }, trans('plugins/captcha-pro::captcha-pro.math_verification_failed'));
    }
}
