<?php

namespace Unleash\Client\DTO\Internal;

use JetBrains\PhpStorm\ExpectedValues;
use Override;
use Unleash\Client\DTO\Variant;
use Unleash\Client\DTO\VariantPayload;
use Unleash\Client\Enum\Stickiness;

/**
 * @internal
 */
final class UnresolvedVariant implements Variant
{
    public function __construct(
        private string $name,
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    public function isEnabled(): bool
    {
        return false;
    }

    #[Override]
    public function getPayload(): ?VariantPayload
    {
        return null;
    }

    #[Override]
    public function getWeight(): int
    {
        return 0;
    }

    #[Override]
    public function getOverrides(): array
    {
        return [];
    }

    #[ExpectedValues(valuesFromClass: Stickiness::class)]
    #[Override]
    public function getStickiness(): string
    {
        return Stickiness::DEFAULT;
    }

    /**
     * todo Change to null once rector supports it
     *
     * @return null
     *
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    #[Override]
    public function jsonSerialize(): mixed
    {
        return null;
    }
}
