<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;
use JsonException;
use LogicException;
use Override;
use Unleash\Client\Enum\VariantPayloadType;

final class DefaultVariantPayload implements VariantPayload
{
    /**
     * @readonly
     * @var string
     */
    private $type;
    /**
     * @readonly
     * @var string
     */
    private $value;
    public function __construct(
        #[\JetBrains\PhpStorm\ExpectedValues(valuesFromClass: \Unleash\Client\Enum\VariantPayloadType::class)]
        string $type,
        string $value
    )
    {
        $this->type = $type;
        $this->value = $value;
    }
    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @throws JsonException
     *
     * @return array<mixed>
     */
    public function fromJson(): array
    {
        if ($this->type !== VariantPayloadType::JSON) {
            throw new LogicException(
                sprintf("Only payloads of type '%s' can be converted from json, this payload has type '%s'", VariantPayloadType::JSON, $this->type)
            );
        }
        return (array) json_decode($this->value, true, 512, 0);
    }

    /**
     * @return array<string>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->getValue(),
        ];
    }
}
