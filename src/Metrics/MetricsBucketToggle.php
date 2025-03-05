<?php

namespace Unleash\Client\Metrics;

use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;

/**
 * @internal
 */
final class MetricsBucketToggle
{
    /**
     * @readonly
     */
    private Feature $feature;
    /**
     * @readonly
     */
    private bool $success;
    /**
     * @readonly
     */
    private ?Variant $variant = null;
    public function __construct(Feature $feature, bool $success, ?Variant $variant = null)
    {
        $this->feature = $feature;
        $this->success = $success;
        $this->variant = $variant;
    }
    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getVariant(): ?Variant
    {
        return $this->variant;
    }
}
