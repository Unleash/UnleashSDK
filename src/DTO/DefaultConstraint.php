<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use Override;
use Unleash\Client\Enum\ConstraintOperator;

final class DefaultConstraint implements Constraint
{
    /**
     * @readonly
     */
    private string $contextName;
    /**
     * @readonly
     */
    private string $operator;
    /**
     * @var array<string>
     * @readonly
     */
    private ?array $values = null;
    /**
     * @readonly
     */
    private ?string $singleValue = null;
    /**
     * @readonly
     */
    private bool $inverted = false;
    /**
     * @readonly
     */
    private bool $caseInsensitive = false;
    /**
     * @param array<string> $values
     */
    public function __construct(
        string $contextName,
        #[\JetBrains\PhpStorm\ExpectedValues(valuesFromClass: \Unleash\Client\Enum\ConstraintOperator::class)]
        string $operator,
        ?array $values = null,
        ?string $singleValue = null,
        bool $inverted = false,
        bool $caseInsensitive = false
    )
    {
        $this->contextName = $contextName;
        $this->operator = $operator;
        $this->values = $values;
        $this->singleValue = $singleValue;
        $this->inverted = $inverted;
        $this->caseInsensitive = $caseInsensitive;
    }
    public function getContextName(): string
    {
        return $this->contextName;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return array<string>|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    public function getSingleValue(): ?string
    {
        return $this->singleValue;
    }

    public function isInverted(): bool
    {
        return $this->inverted;
    }

    public function isCaseInsensitive(): bool
    {
        return $this->caseInsensitive;
    }
}
