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
     * @param array<string,string> $headers
     */
    public function __construct(
        private string|Stringable $url,
        private string|Stringable $appName,
        private string|Stringable $instanceId,
        private ?CacheInterface $cache = null,
        private int $ttl = 15,
        private int $metricsInterval = 60_000,
        private bool $metricsEnabled = true,
        private array $headers = [],
        private bool $autoRegistrationEnabled = true,
        private ?UnleashContextProvider $contextProvider = null,
        private ?BootstrapHandler $bootstrapHandler = null,
        private ?BootstrapProvider $bootstrapProvider = null,
        private bool $fetchingEnabled = true,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private int $staleTtl = 30 * 60,
        private ?CacheInterface $staleCache = null,
        private ?string $proxyKey = null,
        private ?CacheInterface $metricsCache = null,
        private ?MetricsBucketSerializer $metricsBucketSerializer = null,
        /**
         * SDK identifier in a format of `unleash-client-<language-or-framework>:<semver>`.
         */
        private string $sdkVersion = Unleash::SDK_NAME . ':' . Unleash::SDK_VERSION,
    ) {
        $this->contextProvider = $contextProvider ?? new DefaultUnleashContextProvider();
        $this->bootstrapHandler = $bootstrapHandler ?? new DefaultBootstrapHandler();
        $this->bootstrapProvider = $bootstrapProvider ?? new EmptyBootstrapProvider();
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
        $this->metricsBucketSerializer = $metricsBucketSerializer ?? new DefaultMetricsBucketSerializer();
        $this->contextProvider ??= new DefaultUnleashContextProvider();
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

    public function setUrl(string|Stringable $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setAppName(string|Stringable $appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    public function setInstanceId(string|Stringable $instanceId): self
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

    #[Pure]
    public function getBootstrapHandler(): BootstrapHandler
    {
        return $this->bootstrapHandler;
    }

    public function setBootstrapHandler(BootstrapHandler $bootstrapHandler): self
    {
        $this->bootstrapHandler = $bootstrapHandler;

        return $this;
    }

    #[Pure]
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
