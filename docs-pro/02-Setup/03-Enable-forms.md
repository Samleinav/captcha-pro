# Enable forms

After the provider is configured, choose which forms should be protected.

## Built-in forms

Captcha Pro registers common admin forms:

- Admin login form.
- Admin forgot password form.
- Admin reset password form.

Other Botble plugins can register their own forms. Examples include customer login, customer register, contact, newsletter, support ticket, and custom plugin forms.

## Form selectors

Each selector is stored using the Botble form setting key format:

```text
enable_recaptcha_<form_class_as_snake_case_without_backslashes>
enable_math_captcha_<form_class_as_snake_case_without_backslashes>
```

Do not create these keys manually unless you know the exact form identifier.

## Missing forms

If a custom form does not appear in the settings list:

1. Confirm the owning plugin is active.
2. Confirm the plugin registers the form through `captcha_register_form_support`.
3. Run `php artisan optimize:clear`.
4. Reload **Settings > Captcha**.

See **Hooks > Register custom forms** for the developer pattern.

