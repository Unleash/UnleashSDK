<?php

namespace Unleash\Client\Metrics;

use DateTimeImmutable;
use Override;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultVariant;

final class DefaultMetricsBucketSerializer implements MetricsBucketSerializer
{
    public function serialize(MetricsBucket $bucket): string
    {
        $serialized = $bucket->getStartDate()->getTimestamp() . ';';
        if (!count($bucket->getToggles())) {
            $serialized .= ';';
        }
        foreach ($bucket->getToggles() as $toggle) {
            $variantName = (($nullsafeVariable1 = $toggle->getVariant()) ? $nullsafeVariable1->getName() : null) ?? '~';
            $serialized .= "{$toggle->getFeature()->getName()}:";
            $serialized .= $toggle->isSuccess() ? '1' : '0';
            $serialized .= ":{$variantName},";
        }
        $serialized = substr($serialized, 0, -1);
        $serialized .= ';';
        $serialized .= (($nullsafeVariable2 = $bucket->getEndDate()) ? $nullsafeVariable2->getTimestamp() : null) ?? '~';
        return $serialized;
    }

    public function deserialize(string $serialized): MetricsBucket
    {
        [$startDate, $toggles, $endDate] = explode(';', $serialized);
        $startDate = (new DateTimeImmutable())->setTimestamp((int) $startDate);
        $endDate = $endDate === '~' ? null : (new DateTimeImmutable())->setTimestamp((int) $endDate);
        $toggles = array_filter(explode(',', $toggles));
        $bucket = new MetricsBucket($startDate, $endDate);
        foreach ($toggles as $toggle) {
            [$name, $enabled, $variant] = explode(':', $toggle);
            $enabled = $enabled === '1';
            $variant = $variant === '~' ? null : new DefaultVariant($variant, true);
            $bucket->addToggle(new MetricsBucketToggle(
                new DefaultFeature($name, true, []),
                $enabled,
                $variant,
            ));
        }
        return $bucket;
    }
}
