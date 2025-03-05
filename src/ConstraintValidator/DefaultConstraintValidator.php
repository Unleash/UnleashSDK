<?php

namespace Unleash\Client\ConstraintValidator;

use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\ConstraintValidator\Operator\Date\DateAfterOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Date\DateBeforeOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Lists\InListOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Lists\NotInListOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberEqualsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberGreaterThanOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberGreaterThanOrEqualsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberLowerThanOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Number\NumberLowerThanOrEqualsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\String\StringContainsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\String\StringEndsWithOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\String\StringStartsWithOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Version\VersionEqualsOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Version\VersionGreaterThanOperatorValidator;
use Unleash\Client\ConstraintValidator\Operator\Version\VersionLowerThanOperatorValidator;
use Unleash\Client\DTO\Constraint;
use Unleash\Client\Enum\ConstraintOperator;
use Unleash\Client\Exception\OperatorValidatorException;

final class DefaultConstraintValidator implements ConstraintValidator
{
    public function validateConstraint(Constraint $constraint, Context $context): bool
    {
        $field = $constraint->getContextName();
        $currentValue = $context->findContextValue($field) ?? '';
        $callback = $this->getValidationCallback($constraint->getOperator());
        $valueToPass = method_exists($constraint, 'getSingleValue') && $constraint->getSingleValue() !== null
            ? $constraint->getSingleValue()
            : $constraint->getValues();
        $isCaseInsensitive = method_exists($constraint, 'isCaseInsensitive') && $constraint->isCaseInsensitive();
        if ($isCaseInsensitive) {
            $currentValue = $this->makeCaseInsensitive($currentValue);
            $valueToPass = $valueToPass === null ? null : $this->makeCaseInsensitive($valueToPass);
        }
        // Catch any possible validator exceptions to avoid inverting result that failed due to
        // wrong data being passed to true.
        try {
            $result = $callback($currentValue, $valueToPass);

            $isInverted = method_exists($constraint, 'isInverted') && $constraint->isInverted();
            if ($isInverted) {
                $result = !$result;
            }
        } catch (OperatorValidatorException $exception) {
            $result = false;
        }
        return $result;
    }

    /**
     * @template T of string|array<mixed>
     *
     * @param string|mixed[] $value
     *
     * @return T
     *
     * @noinspection PhpDocSignatureInspection
     */
    private function makeCaseInsensitive($value)
    {
        if (is_string($value)) {
            return mb_strtolower($value);
        }

        $result = [];
        foreach ($value as $key => $item) {
            assert(is_array($item) || is_string($item));
            $result[$key] = $this->makeCaseInsensitive($item);
        }

        return $result;
    }

    private function getValidationCallback(string $operator): callable
    {
        switch ($operator) {
            case ConstraintOperator::IN_LIST:
                return new InListOperatorValidator();
            case ConstraintOperator::NOT_IN_LIST:
                return new NotInListOperatorValidator();
            case ConstraintOperator::STRING_STARTS_WITH:
                return new StringStartsWithOperatorValidator();
            case ConstraintOperator::STRING_ENDS_WITH:
                return new StringEndsWithOperatorValidator();
            case ConstraintOperator::STRING_CONTAINS:
                return new StringContainsOperatorValidator();
            case ConstraintOperator::NUMBER_EQUALS:
                return new NumberEqualsOperatorValidator();
            case ConstraintOperator::NUMBER_GREATER_THAN:
                return new NumberGreaterThanOperatorValidator();
            case ConstraintOperator::NUMBER_GREATER_THAN_OR_EQUALS:
                return new NumberGreaterThanOrEqualsOperatorValidator();
            case ConstraintOperator::NUMBER_LOWER_THAN:
                return new NumberLowerThanOperatorValidator();
            case ConstraintOperator::NUMBER_LOWER_THAN_OR_EQUALS:
                return new NumberLowerThanOrEqualsOperatorValidator();
            case ConstraintOperator::DATE_AFTER:
                return new DateAfterOperatorValidator();
            case ConstraintOperator::DATE_BEFORE:
                return new DateBeforeOperatorValidator();
            case ConstraintOperator::VERSION_EQUALS:
                return new VersionEqualsOperatorValidator();
            case ConstraintOperator::VERSION_GREATER_THAN:
                return new VersionGreaterThanOperatorValidator();
            case ConstraintOperator::VERSION_LOWER_THAN:
                return new VersionLowerThanOperatorValidator();
            default:
                return fn () => false;
        }
    }
}
