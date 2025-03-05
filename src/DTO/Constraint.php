<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use Unleash\Client\Enum\ConstraintOperator;

interface Constraint
{
    public function getContextName(): string;

    public function getOperator(): string;

    /**
     * @return array<string>|null
     */
    public function getValues(): ?array;

    public function getSingleValue(): ?string;

    public function isInverted(): bool;

    public function isCaseInsensitive(): bool;
}
