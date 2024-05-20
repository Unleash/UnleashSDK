<?php

namespace Unleash\Client\ConstraintValidator\Operator\Number;

use Override;

/**
 * @internal
 */
final class NumberGreaterThanOrEqualsOperatorValidator extends AbstractNumberOperatorValidator
{
    #[Override]
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return $this->convert($currentValue) >= $this->convert($searchInValue);
    }
}
