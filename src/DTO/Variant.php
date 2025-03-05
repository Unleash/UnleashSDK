<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use Unleash\Client\Enum\Stickiness;

interface Variant extends JsonSerializable
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function getPayload(): ?VariantPayload;

    public function getWeight(): int;

    /**
     * @return array<VariantOverride>
     */
    public function getOverrides(): array;

    public function getStickiness(): string;
}
