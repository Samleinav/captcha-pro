# Troubleshooting

Use this page when CAPTCHA settings, rendering, or validation do not behave as expected.

## The form is missing from settings

Check:

1. The owning plugin is active.
2. The plugin registers `captcha_register_form_support`.
3. The registration runs after translations are loaded.
4. The form identifier is stable.
5. Cache is cleared.

Run:

```bash
php artisan optimize:clear
```

## The widget is missing but validation runs

This usually means the form is enabled but the page did not load the provider script.

Check:

1. The Blade view calls `app('captcha')->display()`.
2. The view uses the same form key that validation uses.
3. The page prints `BASE_FILTER_FOOTER_LAYOUT_TEMPLATE` before `</body>`.
4. Browser devtools show the provider script.

For Cloudflare Turnstile, confirm the page loads:

```text
https://challenges.cloudflare.com/turnstile/v0/api.js
```

## The widget appears but validation fails

Check:

1. Provider site key and secret match.
2. The domain is allowed by the provider.
3. The submitted request includes the provider token.
4. There are not multiple stale widgets on the same form.

## Math CAPTCHA fails

Check:

1. The form includes the Math CAPTCHA input.
2. Session storage works.
3. The form posts to the same application domain.
4. The Math CAPTCHA setting is enabled for the same form key.

## Settings save fails

Check required fields for the selected provider. Captcha Pro validates only the active provider credentials as required when CAPTCHA is enabled.

If you switch away from Google reCAPTCHA, Captcha Pro stores `captcha_type` as `v2` because non-Google providers do not use Google v3 scoring.

