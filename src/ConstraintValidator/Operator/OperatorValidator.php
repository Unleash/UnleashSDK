<?php

namespace Unleash\Client\ConstraintValidator\Operator;

use Unleash\Client\Exception\OperatorValidatorException;

/**
 * @internal
 */
interface OperatorValidator
{
    /**
     * @param array<mixed>|string|null $allowedValues
     *
     * @throws OperatorValidatorException
     */
    public function __invoke(string $currentValue, $allowedValues): bool;
}
