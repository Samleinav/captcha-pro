<?php

namespace Botble\CaptchaPro;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Facades\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Setting::delete([
            'captcha_pro_provider',
            'captcha_pro_turnstile_site_key',
            'captcha_pro_turnstile_secret',
            'captcha_pro_turnstile_theme',
            'captcha_pro_turnstile_size',
            'captcha_pro_hcaptcha_site_key',
            'captcha_pro_hcaptcha_secret',
            'captcha_pro_hcaptcha_theme',
            'captcha_pro_hcaptcha_size',
            'captcha_pro_cap_site_url',
            'captcha_pro_cap_site_key',
            'captcha_pro_cap_secret',
        ]);
    }
}
