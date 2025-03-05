<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;

final class FeatureToggleMissingStrategyHandlerEvent extends AbstractEvent
{
    /**
     * @readonly
     * @var \Unleash\Client\Configuration\Context
     */
    private $context;
    /**
     * @readonly
     * @var \Unleash\Client\DTO\Feature
     */
    private $feature;
    /**
     * @internal
     */
    public function __construct(Context $context, Feature $feature)
    {
        $this->context = $context;
        $this->feature = $feature;
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
