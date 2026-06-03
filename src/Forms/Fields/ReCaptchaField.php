<?php

namespace Botble\CaptchaPro\Forms\Fields;

use Botble\Base\Forms\FormField;

class ReCaptchaField extends FormField
{
    protected function getTemplate(): string
    {
        return 'plugins/captcha-pro::forms.fields.recaptcha';
    }
}
