# CAP Captcha

CAP Captcha uses a CAP Standalone instance.

## Required fields

Configure these in **Settings > Captcha**:

- Cap site URL.
- Cap site key.
- Cap secret key.

The site URL should be the public URL of your CAP Standalone service without the site key path.

## Token field

CAP submits:

```text
cap-token
```

Use `app('captcha')->rules()` for validation instead of hardcoding the field name.

## Script loading

CAP uses a JavaScript module. Standalone Blade pages must print the footer hook before `</body>` so the widget script can load.

