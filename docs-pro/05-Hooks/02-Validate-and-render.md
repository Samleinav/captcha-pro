# Validate and render

After registering custom forms, the owning plugin is responsible for manual validation and rendering if it is not using FormAbstract.

## Check provider CAPTCHA

```php
public static function botbleCaptchaEnabled(string $form): bool
{
    $captcha = app()->bound('captcha') ? app('captcha') : null;

    return $captcha
        && method_exists($captcha, 'isEnabled')
        && method_exists($captcha, 'formSetting')
        && $captcha->isEnabled()
        && (bool) $captcha->formSetting($form, 'enable_recaptcha');
}
```

## Check Math CAPTCHA

```php
public static function mathCaptchaEnabled(string $form): bool
{
    $captcha = app()->bound('captcha') ? app('captcha') : null;

    return $captcha
        && app()->bound('math-captcha')
        && method_exists($captcha, 'mathCaptchaEnabled')
        && method_exists($captcha, 'formSetting')
        && $captcha->mathCaptchaEnabled()
        && (bool) $captcha->formSetting($form, 'enable_math_captcha');
}
```

## Validate provider and Math CAPTCHA

```php
public static function validate(Request $request, string $form): void
{
    $captcha = app()->bound('captcha') ? app('captcha') : null;

    if (! $captcha) {
        return;
    }

    $rules = [];

    if (self::botbleCaptchaEnabled($form)) {
        $rules = array_merge($rules, $captcha->rules());
    }

    if (self::mathCaptchaEnabled($form)) {
        $rules = array_merge($rules, $captcha->mathCaptchaRules());
    }

    if ($rules === []) {
        return;
    }

    Validator::make(
        $request->all(),
        $rules,
        [],
        method_exists($captcha, 'attributes') ? $captcha->attributes() : []
    )->validate();
}
```

## Render the same form key

```blade
@if (\Botble\MyPlugin\Support\PortalFormProtection::botbleCaptchaEnabled(\Botble\MyPlugin\Support\PortalFormProtection::TICKET_CREATE))
    <div class="captcha-wrap">{!! app('captcha')->display() !!}</div>
@endif
```

The form constant used in the view must be the same constant used in the controller.

