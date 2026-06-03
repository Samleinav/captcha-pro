<?php

namespace Botble\CaptchaPro\Facades;

use Illuminate\Support\Facades\Facade;

class Captcha extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'captcha';
    }
}
