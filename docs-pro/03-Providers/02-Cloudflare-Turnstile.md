# Cloudflare Turnstile

Cloudflare Turnstile provides CAPTCHA protection without using Google reCAPTCHA.

## Required fields

Configure these in **Settings > Captcha**:

- Cloudflare Turnstile site key.
- Cloudflare Turnstile secret.
- Theme.
- Size.

## Token field

Turnstile submits:

```text
cf-turnstile-response
```

Do not hardcode this field in plugin controllers. Use:

```php
app('captcha')->rules();
```

## Standalone pages

Turnstile needs its script to load in the page. If your plugin renders a complete Blade document instead of using a Botble theme layout, print the footer hook before `</body>`.

```blade
@if (defined('BASE_FILTER_FOOTER_LAYOUT_TEMPLATE'))
    {!! apply_filters(BASE_FILTER_FOOTER_LAYOUT_TEMPLATE, '') !!}
@endif
```

If you see "The Captcha field is required" and the Turnstile widget is not visible, the footer hook is the first thing to check.

