<?php

namespace Unleash\Client\ConstraintValidator\Operator;

use Override;
use Unleash\Client\Exception\OperatorValidatorException;

/**
 * @internal
 */
abstract class AbstractOperatorValidator implements OperatorValidator
{
    /**
     * @param array<mixed>|string|null $allowedValues
     */
    public function __invoke(string $currentValue, $allowedValues): bool
    {
        if ($allowedValues === null) {
            throw new OperatorValidatorException('No values to validate against have been found');
        }
        if ($this->isMultiple($allowedValues)) {
            assert(is_array($allowedValues));

            foreach ($allowedValues as $allowedValue) {
                assert(is_string($allowedValue) || is_array($allowedValue));

                if (!$this->acceptsValues($allowedValue)) {
                    throw new OperatorValidatorException('Invalid value have been passed in an array with values');
                }

                if ($this->validate($currentValue, $allowedValue)) {
                    return true;
                }
            }

            return false;
        }
        if (!$this->acceptsValues($allowedValues)) {
            throw new OperatorValidatorException('Values of unacceptable format have been passed');
        }
        return $this->validate($currentValue, $allowedValues);
    }

    /**
     * @param array<mixed>|string $values
     */
    abstract protected function acceptsValues($values): bool;

    /**
     * @param string|array<mixed> $searchInValue
     */
    abstract protected function validate(string $currentValue, $searchInValue): bool;

    /**
     * @param array<mixed>|string $allowedValues
     */
    private function isMultiple($allowedValues): bool
    {
        return is_array($allowedValues) && !$this->acceptsValues($allowedValues);
    }
}
