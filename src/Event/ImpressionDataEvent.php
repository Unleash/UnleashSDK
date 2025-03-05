<?php

namespace Unleash\Client\Event;

use DateTimeInterface;
use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\ImpressionDataEventType;

final class ImpressionDataEvent extends AbstractEvent implements JsonSerializable
{
    public function __construct(
        #[ExpectedValues(valuesFromClass: ImpressionDataEventType::class)]private string $eventType,
        private string $eventId,
        private UnleashConfiguration $configuration,
        private Context $context,
        private Feature $feature,
        private ?Variant $variant,
    ) {
    }

    #[ExpectedValues(valuesFromClass: ImpressionDataEventType::class)]
    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * @return array{
     *     currentTime: DateTimeInterface,
     *     userId: string|null,
     *     sessionId: string|null,
     *     remoteAddress: string|null,
     *     environment: string|null,
     *     appName: string,
     *     properties: array<string, string>
     * }
     */
    public function getContext(): array
    {
        return [
            'currentTime' => clone $this->context->getCurrentTime(),
            'userId' => $this->context->getCurrentUserId(),
            'sessionId' => $this->context->getSessionId(),
            'remoteAddress' => $this->context->getIpAddress(),
            'environment' => $this->context->getEnvironment(),
            'appName' => $this->configuration->getAppName(),
            'properties' => $this->context->getCustomProperties(),
        ];
    }

    public function isEnabled(): bool
    {
        return $this->feature->isEnabled();
    }

    public function getFeatureName(): string
    {
        return $this->feature->getName();
    }

    public function getVariant(): ?string
    {
        return $this->variant?->getName();
    }

    /**
     * @return array{
     *     eventType: string,
     *     eventId: string,
     *     context: array{
     *          currentTime: DateTimeInterface,
     *          userId: string|null,
     *          sessionId: string|null,
     *          remoteAddress: string|null,
     *          environment: string|null,
     *          appName: string,
     *          properties: array<string, string>
     *     },
     *     enabled: bool,
     *     featureName: string,
     *     variant?: string
     * }
     */
    #[Override]
    public function jsonSerialize(): array
    {
        $result = [
            'eventType' => $this->getEventType(),
            'eventId' => $this->getEventId(),
            'context' => $this->getContext(),
            'enabled' => $this->isEnabled(),
            'featureName' => $this->getFeatureName(),
        ];

        if ($this->getVariant() !== null) {
            $result['variant'] = $this->getVariant();
        }

        return $result;
    }
}
