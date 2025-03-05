<?php

namespace Unleash\Client\DTO;

use JsonSerializable;
use Override;
use Unleash\Client\Enum\Stickiness;

final class DefaultProxyFeature implements ProxyFeature, JsonSerializable
{
    private readonly string $name;

    private readonly bool $enabled;

    private readonly Variant $variant;

    private readonly bool $impressionData;

    /**
     * @param array{
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
     * } $response
     */
    public function __construct(array $response)
    {
        // This is validated elsewhere, this should only happen if a consumer
        // tries to new this up manually and isn't interesting to tests

        // @codeCoverageIgnoreStart
        assert(
            isset($response['name'], $response['enabled'], $response['variant'])
            && (isset($response['impressionData']) || isset($response['impression_data']))
        );
        // @codeCoverageIgnoreEnd

        $this->name = $response['name'];
        $this->enabled = $response['enabled'];
        $this->impressionData = $response['impressionData'] ?? $response['impression_data'] ?? false;

        $payload = null;

        if (isset($response['variant']['payload']['type'], $response['variant']['payload']['value'])) {
            $payload = new DefaultVariantPayload($response['variant']['payload']['type'], $response['variant']['payload']['value']);
        }

        $this->variant = new DefaultVariant($response['variant']['name'], $response['variant']['enabled'], 0, Stickiness::DEFAULT, $payload);
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    #[Override]
    public function getVariant(): Variant
    {
        return $this->variant;
    }

    #[Override]
    public function hasImpressionData(): bool
    {
        return $this->impressionData;
    }

    /**
     * @return array<mixed>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'enabled' => $this->enabled,
            'variant' => $this->variant,
            'impression_data' => $this->impressionData, // deprecated
            'impressionData' => $this->impressionData, // if you were reading the snake then you should read the camel
        ];
    }
}
