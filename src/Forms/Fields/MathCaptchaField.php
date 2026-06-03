<?php

namespace Botble\CaptchaPro\Forms\Fields;

use Botble\Base\Forms\FormField;

class MathCaptchaField extends FormField
{
    protected function getTemplate(): string
    {
        return 'plugins/captcha-pro::forms.fields.math-captcha';
    }
}
