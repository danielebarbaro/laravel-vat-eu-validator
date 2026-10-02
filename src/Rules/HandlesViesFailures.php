<?php

namespace Danielebarbaro\LaravelVatEuValidator\Rules;

use Closure;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use InvalidArgumentException;

/**
 * Applies the vat-validator.on_vies_failure setting when VIES cannot answer.
 */
trait HandlesViesFailures
{
    private bool $viesUnavailable = false;

    /**
     * Whether the last validation failed because VIES could not answer.
     */
    public function failedBecauseViesIsUnavailable(): bool
    {
        return $this->viesUnavailable;
    }

    /**
     * @throws ViesException when the setting is "throw"
     */
    protected function handleViesFailure(ViesException $viesException, string $attribute, Closure $fail): void
    {
        $mode = config('vat-validator.on_vies_failure', 'throw');

        match ($mode) {
            'throw' => throw $viesException,
            'pass' => null,
            'fail' => $this->failBecauseViesIsUnavailable($attribute, $fail),
            default => throw new InvalidArgumentException(
                "Unknown vat-validator.on_vies_failure value: {$mode}",
                0,
                $viesException,
            ),
        };
    }

    private function failBecauseViesIsUnavailable(string $attribute, Closure $fail): void
    {
        $this->viesUnavailable = true;

        $fail(__('laravelVatEuValidator::validation.vies_unavailable', ['attribute' => $attribute]));
    }
}
