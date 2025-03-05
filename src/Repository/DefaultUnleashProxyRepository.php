<?php

namespace Unleash\Client\Repository;

use InvalidArgumentException;
use Override;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\VarExporter\Exception\LogicException;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DTO\DefaultProxyFeature;
use Unleash\Client\DTO\ProxyFeature;
use Unleash\Client\Helper\Url;

final class DefaultUnleashProxyRepository implements ProxyRepository
{
    public function __construct(
        private readonly UnleashConfiguration $configuration,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory
    ) {
    }

    #[Override]
    public function findFeatureByContext(string $featureName, ?Context $context = null): ?ProxyFeature
    {
        $apiKey = $this->configuration->getProxyKey();
        if ($apiKey === null) {
            // The only way we can get here is if this is manually constructed without the builder
            // @codeCoverageIgnoreStart
            throw new LogicException('No api proxy key was specified');
            // @codeCoverageIgnoreEnd
        }

        $cacheKey = $this->getCacheKey($featureName, $context);

        if ($this->configuration->getCache() !== null && $this->configuration->getCache()->has($cacheKey)) {
            $cachedFeature = $this->configuration->getCache()->get($cacheKey);
            if (is_array($cachedFeature)) {
                $featureData = $this->validateResponse($cachedFeature);
                if ($featureData !== null) {
                    return new DefaultProxyFeature($featureData);
                }
            }
        }

        $context ??= new UnleashContext();
        $featureUrl = (string) Url::appendPath($this->configuration->getUrl(), 'frontend/features/' . $featureName);
        $url = $this->addQuery($featureUrl, $this->contextToQueryString($context));

        $request = $this->requestFactory->createRequest('GET', $url)
            ->withHeader('Accept', 'application/json');

        foreach ($this->configuration->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $request = $request->withHeader('Authorization', $apiKey);

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() != 200) {
            return null;
        }

        $body = json_decode($response->getBody(), true);
        if ($body === null) {
            return null;
        }
        if (is_array($body)) {
            $featureData = $this->validateResponse($body);

            if ($featureData !== null) {
                if ($this->configuration->getCache() !== null) {
                    $this->configuration->getCache()->set($cacheKey, $featureData, $this->configuration->getTtl());
                }

                return new DefaultProxyFeature($featureData);
            }
        }

        return null;
    }

    private function getCacheKey(string $featureName, ?Context $context): string
    {
        if ($context === null) {
            return $featureName;
        }
        $contextHash = hash('sha512', ($this->contextToQueryString($context)));

        return $featureName . '-' . $contextHash;
    }

    private function contextToQueryString(Context $context): string
    {
        $query = [];
        $values = [
            'userId' => $context->getCurrentUserId(),
            'sessionId' => $context->getSessionId(),
            'remoteAddress' => $context->getIpAddress(),
        ];

        $values = array_filter($values, fn (?string $value) => $value !== null);
        $properties = array_filter($context->getCustomProperties(), fn (?string $value) => $value !== null);

        foreach ($values as $key => $value) {
            $query[$key] = $value;
        }

        $query['properties'] = [];

        foreach ($properties as $key => $value) {
            $query['properties'][$key] = $value;
        }

        return urldecode(http_build_query($query));
    }

    private function addQuery(string $url, string $query): string
    {
        $urlParts = parse_url($url);

        // The base url is already validated, so this should only happen if a feature toggle has a name
        // that can't form part of a URL but this shouldn't ever happen, given that this is validated
        // by the Unleash server and UI

        // @codeCoverageIgnoreStart
        if ($urlParts === false) {
            throw new InvalidArgumentException('Invalid URL provided');
        }
        // @codeCoverageIgnoreEnd

        if (!isset($urlParts['query'])) {
            $urlParts['query'] = $query;
        }

        return Url::buildUrl($urlParts);
    }

    /**
     * @param array<string, mixed> $response
     *
     * @return array{
     *     name: string,
     *     enabled: bool,
     *     variant: array{
     *         name: string,
     *         enabled: bool,
     *         payload?: array{
     *             type: string,
     *             value: string
     *         }
     *     },
     *     impressionData: bool
     * }|null
     */
    private function validateResponse(array $response): ?array
    {
        // fix impression data:
        if (isset($response['impression_data'])) {
            $response['impressionData'] = $response['impression_data'];
            unset($response['impression_data']);
        }
        if (!isset($response['name'], $response['enabled'], $response['variant'], $response['impressionData'])) {
            return null;
        }

        if (!is_string($response['name']) || !is_bool($response['enabled']) || !is_bool($response['impressionData']) || !is_array($response['variant'])) {
            return null;
        }

        if (!isset($response['variant']['name'], $response['variant']['enabled']) || !is_string($response['variant']['name']) || !is_bool($response['variant']['enabled'])) {
            return null;
        }

        if (isset($response['variant']['payload']) && (!is_array($response['variant']['payload']) || !isset($response['variant']['payload']['type'], $response['variant']['payload']['value']) || !is_string($response['variant']['payload']['type']) || !is_string($response['variant']['payload']['value']))) {
            return null;
        }

        return $response;
    }
}
