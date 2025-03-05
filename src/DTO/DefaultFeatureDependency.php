<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultFeatureDependency implements FeatureDependency
{
    /**
     * @param array<Variant>|null $requiredVariants
     */
    public function __construct(
        private readonly ?Feature $feature,
        private readonly bool $expectedState,
        private readonly ?array $requiredVariants,
    ) {
    }

    #[Override]
    public function getFeature(): ?Feature
    {
        return $this->feature;
    }

    #[Override]
    public function getExpectedState(): bool
    {
        return $this->expectedState;
    }

    #[Override]
    public function getRequiredVariants(): ?array
    {
        return $this->requiredVariants;
    }

    #[Override]
    public function isResolved(): bool
    {
        return true;
    }
}
