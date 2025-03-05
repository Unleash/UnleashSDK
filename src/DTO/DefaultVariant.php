<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use Override;
use Unleash\Client\Enum\Stickiness;

final class DefaultVariant implements Variant
{
    /**
     * @readonly
     * @var string
     */
    private $name;
    /**
     * @readonly
     * @var bool
     */
    private $enabled;
    /**
     * @readonly
     * @var int
     */
    private $weight = 0;
    /**
     * @readonly
     * @var string
     */
    private $stickiness = Stickiness::DEFAULT;
    /**
     * @readonly
     * @var \Unleash\Client\DTO\VariantPayload|null
     */
    private $payload;
    /**
     * @var array<VariantOverride>
     * @readonly
     */
    private $overrides;
    /**
     * @param array<VariantOverride> $overrides
     */
    public function __construct(
        string $name,
        bool $enabled,
        int $weight = 0,
        #[\JetBrains\PhpStorm\ExpectedValues(valuesFromClass: \Unleash\Client\Enum\Stickiness::class)]
        string $stickiness = Stickiness::DEFAULT,
        ?VariantPayload $payload = null,
        ?array $overrides = null
    )
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->weight = $weight;
        $this->stickiness = $stickiness;
        $this->payload = $payload;
        $this->overrides = $overrides;
    }
    public function getName(): string
    {
        return $this->name;
    }

    public function getPayload(): ?VariantPayload
    {
        return $this->payload;
    }

    /**
     * @phpstan-return array<string|bool|array<string>>
     */
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->name,
            'enabled' => $this->enabled,
        ];
        if ($this->payload !== null) {
            $result['payload'] = $this->payload->jsonSerialize();
            assert(is_array($result['payload']));
        }
        return $result;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<VariantOverride>
     */
    public function getOverrides(): array
    {
        return $this->overrides ?? [];
    }

    public function getStickiness(): string
    {
        return $this->stickiness;
    }
}
