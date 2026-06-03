# Common questions

## Do I need Botble's base Captcha plugin?

No. Captcha Pro can provide its own settings page.

If the base Captcha plugin is active, Captcha Pro extends that page instead.

## Can I enable multiple providers at once?

No. Captcha Pro uses one active provider at a time.

You can combine the active provider with Math CAPTCHA if you want both checks.

## Should custom plugins hardcode Turnstile or hCaptcha fields?

No. Custom plugins should use:

```php
app('captcha')->rules();
```

Captcha Pro chooses the correct field for the active provider.

## How do plugins add their forms to the settings list?

Use the `captcha_register_form_support` hook and call `app('captcha')->registerFormSupport()`.

See **Hooks > Register custom forms**.

## Why is "The Captcha field is required" shown when no widget is visible?

The form is enabled and validation is running, but the widget or provider script is not rendered.

For standalone Blade pages, add `BASE_FILTER_FOOTER_LAYOUT_TEMPLATE` before `</body>`.

## Where should I start after installation?

Start with:

1. Select a provider.
2. Add credentials.
3. Enable one low-risk form such as a test contact form.
4. Confirm the widget renders.
5. Submit the form.
6. Enable more forms after the first one works.

