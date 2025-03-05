<?php

namespace Unleash\Client\ConstraintValidator\Operator\Lists;

use Override;

/**
 * @internal
 */
final class InListOperatorValidator extends AbstractListOperatorValidator
{
    /**
     * @param mixed[]|string $searchInValue
     */
    protected function validate(string $currentValue, $searchInValue): bool
    {
        assert(is_array($searchInValue));
        return in_array($currentValue, $searchInValue, true);
    }
}
