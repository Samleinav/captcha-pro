<?php

namespace Botble\CaptchaPro\Http\Controllers\Settings;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\CaptchaPro\Forms\Settings\CaptchaProSettingForm;
use Botble\CaptchaPro\Http\Requests\Settings\CaptchaProSettingRequest;
use Botble\CaptchaPro\Services\ProviderRegistry;
use Botble\Setting\Http\Controllers\SettingController;

class CaptchaProSettingController extends SettingController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('core/base::base.panel.others'));
    }

    public function edit(): string
    {
        $this->pageTitle(trans('plugins/captcha-pro::captcha-pro.settings.title'));

        return CaptchaProSettingForm::create()->renderForm();
    }

    public function update(CaptchaProSettingRequest $request): BaseHttpResponse
    {
        $data = $request->validated();
        $data = [
            ...$data,
            'enable_math_captcha_for_contact_form' => $request->input('enable_math_captcha_botble_contact_forms_fronts_contact_form'),
            'enable_math_captcha_for_newsletter_form' => $request->input('enable_math_captcha_botble_newsletter_forms_fronts_newsletter_form'),
        ];

        if (($data['captcha_pro_provider'] ?? null) !== ProviderRegistry::PROVIDER_GOOGLE_RECAPTCHA) {
            $data['captcha_type'] = 'v2';
        }

        return $this->performUpdate($data);
    }
}
