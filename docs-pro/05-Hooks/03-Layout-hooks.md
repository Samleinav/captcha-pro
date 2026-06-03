# Layout hooks

Captcha providers load scripts through Botble layout hooks.

## Theme layouts

Most Botble themes already print the required hooks in their base layout.

If the form is inside a normal theme page, no extra work is usually needed.

## Standalone plugin pages

If the plugin view renders a full HTML page, add the hooks manually.

In `<head>`:

```blade
@if (defined('BASE_FILTER_HEAD_LAYOUT_TEMPLATE'))
    {!! apply_filters(BASE_FILTER_HEAD_LAYOUT_TEMPLATE, '') !!}
@endif
```

Before `</body>`:

```blade
@if (defined('BASE_FILTER_FOOTER_LAYOUT_TEMPLATE'))
    {!! apply_filters(BASE_FILTER_FOOTER_LAYOUT_TEMPLATE, '') !!}
@endif
```

## Required order

The footer hook must be printed after the form calls `app('captcha')->display()`.

This matters because `display()` registers the provider script while the form is rendering.

## Symptom when missing

The common symptom is:

```text
The Captcha field is required.
```

The settings checkbox is enabled and validation runs, but the widget is invisible or the provider token is not submitted.

