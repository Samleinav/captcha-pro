<?php

namespace Botble\CaptchaPro\Contracts;

interface CaptchaProvider
{
    public function key(): string;

    public function label(): string;

    public function inputName(): string;

    public function isEnabled(): bool;

    public function display(array $attributes = [], array $options = []): ?string;

    public function verify(string $response, ?string $clientIp = null, array $options = []): bool;
}
