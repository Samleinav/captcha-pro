<?php

namespace Botble\CaptchaPro\Services;

use Botble\Base\Facades\Html;
use Exception;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;

class MathCaptcha
{
    public function __construct(protected SessionManager|Store|null $session = null) {}

    public function label(): string
    {
        $label = $this->getMathLabelOnly();

        return trans('plugins/captcha-pro::captcha-pro.math_question', compact('label'));
    }

    public function getMathLabelOnly(): string
    {
        return sprintf(
            '%d %s %d',
            $this->getMathSecondOperator(),
            $this->getMathOperand(),
            $this->getMathFirstOperator()
        );
    }

    public function input(array $attributes = []): string
    {
        $attributes = array_merge([
            'type' => 'text',
            'id' => 'math-captcha',
            'name' => 'math-captcha',
            'required' => 'required|string',
            'value' => old('math-captcha'),
        ], $attributes);

        return (string) Html::tag('input', '', $attributes);
    }

    public function verify(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return (float) $value === (float) $this->getMathResult();
    }

    public function reset(): void
    {
        $this->session()->forget('math-captcha.first');
        $this->session()->forget('math-captcha.second');
        $this->session()->forget('math-captcha.operand');
    }

    protected function getMathOperand(): string
    {
        if (! $this->session()->get('math-captcha.operand')) {
            $operands = $this->operands();

            $this->session()->put('math-captcha.operand', $operands[array_rand($operands)]);
        }

        return $this->session()->get('math-captcha.operand');
    }

    protected function getMathFirstOperator(): int
    {
        if (! $this->session()->get('math-captcha.first')) {
            $this->session()->put('math-captcha.first', rand($this->randMin(), $this->randMax()));
        }

        return $this->session()->get('math-captcha.first');
    }

    protected function getMathSecondOperator(): int
    {
        if (! $this->session()->get('math-captcha.second')) {
            $this->session()->put(
                'math-captcha.second',
                $this->getMathFirstOperator() + rand($this->randMin(), $this->randMax())
            );
        }

        return $this->session()->get('math-captcha.second');
    }

    protected function getMathResult(): float|int
    {
        return match ($this->getMathOperand()) {
            '+' => $this->getMathFirstOperator() + $this->getMathSecondOperator(),
            '*' => $this->getMathFirstOperator() * $this->getMathSecondOperator(),
            '-' => abs($this->getMathFirstOperator() - $this->getMathSecondOperator()),
            default => throw new Exception('Math captcha uses an unknown operand.'),
        };
    }

    protected function operands(): array
    {
        return config('plugins.captcha-pro.general.math-captcha.operands', ['+', '-', '*']);
    }

    protected function randMin(): int
    {
        return (int) config('plugins.captcha-pro.general.math-captcha.rand-min', 1);
    }

    protected function randMax(): int
    {
        return (int) config('plugins.captcha-pro.general.math-captcha.rand-max', 10);
    }

    protected function session(): SessionManager|Store
    {
        return $this->session ?: app('session');
    }
}
