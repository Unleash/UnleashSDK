<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use Override;
use Unleash\Client\Enum\ConstraintOperator;

final class DefaultConstraint implements Constraint
{
    /**
     * @param array<string> $values
     */
    public function __construct(
        private string $contextName,
        #[ExpectedValues(valuesFromClass: ConstraintOperator::class)]
        private string $operator,
        private ?array $values = null,
        private ?string $singleValue = null,
        private bool $inverted = false,
        private bool $caseInsensitive = false,
    ) {
    }

    #[Override]
    public function getContextName(): string
    {
        return $this->contextName;
    }

    #[Override]
    #[ExpectedValues(valuesFromClass: ConstraintOperator::class)]
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return array<string>|null
     */
    #[Override]
    public function getValues(): ?array
    {
        return $this->values;
    }

    #[Override]
    public function getSingleValue(): ?string
    {
        return $this->singleValue;
    }

    #[Override]
    public function isInverted(): bool
    {
        return $this->inverted;
    }

    #[Override]
    public function isCaseInsensitive(): bool
    {
        return $this->caseInsensitive;
    }
}
