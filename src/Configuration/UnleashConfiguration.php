<?php

namespace Unleash\Client\Configuration;

use JetBrains\PhpStorm\Pure;
use LogicException;
use Psr\SimpleCache\CacheInterface;
use Stringable;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unleash\Client\Bootstrap\BootstrapHandler;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Bootstrap\DefaultBootstrapHandler;
use Unleash\Client\Bootstrap\EmptyBootstrapProvider;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;
use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Helper\Url;
use Unleash\Client\Metrics\DefaultMetricsBucketSerializer;
use Unleash\Client\Metrics\MetricsBucketSerializer;
use Unleash\Client\Unleash;

final class UnleashConfiguration
{
    /**
     * @var string|\Stringable
     */
    private $url;
    /**
     * @var string|\Stringable
     */
    private $appName;
    /**
     * @var string|\Stringable
     */
    private $instanceId;
    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $cache;
    /**
     * @var int
     */
    private $ttl = 15;
    /**
     * @var int
     */
    private $metricsInterval = 60000;
    /**
     * @var bool
     */
    private $metricsEnabled = true;
    /**
     * @var array<string, string>
     */
    private $headers = [];
    /**
     * @var bool
     */
    private $autoRegistrationEnabled = true;
    /**
     * @var \Unleash\Client\ContextProvider\UnleashContextProvider
     */
    private $contextProvider;
    /**
     * @var \Unleash\Client\Bootstrap\BootstrapHandler
     */
    private $bootstrapHandler;
    /**
     * @var \Unleash\Client\Bootstrap\BootstrapProvider
     */
    private $bootstrapProvider;
    /**
     * @var bool
     */
    private $fetchingEnabled = true;
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var int
     */
    private $staleTtl = 30 * 60;
    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $staleCache;
    /**
     * @var string|null
     */
    private $proxyKey;
    /**
     * @var \Psr\SimpleCache\CacheInterface|null
     */
    private $metricsCache;
    /**
     * @var \Unleash\Client\Metrics\MetricsBucketSerializer
     */
    private $metricsBucketSerializer;
    /**
     * @var string
     */
    private $sdkVersion = Unleash::SDK_NAME . ':' . Unleash::SDK_VERSION;
    /**
     * @param array<string,string> $headers
     * @param string|\Stringable $url
     * @param string|\Stringable $appName
     * @param string|\Stringable $instanceId
     */
    public function __construct($url, $appName, $instanceId, ?CacheInterface $cache = null, int $ttl = 15, int $metricsInterval = 60000, bool $metricsEnabled = true, array $headers = [], bool $autoRegistrationEnabled = true, UnleashContextProvider $contextProvider = null, BootstrapHandler $bootstrapHandler = null, BootstrapProvider $bootstrapProvider = null, bool $fetchingEnabled = true, EventDispatcherInterface $eventDispatcher = null, int $staleTtl = 30 * 60, ?CacheInterface $staleCache = null, ?string $proxyKey = null, ?CacheInterface $metricsCache = null, MetricsBucketSerializer $metricsBucketSerializer = null, string $sdkVersion = Unleash::SDK_NAME . ':' . Unleash::SDK_VERSION)
    {
        $contextProvider = $contextProvider ?? new DefaultUnleashContextProvider();
        $bootstrapHandler = $bootstrapHandler ?? new DefaultBootstrapHandler();
        $bootstrapProvider = $bootstrapProvider ?? new EmptyBootstrapProvider();
        $eventDispatcher = $eventDispatcher ?? new EventDispatcher();
        $metricsBucketSerializer = $metricsBucketSerializer ?? new DefaultMetricsBucketSerializer();
        $this->url = $url;
        $this->appName = $appName;
        $this->instanceId = $instanceId;
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->metricsInterval = $metricsInterval;
        $this->metricsEnabled = $metricsEnabled;
        $this->headers = $headers;
        $this->autoRegistrationEnabled = $autoRegistrationEnabled;
        $this->contextProvider = $contextProvider;
        $this->bootstrapHandler = $bootstrapHandler;
        $this->bootstrapProvider = $bootstrapProvider;
        $this->fetchingEnabled = $fetchingEnabled;
        $this->eventDispatcher = $eventDispatcher;
        $this->staleTtl = $staleTtl;
        $this->staleCache = $staleCache;
        $this->proxyKey = $proxyKey;
        $this->metricsCache = $metricsCache;
        $this->metricsBucketSerializer = $metricsBucketSerializer;
        /**
         * SDK identifier in a format of `unleash-client-<language-or-framework>:<semver>`.
         */
        $this->sdkVersion = $sdkVersion;
        $this->contextProvider = $this->contextProvider ?? new DefaultUnleashContextProvider();
    }
    public function getCache(): CacheInterface
    {
        if ($this->cache === null) {
            throw new LogicException('Cache handler is not set');
        }

        return $this->cache;
    }

