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
    /**
     * @readonly
     */
    private CacheInterface $cache;
    /**
     * @readonly
     */
    private CacheInterface $staleCache;
    /**
     * @readonly
     */
    private ClientInterface $httpClient;
    /**
     * @readonly
     */
    private ?MetricsSender $metricsSender;
    /**
     * @readonly
     */
    private CacheInterface $metricsCache;
    /**
     * @readonly
     */
    private RequestFactoryInterface $requestFactory;
    /**
     * @readonly
     */
    private StickinessCalculator $stickinessCalculator;
    /**
     * @readonly
     */
    private ?UnleashConfiguration $configuration;
    /**
     * @readonly
     */
    private UnleashContextProvider $contextProvider;
    /**
     * @readonly
     */
    private BootstrapHandler $bootstrapHandler;
    /**
     * @readonly
     */
    private BootstrapProvider $bootstrapProvider;
    /**
     * @readonly
     */
    private EventDispatcherInterface $eventDispatcher;
    /**
     * @readonly
     */
    private MetricsBucketSerializer $metricsBucketSerializer;
    public function __construct(CacheInterface $cache, CacheInterface $staleCache, ClientInterface $httpClient, ?MetricsSender $metricsSender, CacheInterface $metricsCache, RequestFactoryInterface $requestFactory, StickinessCalculator $stickinessCalculator, ?UnleashConfiguration $configuration, UnleashContextProvider $contextProvider, BootstrapHandler $bootstrapHandler, BootstrapProvider $bootstrapProvider, EventDispatcherInterface $eventDispatcher, MetricsBucketSerializer $metricsBucketSerializer)
    {
        $this->cache = $cache;
        $this->staleCache = $staleCache;
        $this->httpClient = $httpClient;
        $this->metricsSender = $metricsSender;
        $this->metricsCache = $metricsCache;
        $this->requestFactory = $requestFactory;
        $this->stickinessCalculator = $stickinessCalculator;
        $this->configuration = $configuration;
        $this->contextProvider = $contextProvider;
        $this->bootstrapHandler = $bootstrapHandler;
        $this->bootstrapProvider = $bootstrapProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->metricsBucketSerializer = $metricsBucketSerializer;
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
