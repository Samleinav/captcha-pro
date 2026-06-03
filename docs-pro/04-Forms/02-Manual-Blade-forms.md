# Manual Blade forms

Manual Blade forms need three pieces:

1. a stable form identifier;
2. manual validation in the controller;
3. widget rendering in the Blade view.

## Form identifier

Use a stable class-like string:

```php
public const TICKET_CREATE = 'Botble\\MyPlugin\\CaptchaForms\\TicketCreateForm';
```

The string does not need to be a real class unless you want automatic FormAbstract handling.

## Render the provider widget

```blade
@if (\Botble\MyPlugin\Support\PortalFormProtection::botbleCaptchaEnabled(\Botble\MyPlugin\Support\PortalFormProtection::TICKET_CREATE))
    <div class="captcha-wrap">{!! app('captcha')->display() !!}</div>
@endif
```

## Render Math CAPTCHA

```blade
@if (\Botble\MyPlugin\Support\PortalFormProtection::mathCaptchaEnabled(\Botble\MyPlugin\Support\PortalFormProtection::TICKET_CREATE))
    <div class="math-captcha-wrap">
        <label for="math-captcha">{{ app('math-captcha')->label() }}</label>
        {!! app('math-captcha')->input(['placeholder' => app('math-captcha')->getMathLabelOnly() . ' = ?']) !!}
    </div>
@endif
```

## Validate in the controller

Run your normal form validation first, then validate CAPTCHA for the same form key.

```php
$request->validate($rules);

PortalFormProtection::validate($request, PortalFormProtection::TICKET_CREATE);
```

If the render key and validation key do not match, the widget can be hidden while validation still runs.

