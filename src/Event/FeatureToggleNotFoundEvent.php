<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;

final class FeatureToggleNotFoundEvent extends AbstractEvent
{
    /**
     * @readonly
     * @var \Unleash\Client\Configuration\Context
     */
    private $context;
    /**
     * @readonly
     * @var string
     */
    private $featureName;
    /**
     * @internal
     */
    public function __construct(Context $context, string $featureName)
    {
        $this->context = $context;
        $this->featureName = $featureName;
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
