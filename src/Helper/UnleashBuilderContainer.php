<?php

namespace Unleash\Client\Helper;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unleash\Client\Bootstrap\BootstrapHandler;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Metrics\MetricsBucketSerializer;
use Unleash\Client\Metrics\MetricsSender;
use Unleash\Client\Stickiness\StickinessCalculator;

/**
 * @internal
 */
final class UnleashBuilderContainer
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly CacheInterface $staleCache,
        private readonly ClientInterface $httpClient,
        private readonly ?MetricsSender $metricsSender,
        private readonly CacheInterface $metricsCache,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StickinessCalculator $stickinessCalculator,
        private readonly ?UnleashConfiguration $configuration,
        private readonly UnleashContextProvider $contextProvider,
        private readonly BootstrapHandler $bootstrapHandler,
        private readonly BootstrapProvider $bootstrapProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MetricsBucketSerializer $metricsBucketSerializer,
    ) {
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getStaleCache(): CacheInterface
    {
        return $this->staleCache;
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    public function getMetricsSender(): ?MetricsSender
    {
        return $this->metricsSender;
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    public function getStickinessCalculator(): StickinessCalculator
    {
        return $this->stickinessCalculator;
    }

    public function getConfiguration(): ?UnleashConfiguration
    {
        return $this->configuration;
    }

    public function getMetricsCache(): CacheInterface
    {
        return $this->metricsCache;
    }

    public function getContextProvider(): UnleashContextProvider
    {
        return $this->contextProvider;
    }

    public function getBootstrapHandler(): BootstrapHandler
    {
        return $this->bootstrapHandler;
    }

    public function getBootstrapProvider(): BootstrapProvider
    {
        return $this->bootstrapProvider;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getMetricsBucketSerializer(): MetricsBucketSerializer
    {
        return $this->metricsBucketSerializer;
    }
}
