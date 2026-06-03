# Google reCAPTCHA

Google reCAPTCHA is the default provider used by Botble's original Captcha plugin.

## v2

Use v2 when you want a visible CAPTCHA widget.

Validation uses the active provider rules:

```php
app('captcha')->rules();
```

The token field is `g-recaptcha-response`, but custom plugins should not reference it directly.

## v3

Use v3 when you want score-based verification.

The minimum score is configured in **Settings > Captcha**. Higher scores are stricter.

## Badge and disclaimer

If you hide the reCAPTCHA badge, make sure your site still follows Google's terms for disclosure. Captcha Pro includes a disclaimer option for this.

## Troubleshooting

If the form fails validation:

1. Confirm the site key and secret are for the same domain.
2. Confirm the selected type matches the credentials.
3. Confirm the form renders the CAPTCHA field.
4. Confirm layout footer hooks are printed on standalone pages.

