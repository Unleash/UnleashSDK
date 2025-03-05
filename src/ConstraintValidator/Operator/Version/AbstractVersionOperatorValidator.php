<?php

namespace Unleash\Client\ConstraintValidator\Operator\Version;

use Override;
use Unleash\Client\ConstraintValidator\Operator\AbstractOperatorValidator;

/**
 * @internal
 */
abstract class AbstractVersionOperatorValidator extends AbstractOperatorValidator
{
    /**
     * @var string
     */
    private const REGEX = '@^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$@';

    #[Override]
    protected function acceptsValues(array|string $values): bool
    {
        return is_string($values) && preg_match(self::REGEX, $values);
    }
}
