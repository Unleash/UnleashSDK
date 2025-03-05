<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use LogicException;
use Unleash\Client\Enum\VariantPayloadType;

interface VariantPayload extends JsonSerializable
{
    public function getType(): string;

    public function getValue(): string;

    /**
     * @throws LogicException
     *
     * @return array<mixed>
     */
    public function fromJson(): array;
}
