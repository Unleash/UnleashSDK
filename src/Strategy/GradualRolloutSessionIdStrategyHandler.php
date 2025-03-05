<?php

namespace Unleash\Client\Strategy;

use JetBrains\PhpStorm\Deprecated;
use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\DefaultStrategy;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Enum\Stickiness;

#[Deprecated(reason: 'The strategy has been deprecated, please use Gradual Rollout (flexibleRollout)')]
final class GradualRolloutSessionIdStrategyHandler extends AbstractStrategyHandler
{
    public function __construct(private GradualRolloutStrategyHandler $rolloutStrategyHandler)
    {
    }

    #[Override]
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        $transformedStrategy = new DefaultStrategy(
            $this->getStrategyName(),
            [
                'stickiness' => Stickiness::SESSION_ID,
                'groupId' => $strategy->getParameters()['groupId'],
                'rollout' => $strategy->getParameters()['percentage'],
            ]
        );

        return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
    }

    #[Override]
    public function getStrategyName(): string
    {
        return 'gradualRolloutSessionId';
    }
}
