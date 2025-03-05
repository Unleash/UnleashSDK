<?php

namespace Unleash\Client;

use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Repository\ProxyRepository;

final class DefaultProxyUnleash implements Unleash
{
    public function __construct(
        private readonly ProxyRepository $repository,
        private readonly MetricsHandler $metricsHandler,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    #[Override]
    public function register(): bool
    {
        //This is a no op, since registration is handled by the proxy/edge, this doesn't need coverage
        return false;
    }

    #[Override]
    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $response = $this->repository->findFeatureByContext($featureName, $context);
        $enabled = $response ? $response->isEnabled() : $default;
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $enabled, []), $enabled);

        return $enabled;
    }

    #[Override]
    public function getVariant(string $featureName, ?Context $context = null, ?Variant $fallbackVariant = null): Variant
    {
        $variant = $fallbackVariant ?? new DefaultVariant('disabled', false, 0, Stickiness::DEFAULT);

        $response = $this->repository->findFeatureByContext($featureName, $context);

        if ($response !== null) {
            $variant = $response->getVariant();
        }
        $metricVariant = new DefaultVariant($variant->getName(), $variant->isEnabled(), 0, Stickiness::DEFAULT, $variant->getPayload());
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $variant->isEnabled(), []), $variant->isEnabled(), $metricVariant);

        return $variant;
    }
}
