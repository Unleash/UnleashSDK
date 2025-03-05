<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultFeature implements Feature
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
     * @var iterable<Strategy>
     * @readonly
     */
    private $strategies;
    /**
     * @var array<Variant>
     * @readonly
     */
    private $variants = [];
    /**
     * @readonly
     * @var bool
     */
    private $impressionData = false;
    /**
     * @var array<FeatureDependency>
     * @readonly
     */
    private $dependencies = [];
    /**
     * @param iterable<Strategy>       $strategies
     * @param array<Variant>           $variants
     * @param array<FeatureDependency> $dependencies
     */
    public function __construct(string $name, bool $enabled, iterable $strategies, array $variants = [], bool $impressionData = false, array $dependencies = [])
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->strategies = $strategies;
        $this->variants = $variants;
        $this->impressionData = $impressionData;
        $this->dependencies = $dependencies;
    }
    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return iterable<Strategy>
     */
    public function getStrategies(): iterable
    {
        return $this->strategies;
    }

    /**
     * @return array<Variant>
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

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
