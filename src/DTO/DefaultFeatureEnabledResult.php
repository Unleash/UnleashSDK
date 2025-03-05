<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultFeatureEnabledResult implements FeatureEnabledResult
{
    public function __construct(
        private readonly bool $isEnabled = false,
        private readonly ?Strategy $strategy = null,
    ) {
    }

    #[Override]
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    #[Override]
    public function getStrategy(): ?Strategy
    {
        return $this->strategy;
    }
}
