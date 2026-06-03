<?php

use Botble\Base\Facades\AdminHelper;
use Illuminate\Support\Facades\Route;

if (Route::has('captcha.settings')) {
    return;
}

Route::group(['namespace' => 'Botble\CaptchaPro\Http\Controllers'], function (): void {
    AdminHelper::registerRoutes(function (): void {
        Route::group(['prefix' => 'settings/captcha', 'as' => 'captcha.settings', 'permission' => 'captcha.settings'], function (): void {
            Route::get('/', [
                'uses' => 'Settings\CaptchaProSettingController@edit',
            ]);

            Route::put('/', [
                'as' => '.update',
                'uses' => 'Settings\CaptchaProSettingController@update',
            ]);
        });
    });
});
