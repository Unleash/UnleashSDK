<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultFeatureEnabledResult implements FeatureEnabledResult
{
    /**
     * @readonly
     */
    private bool $isEnabled = false;
    /**
     * @readonly
     */
    private ?Strategy $strategy = null;
    public function __construct(bool $isEnabled = false, ?Strategy $strategy = null)
    {
        $this->isEnabled = $isEnabled;
        $this->strategy = $strategy;
    }
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getStrategy(): ?Strategy
    {
        return $this->strategy;
    }
}
