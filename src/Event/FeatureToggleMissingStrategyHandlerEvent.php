<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;

final class FeatureToggleMissingStrategyHandlerEvent extends AbstractEvent
{
    /**
     * @internal
     */
    public function __construct(
        private Context $context,
        private Feature $feature,
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getFeature(): Feature
    {
        return $this->feature;
    }
}
