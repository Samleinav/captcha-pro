# Provider settings

Captcha Pro uses one active provider at a time. The provider is selected in **Settings > Captcha**.

## Enable CAPTCHA

Turn on **Enable CAPTCHA** before selecting forms.

CAPTCHA validation only runs when the global CAPTCHA switch is enabled and the selected provider has valid credentials.

## Google reCAPTCHA

Google reCAPTCHA supports v2 and v3.

Fields:

- **Google reCAPTCHA type**: v2 or v3.
- **Google reCAPTCHA site key**.
- **Google reCAPTCHA secret**.
- **Minimum v3 score** when v3 is selected.
- **Hide reCAPTCHA badge**.
- **Display reCAPTCHA disclaimer**.

Use v2 when you want visible challenge behavior. Use v3 when you want score-based background verification.

## Cloudflare Turnstile

Fields:

- **Cloudflare Turnstile site key**.
- **Cloudflare Turnstile secret**.
- **Turnstile theme**: Auto, Light, or Dark.
- **Turnstile size**: Normal, Compact, or Flexible.

Turnstile posts `cf-turnstile-response`, but plugin code should never hardcode that field. Use `app('captcha')->rules()`.

## hCaptcha

Fields:

- **hCaptcha site key**.
- **hCaptcha secret**.
- **hCaptcha theme**: Light or Dark.
- **hCaptcha size**: Normal or Compact.

## CAP Captcha

Fields:

- **Cap site URL**.
- **Cap site key**.
- **Cap secret key**.

The site URL should be the public URL of the CAP Standalone instance, without the site key path.

## Math CAPTCHA

Math CAPTCHA is configured separately with **Enable Math CAPTCHA**.

You can enable provider CAPTCHA, Math CAPTCHA, or both for each form.

