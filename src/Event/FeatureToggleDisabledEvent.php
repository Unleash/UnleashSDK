<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;

final class FeatureToggleDisabledEvent extends AbstractEvent
{
    /**
     * @readonly
     */
    private Feature $feature;
    /**
     * @readonly
     */
    private Context $context;
    /**
     * @internal
     */
    public function __construct(Feature $feature, Context $context)
    {
        $this->feature = $feature;
        $this->context = $context;
    }
    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
