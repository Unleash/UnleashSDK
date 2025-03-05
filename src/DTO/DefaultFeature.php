<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultFeature implements Feature
{
    /**
     * @param iterable<Strategy>       $strategies
     * @param array<Variant>           $variants
     * @param array<FeatureDependency> $dependencies
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $enabled,
        private readonly iterable $strategies,
        private readonly array $variants = [],
        private readonly bool $impressionData = false,
        private readonly array $dependencies = [],
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
        return $this->enabled;
    }

    /**
     * @return iterable<Strategy>
     */
    #[Override]
    public function getStrategies(): iterable
    {
        return $this->strategies;
    }

    /**
     * @return array<Variant>
     */
    #[Override]
    public function getVariants(): array
    {
        return $this->variants;
    }

    #[Override]
    public function hasImpressionData(): bool
    {
        return $this->impressionData;
    }

    /**
     * @return array<FeatureDependency>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
