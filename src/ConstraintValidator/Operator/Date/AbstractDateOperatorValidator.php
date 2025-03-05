<?php

namespace Unleash\Client\ConstraintValidator\Operator\Date;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Override;
use Unleash\Client\ConstraintValidator\Operator\AbstractOperatorValidator;

/**
 * @internal
 */
abstract class AbstractDateOperatorValidator extends AbstractOperatorValidator
{
    /**
     * @param mixed[]|string $values
     */
    protected function acceptsValues($values): bool
    {
        if (!is_string($values)) {
            return false;
        }
        try {
            new DateTimeImmutable($values);

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    protected function convert(string $dateString): DateTimeInterface
    {
        return new DateTimeImmutable($dateString);
    }
}
