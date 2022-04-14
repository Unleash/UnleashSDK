<?php

namespace Unleash\Client\Configuration;

use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Pure;
use LogicException;
use Psr\SimpleCache\CacheInterface;
use Unleash\Client\Bootstrap\BootstrapHandler;
use Unleash\Client\Bootstrap\BootstrapProvider;
use Unleash\Client\Bootstrap\DefaultBootstrapHandler;
use Unleash\Client\Bootstrap\EmptyBootstrapProvider;
use Unleash\Client\ContextProvider\DefaultUnleashContextProvider;
use Unleash\Client\ContextProvider\SettableUnleashContextProvider;
use Unleash\Client\ContextProvider\UnleashContextProvider;

final class UnleashConfiguration
{
    private string $url;
    private string $appName;
    private string $instanceId;
    private ?CacheInterface $cache = null;
    private int $ttl = 30;
    private int $metricsInterval = 30_000;
    private bool $metricsEnabled = true;
    /**
     * @var array<string, string>
     */
    private array $headers = [];
    private bool $autoRegistrationEnabled = true;
    private ?UnleashContextProvider $contextProvider = null;
    private ?BootstrapHandler $bootstrapHandler = null;
    private ?BootstrapProvider $bootstrapProvider = null;
    private bool $fetchingEnabled = true;
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        string $url,
        string $appName,
        string $instanceId,
        ?CacheInterface $cache = null,
        int $ttl = 30,
        int $metricsInterval = 30_000,
        bool $metricsEnabled = true,
        array $headers = [],
        bool $autoRegistrationEnabled = true,
        // todo remove in next major version
        ?Context $defaultContext = null,
        ?UnleashContextProvider $contextProvider = null,
        ?BootstrapHandler $bootstrapHandler = null,
        ?BootstrapProvider $bootstrapProvider = null,
        bool $fetchingEnabled = true
    )
    {
        $this->url = $url;
        $this->appName = $appName;
        $this->instanceId = $instanceId;
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->metricsInterval = $metricsInterval;
        $this->metricsEnabled = $metricsEnabled;
        $this->headers = $headers;
        $this->autoRegistrationEnabled = $autoRegistrationEnabled;
        // todo remove nullability in next major version
        $this->contextProvider = $contextProvider;
        // todo remove nullability in next major version
        $this->bootstrapHandler = $bootstrapHandler;
        // todo remove nullability in next major version
        $this->bootstrapProvider = $bootstrapProvider;
        $this->fetchingEnabled = $fetchingEnabled;
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

    public function getUrl(): string
    {
        $url = $this->url;
        if (substr_compare($url, '/', -strlen('/')) !== 0) {
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

    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;

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

    /**
     * @codeCoverageIgnore
     */
    public function setAppName(string $appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
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
}
