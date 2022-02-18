<?php

namespace Unleash\Client\ConstraintValidator\Operator\Version;

/**
 * @internal
 */
final class VersionLowerThanOperatorValidator extends AbstractVersionOperatorValidator
{
    protected function validate(string $currentValue, array|string $searchInValue): bool
    {
        assert(is_string($searchInValue));

        return version_compare($currentValue, $searchInValue, 'lt');
    }
}
