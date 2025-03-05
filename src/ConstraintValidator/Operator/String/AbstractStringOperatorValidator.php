<?php

namespace Unleash\Client\ConstraintValidator\Operator\String;

use Override;
use Unleash\Client\ConstraintValidator\Operator\AbstractOperatorValidator;

/**
 * @internal
 */
abstract class AbstractStringOperatorValidator extends AbstractOperatorValidator
{
    /**
     * @param mixed[]|string $values
     */
    protected function acceptsValues($values): bool
    {
        return is_string($values);
    }
}
