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
    /**
     * @readonly
     * @var \Unleash\Client\Repository\ProxyRepository
     */
    private $repository;
    /**
     * @readonly
     * @var \Unleash\Client\Metrics\MetricsHandler
     */
    private $metricsHandler;
    public function __construct(ProxyRepository $repository, MetricsHandler $metricsHandler)
    {
        $this->repository = $repository;
        $this->metricsHandler = $metricsHandler;
    }
    /**
     * @codeCoverageIgnore
     */
    public function register(): bool
    {
        //This is a no op, since registration is handled by the proxy/edge, this doesn't need coverage
        return false;
    }

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $response = $this->repository->findFeatureByContext($featureName, $context);
        $enabled = $response ? $response->isEnabled() : $default;
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $enabled, []), $enabled);
        return $enabled;
    }

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
