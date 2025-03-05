<?php

namespace Unleash\Client\Metrics;

use Override;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Helper\StringStream;
use Unleash\Client\Unleash;

final class DefaultMetricsSender implements MetricsSender
{
    /**
     * @readonly
     */
    private ClientInterface $httpClient;
    /**
     * @readonly
     */
    private RequestFactoryInterface $requestFactory;
    /**
     * @readonly
     */
    private UnleashConfiguration $configuration;
    public function __construct(ClientInterface $httpClient, RequestFactoryInterface $requestFactory, UnleashConfiguration $configuration)
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->configuration = $configuration;
    }
    public function sendMetrics(MetricsBucket $bucket): void
    {
        if (!$this->configuration->isMetricsEnabled() || !$this->configuration->isFetchingEnabled()) {
            return;
        }
        $request = $this->requestFactory
            ->createRequest('POST', $this->configuration->getMetricsUrl())
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Unleash-Interval', (string) $this->configuration->getMetricsInterval())
            ->withBody(new StringStream(json_encode([
                'appName' => $this->configuration->getAppName(),
                'instanceId' => $this->configuration->getInstanceId(),
                'bucket' => $bucket->jsonSerialize(),
                'platformName' => PHP_SAPI,
                'platformVersion' => PHP_VERSION,
                'yggdrasilVersion' => null,
                'specVersion' => Unleash::SPECIFICATION_VERSION,
            ], JSON_THROW_ON_ERROR)));
        foreach ($this->configuration->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        try {
            $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            // ignore the error
        }
    }
}
