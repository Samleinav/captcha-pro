# Register custom forms

Captcha Pro exposes a generic action hook for other plugins:

```php
captcha_register_form_support
```

Use this hook when a plugin owns custom forms and wants them to appear in **Settings > Captcha**.

## Service provider pattern

Add a registration method to your plugin service provider.

```php
protected function registerCaptchaFormSupport(): void
{
    if (function_exists('add_action')) {
        add_action('captcha_register_form_support', function (?object $captcha = null): void {
            PortalFormProtection::registerCaptchaForms();
        });
    }

    $this->app->booted(function (): void {
        PortalFormProtection::registerCaptchaForms();
    });
}
```

Call it from `boot()` after translations are loaded.

The `app->booted()` fallback keeps compatibility with Botble's base Captcha plugin and older registration flows.

## PortalFormProtection example

```php
final class PortalFormProtection
{
    public const PORTAL_LOGIN = 'Botble\\MyPlugin\\CaptchaForms\\PortalLoginForm';
    public const TICKET_CREATE = 'Botble\\MyPlugin\\CaptchaForms\\TicketCreateForm';

    public static function registerCaptchaForms(): void
    {
        $captcha = app()->bound('captcha') ? app('captcha') : null;

        if (! $captcha || ! method_exists($captcha, 'registerFormSupport')) {
            return;
        }

        foreach (self::forms() as $form => $title) {
            $captcha->registerFormSupport($form, str_replace('Form', 'Request', $form), $title);
        }
    }

    public static function forms(): array
    {
        return [
            self::PORTAL_LOGIN => trans('plugins/my-plugin::my-plugin.captcha.portal_login'),
            self::TICKET_CREATE => trans('plugins/my-plugin::my-plugin.captcha.ticket_create'),
        ];
    }
}
```

## Translation keys

Add readable labels for the settings screen:

```php
'captcha' => [
    'portal_login' => 'My Plugin portal login',
    'ticket_create' => 'My Plugin ticket form',
],
```

## Rules

- The form identifier must be stable.
- The same form identifier must be used for settings checks, rendering, and validation.
- Do not add plugin-specific compatibility code to Captcha Pro.

