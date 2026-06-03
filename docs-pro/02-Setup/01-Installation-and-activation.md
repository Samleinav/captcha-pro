# Installation and activation

Install Captcha Pro like any Botble plugin, then activate it from the admin panel or CLI.

## Requirements

- Botble CMS 7.6.3 or newer.
- PHP version supported by your Botble installation.
- Valid credentials for the provider you choose.

## Activate the plugin

From the admin panel, open **Plugins** and activate **Captcha Pro**.

From CLI:

```bash
php artisan cms:plugin:activate captcha-pro
php artisan optimize:clear
```

## Base Captcha plugin compatibility

Captcha Pro can run with or without Botble's base Captcha plugin.

When the base plugin is active, Captcha Pro extends its existing settings form.

When the base plugin is inactive, Captcha Pro registers a standalone **Settings > Captcha** panel.

## After activation

Open **Settings > Captcha** and confirm you see the **Provider** options:

- Google reCAPTCHA
- Cloudflare Turnstile
- hCaptcha
- Cap Captcha

If the settings panel does not update, run:

```bash
php artisan optimize:clear
```

