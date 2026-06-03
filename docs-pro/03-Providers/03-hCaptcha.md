# hCaptcha

hCaptcha is supported as a first-class Captcha Pro provider.

## Required fields

Configure these in **Settings > Captcha**:

- hCaptcha site key.
- hCaptcha secret.
- Theme.
- Size.

## Token field

hCaptcha submits:

```text
h-captcha-response
```

Custom plugin code should use `app('captcha')->rules()` instead of referencing this field directly.

## Common checks

If validation fails:

1. Confirm the site key and secret.
2. Confirm the domain is allowed in hCaptcha.
3. Confirm the widget is visible in the protected form.
4. Confirm footer hooks are printed on standalone pages.

