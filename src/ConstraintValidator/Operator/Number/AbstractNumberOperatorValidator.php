<?php

namespace Unleash\Client\ConstraintValidator\Operator\Number;

use Override;
use Unleash\Client\ConstraintValidator\Operator\AbstractOperatorValidator;

/**
 * @internal
 */
abstract class AbstractNumberOperatorValidator extends AbstractOperatorValidator
{
    /**
     * @param mixed[]|string $values
     */
    protected function acceptsValues($values): bool
    {
        return is_string($values) && is_numeric($values);
    }

    /**
     * @return float|int
     */
    protected function convert(string $number)
    {
        if (strpos($number, '.') !== false) {
            return (float) $number;
        }

        return (int) $number;
    }
}
