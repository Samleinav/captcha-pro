# Captcha Pro

Captcha Pro lets a Botble site choose one global CAPTCHA provider while keeping the familiar Botble form selectors.

Use it when you want to protect admin, customer, contact, newsletter, or custom plugin forms with Google reCAPTCHA, Cloudflare Turnstile, hCaptcha, CAP, or Math CAPTCHA.

## What Captcha Pro does

- Replaces the `captcha` service with a provider-aware manager.
- Adds a provider selector to the CAPTCHA settings screen.
- Keeps the existing Botble per-form settings such as "Enable for Admin login form".
- Supports form registration from other plugins through a generic hook.
- Validates the active provider token without plugin code needing to know the provider input name.
- Keeps Math CAPTCHA available as a separate option.

## Supported providers

- **Google reCAPTCHA** v2 and v3.
- **Cloudflare Turnstile**.
- **hCaptcha**.
- **CAP Captcha** using a CAP Standalone instance.
- **Math CAPTCHA** for local math challenge protection.

## Where to configure it

Open **Settings > Captcha**.

If Botble's base Captcha plugin is active, Captcha Pro extends that settings page.

If the base Captcha plugin is not active, Captcha Pro provides its own settings page with the same route and permission.

## Typical setup

1. Enable CAPTCHA.
2. Select the provider.
3. Enter the provider credentials.
4. Enable CAPTCHA for the forms you want to protect.
5. Save settings.
6. Test every protected form from a logged-out browser session.

## For developers

Plugins should not be hardcoded into Captcha Pro. A plugin that owns a custom form should register itself using the `captcha_register_form_support` hook. See **Hooks > Register custom forms**.

