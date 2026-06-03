# Form builder forms

Botble FormAbstract and FormFront forms can receive CAPTCHA fields automatically when the form class and request class are registered.

## Register the form

Register the actual form class and request class:

```php
app('captcha')->registerFormSupport(
    MyFrontendForm::class,
    MyFrontendRequest::class,
    trans('plugins/my-plugin::my-plugin.captcha.my_frontend_form')
);
```

## Automatic injection

When the form is enabled in **Settings > Captcha**, Captcha Pro can:

- add the provider CAPTCHA field before submit;
- add Math CAPTCHA before submit;
- add request validation rules through Botble's request rule hooks.

## Requirements

For automatic validation to work, the registered request class must be the class that handles the form submission.

The form and request mapping is resolved through `app('captcha')->formByRequest($request::class)`.

## When to use manual integration

Use manual integration for custom portal pages, plain Blade forms, or controllers that do not use FormAbstract/FormFront.

