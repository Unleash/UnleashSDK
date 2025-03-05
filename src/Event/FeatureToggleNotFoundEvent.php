<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;

final class FeatureToggleNotFoundEvent extends AbstractEvent
{
    /**
     * @internal
     */
    public function __construct(
        private Context $context,
        private string $featureName,
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getFeatureName(): string
    {
        return $this->featureName;
    }
}