    public function getStaleCache(): CacheInterface
    {
        return $this->staleCache ?? $this->getCache();
    }

    public function getMetricsCache(): CacheInterface
    {
        return $this->metricsCache ?? $this->getCache();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getProxyKey(): ?string
    {
        return $this->proxyKey;
    }

    public function setProxyKey(?string $proxyKey): self
    {
        $this->proxyKey = $proxyKey;

        return $this;
    }

    public function getMetricsUrl(): string
    {
        return $this->proxyKey !== null
            ? Url::appendPath($this->getUrl(), 'frontend/client/metrics')
            : Url::appendPath($this->getUrl(), 'client/metrics');
    }

    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    public function setStaleCache(?CacheInterface $cache): self
    {
        $this->staleCache = $cache;

        return $this;
    }

    public function setMetricsCache(?CacheInterface $cache): self
    {
        $this->metricsCache = $cache;

        return $this;
    }

    public function setTtl(int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function getMetricsInterval(): int
    {
        return $this->metricsInterval;
    }

    public function isMetricsEnabled(): bool
    {
        return $this->metricsEnabled;
    }

    /**
     * @param string|\Stringable $url
     */
    public function setUrl($url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param string|\Stringable $appName
     */
    public function setAppName($appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * @param string|\Stringable $instanceId
     */
    public function setInstanceId($instanceId): self
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    public function setMetricsInterval(int $metricsInterval): self
    {
        $this->metricsInterval = $metricsInterval;

        return $this;
    }

    public function setMetricsEnabled(bool $metricsEnabled): self
    {
        $this->metricsEnabled = $metricsEnabled;

        return $this;
    }

    /**
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        $identificationHeaders = [
            'unleash-appname' => $this->getAppName(),
            'unleash-sdk' => $this->getSdkVersion(),
        ];

        return array_merge($this->headers, $identificationHeaders);
    }

    /**
     * @param array<string,string> $headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function isAutoRegistrationEnabled(): bool
    {
        return $this->autoRegistrationEnabled;
    }

    public function setAutoRegistrationEnabled(bool $autoRegistrationEnabled): self
    {
        $this->autoRegistrationEnabled = $autoRegistrationEnabled;

        return $this;
    }

    public function getDefaultContext(): Context
    {
        return $this->getContextProvider()->getContext();
    }

    public function getContextProvider(): UnleashContextProvider
    {
        return $this->contextProvider;
    }

    public function setContextProvider(UnleashContextProvider $contextProvider): self
    {
        $this->contextProvider = $contextProvider;

        return $this;
    }

    public function getBootstrapHandler(): BootstrapHandler
    {
        return $this->bootstrapHandler;
    }

    public function setBootstrapHandler(BootstrapHandler $bootstrapHandler): self
    {
        $this->bootstrapHandler = $bootstrapHandler;

        return $this;
    }

    public function getBootstrapProvider(): BootstrapProvider
    {
        return $this->bootstrapProvider;
    }

    public function setBootstrapProvider(BootstrapProvider $bootstrapProvider): self
    {
        $this->bootstrapProvider = $bootstrapProvider;

        return $this;
    }

    public function isFetchingEnabled(): bool
    {
        return $this->fetchingEnabled;
    }

    public function setFetchingEnabled(bool $fetchingEnabled): self
    {
        $this->fetchingEnabled = $fetchingEnabled;

        return $this;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function getStaleTtl(): int
    {
        return $this->staleTtl;
    }

    public function setStaleTtl(int $staleTtl): self
    {
        $this->staleTtl = $staleTtl;

        return $this;
    }

    public function getMetricsBucketSerializer(): MetricsBucketSerializer
    {
        return $this->metricsBucketSerializer;
    }

    public function setMetricsBucketSerializer(MetricsBucketSerializer $metricsBucketSerializer): self
    {
        $this->metricsBucketSerializer = $metricsBucketSerializer;

        return $this;
    }

    public function getSdkVersion(): string
    {
        return $this->sdkVersion;
    }
}
