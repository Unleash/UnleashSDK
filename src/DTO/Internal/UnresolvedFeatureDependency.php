<?php

namespace Unleash\Client\DTO\Internal;

use Override;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\FeatureDependency;
use Unleash\Client\DTO\Variant;

/**
 * @internal
 */
final class UnresolvedFeatureDependency implements FeatureDependency
{
    /**
     * @readonly
     * @var \Unleash\Client\DTO\Feature
     */
    private $feature;
    /**
     * @readonly
     * @var bool
     */
    private $expectedState;
    /**
     * @var array<Variant>|null
     * @readonly
     */
    private $requiredVariants;
    /**
     * @param array<Variant>|null $requiredVariants
     */
    public function __construct(Feature $feature, bool $expectedState, ?array $requiredVariants)
    {
        $this->feature = $feature;
        $this->expectedState = $expectedState;
        $this->requiredVariants = $requiredVariants;
    }
    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function getExpectedState(): bool
    {
        return $this->expectedState;
    }

    public function getRequiredVariants(): ?array
    {
        return $this->requiredVariants;
    }

    public function isResolved(): bool
    {
        return false;
    }
}
