<?php

namespace Botble\CaptchaPro\Forms\Settings;

use Botble\Base\Facades\Html;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\FieldOptions\RadioFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\RadioField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\CaptchaPro\Http\Requests\Settings\CaptchaProSettingRequest;
use Botble\CaptchaPro\Services\ProviderRegistry;
use Botble\Setting\Forms\SettingForm;

class CaptchaProSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $registry = new ProviderRegistry;
        $provider = $registry->currentProvider(old('captcha_pro_provider', setting('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA)));
        $captcha = app('captcha');

        $this
            ->setUrl(route('captcha.settings.update'))
            ->setSectionTitle(trans('plugins/captcha-pro::captcha-pro.settings.title'))
            ->setSectionDescription(trans('plugins/captcha-pro::captcha-pro.settings.description'))
            ->setValidatorClass(CaptchaProSettingRequest::class)
            ->add(
                'enable_captcha',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.enable_captcha'))
                    ->value($enabled = old('enable_captcha', (bool) setting('enable_captcha')))
            )
            ->addOpenCollapsible('enable_captcha', '1', $enabled)
            ->add(
                'captcha_pro_provider',
                RadioField::class,
                RadioFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.provider'))
                    ->choices($registry->choices())
                    ->selected($provider)
                    ->helperText(trans('plugins/captcha-pro::captcha-pro.settings.provider_help'))
            )
            ->addOpenCollapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA, $provider)
            ->addGoogleFields($provider)
            ->addCloseCollapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA)
            ->addOpenCollapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE, $provider)
            ->addTurnstileFields($provider)
            ->addCloseCollapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE)
            ->addOpenCollapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_HCAPTCHA, $provider)
            ->addHCaptchaFields($provider)
            ->addCloseCollapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_HCAPTCHA)
            ->addOpenCollapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CAPTCHA, $provider)
            ->addCapCaptchaFields($provider)
            ->addCloseCollapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CAPTCHA)
            ->addSelectFormFields('enable_recaptcha')
            ->addCloseCollapsible('enable_captcha', '1')
            ->add(
                'enable_math_captcha',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.enable_math_captcha'))
                    ->value($mathEnabled = old('enable_math_captcha', $captcha->mathCaptchaEnabled()))
            )
            ->addOpenCollapsible('enable_math_captcha', '1', $mathEnabled)
            ->addSelectFormFields('enable_math_captcha')
            ->addCloseCollapsible('enable_math_captcha', '1');
    }

    protected function addGoogleFields(string $provider): static
    {
        return $this
            ->add(
                'captcha_type',
                RadioField::class,
                RadioFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.google_type'))
                    ->choices([
                        'v2' => trans('plugins/captcha-pro::captcha-pro.settings.google_type_v2'),
                        'v3' => trans('plugins/captcha-pro::captcha-pro.settings.google_type_v3'),
                    ])
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA, $provider)
                    ->selected($captchaType = app('captcha')->reCaptchaType())
            )
            ->addOpenCollapsible('captcha_type', 'v3', $captchaType)
            ->add(
                'captcha_hide_badge',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.google_hide_badge'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA, $provider)
                    ->defaultValue((bool) setting('captcha_hide_badge'))
            )
            ->add(
                'captcha_show_disclaimer',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.google_show_disclaimer'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA, $provider)
                    ->defaultValue((bool) setting('captcha_show_disclaimer', false))
            )
            ->add(
                'recaptcha_score',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.google_score'))
                    ->choices(app('captcha')->scores())
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA, $provider)
                    ->selected(setting('recaptcha_score', 0.6))
            )
            ->addCloseCollapsible('captcha_type', 'v3')
            ->add(
                'captcha_site_key',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.google_site_key'))
                    ->value(setting('captcha_site_key'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA, $provider)
                    ->maxLength(120)
            )
            ->add(
                'captcha_secret',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.google_secret'))
                    ->value(setting('captcha_secret'))
                    ->maxLength(120)
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA, $provider)
                    ->helperText(trans('plugins/captcha-pro::captcha-pro.settings.google_credentials_help', [
                        'link' => Html::link(
                            'https://www.google.com/recaptcha/admin#list',
                            trans('plugins/captcha-pro::captcha-pro.settings.google_credentials_here'),
                            ['target' => '_blank']
                        ),
                    ]))
            );
    }

    protected function addTurnstileFields(string $provider): static
    {
        return $this
            ->add(
                'captcha_pro_turnstile_site_key',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.turnstile_site_key'))
                    ->value(setting('captcha_pro_turnstile_site_key'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE, $provider)
                    ->maxLength(255)
            )
            ->add(
                'captcha_pro_turnstile_secret',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.turnstile_secret'))
                    ->value(setting('captcha_pro_turnstile_secret'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CLOUDFLARE_TURNSTILE, $provider)
                    ->maxLength(255)
            )
            ->add(
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
            ->add(
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
            );
    }

    protected function addHCaptchaFields(string $provider): static
    {
        return $this
            ->add(
                'captcha_pro_hcaptcha_site_key',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_site_key'))
                    ->value(setting('captcha_pro_hcaptcha_site_key'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_HCAPTCHA, $provider)
                    ->maxLength(255)
            )
            ->add(
                'captcha_pro_hcaptcha_secret',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.hcaptcha_secret'))
                    ->value(setting('captcha_pro_hcaptcha_secret'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_HCAPTCHA, $provider)
                    ->maxLength(255)
            )
            ->add(
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
            ->add(
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
            );
    }

    protected function addCapCaptchaFields(string $provider): static
    {
        return $this
            ->add(
                'captcha_pro_cap_site_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.cap_site_url'))
                    ->helperText(trans('plugins/captcha-pro::captcha-pro.settings.cap_site_url_help'))
                    ->value(setting('captcha_pro_cap_site_url'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CAPTCHA, $provider)
                    ->maxLength(255)
            )
            ->add(
                'captcha_pro_cap_site_key',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.cap_site_key'))
                    ->value(setting('captcha_pro_cap_site_key'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CAPTCHA, $provider)
                    ->maxLength(255)
            )
            ->add(
                'captcha_pro_cap_secret',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.cap_secret'))
                    ->value(setting('captcha_pro_cap_secret'))
                    ->collapsible('captcha_pro_provider', ProviderRegistry::PROVIDER_CAPTCHA, $provider)
                    ->maxLength(255)
            );
    }

    protected function addSelectFormFields(string $key): static
    {
        $captcha = app('captcha');

        foreach ($captcha->getFormsSupport() as $form => $title) {
            $this->add(
                $captcha->formSettingKey($form, $key),
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/captcha-pro::captcha-pro.settings.enable_for_form', ['form' => $title]))
                    ->value($captcha->formSetting($form, $key))
            );
        }

        return $this;
    }
}
