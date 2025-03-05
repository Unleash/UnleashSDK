<?php

namespace Unleash\Client\Strategy;

use JetBrains\PhpStorm\Deprecated;
use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\DefaultStrategy;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Enum\Stickiness;

final class GradualRolloutRandomStrategyHandler extends AbstractStrategyHandler
{
    /**
     * @readonly
     */
    private GradualRolloutStrategyHandler $rolloutStrategyHandler;
    public function __construct(GradualRolloutStrategyHandler $rolloutStrategyHandler)
    {
        $this->rolloutStrategyHandler = $rolloutStrategyHandler;
    }
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        $transformedStrategy = new DefaultStrategy(
            $this->getStrategyName(),
            [
                'stickiness' => Stickiness::RANDOM,
                'groupId' => $strategy->getParameters()['groupId'] ?? '',
                'rollout' => $strategy->getParameters()['percentage'],
            ]
        );
        return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
    }
    public function getStrategyName(): string
    {
        return 'gradualRolloutRandom';
    }
}
