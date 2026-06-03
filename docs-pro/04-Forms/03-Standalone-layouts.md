# Standalone layouts

Some plugin portals render a full HTML document instead of using a Botble theme layout.

These pages must print the Botble layout hooks used by CAPTCHA providers to inject scripts.

## Head hook

Place this in `<head>`:

```blade
@if (defined('BASE_FILTER_HEAD_LAYOUT_TEMPLATE'))
    {!! apply_filters(BASE_FILTER_HEAD_LAYOUT_TEMPLATE, '') !!}
@endif
```

## Footer hook

Place this right before `</body>`:

```blade
@if (defined('BASE_FILTER_FOOTER_LAYOUT_TEMPLATE'))
    {!! apply_filters(BASE_FILTER_FOOTER_LAYOUT_TEMPLATE, '') !!}
@endif
```

## Why it matters

`app('captcha')->display()` renders the widget and registers provider scripts into layout hooks.

If the page never prints those hooks, the HTML placeholder may exist but the provider script may not load.

This is common with Cloudflare Turnstile: the request fails with "The Captcha field is required" because the browser never creates `cf-turnstile-response`.

