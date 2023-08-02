<?php

namespace Unleash\Client\Configuration;

use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Pure;
use LogicException;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unleash\Client\Bootstrap\BootstrapHandler;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Bootstrap\DefaultBootstrapHandler;
use Unleash\Client\Bootstrap\EmptyBootstrapProvider;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;
use Unleash\Client\ContextProvider\SettableUnleashContextProvider;
use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Helper\EventDispatcher;

final class UnleashConfiguration
{
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        private string $url,
        private string $appName,
        private string $instanceId,
        private ?CacheInterface $cache = null,
        private int $ttl = 30,
        private int $metricsInterval = 30_000,
        private bool $metricsEnabled = true,
        private array $headers = [],
        private bool $autoRegistrationEnabled = true,
        // todo remove in next major version
        ?Context $defaultContext = null,
        // todo remove nullability in next major version
        private ?UnleashContextProvider $contextProvider = null,
        // todo remove nullability in next major version
        private ?BootstrapHandler $bootstrapHandler = null,
        // todo remove nullability in next major version
        private ?BootstrapProvider $bootstrapProvider = null,
        private bool $fetchingEnabled = true,
        // todo remove nullability in next major version
        private ?EventDispatcherInterface $eventDispatcher = null,
        private int $staleTtl = 30 * 60,
        private ?CacheInterface $staleCache = null,
        private ?string $proxyKey = null,
    ) {
        $this->contextProvider ??= new DefaultUnleashContextProvider();
        if ($defaultContext !== null) {
            $this->setDefaultContext($defaultContext);
        }
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

    public function getUrl(): string
    {
        $url = $this->url;
        if (!str_ends_with($url, '/')) {
            $url .= '/';
        }

        return $url;
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

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setAppName(string $appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    public function setInstanceId(string $instanceId): self
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
        return $this->headers;
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

    /**
     * @todo remove on next major version
     */
    #[Deprecated(reason: 'Support for context provider was added, default context logic should be handled in a provider')]
    public function setDefaultContext(?Context $defaultContext): self
    {
        if ($this->getContextProvider() instanceof SettableUnleashContextProvider) {
            $this->getContextProvider()->setDefaultContext($defaultContext ?? new UnleashContext());
        } else {
            throw new LogicException("Default context cannot be set via configuration for a context provider that doesn't implement SettableUnleashContextProvider");
        }

        return $this;
    }

    public function getContextProvider(): UnleashContextProvider
    {
        assert($this->contextProvider !== null);

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
        return $this->bootstrapHandler ?? new DefaultBootstrapHandler();
    }

    public function setBootstrapHandler(BootstrapHandler $bootstrapHandler): self
    {
        $this->bootstrapHandler = $bootstrapHandler;

        return $this;
    }

    #[Pure]
    public function getBootstrapProvider(): BootstrapProvider
    {
        return $this->bootstrapProvider ?? new EmptyBootstrapProvider();
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

    #[Deprecated('This method has been deprecated and will be removed in next major version')]
    public function getEventDispatcher(): EventDispatcher
    {
        if ($this->eventDispatcher === null) {
            return new EventDispatcher(null);
        }
        if ($this->eventDispatcher instanceof EventDispatcher) {
            return $this->eventDispatcher;
        }

        return new EventDispatcher($this->eventDispatcher);
    }

    /**
     * @internal
     */
    public function getEventDispatcherOrNull(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): self
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
}
