<?php

namespace Unleash\Client\Stickiness;

use lastguest\Murmur;
use Override;

final class MurmurHashCalculator implements StickinessCalculator
{
    public function calculate(string $id, string $groupId, int $normalizer = 100, int $seed = 0): int
    {
        return Murmur::hash3_int("{$groupId}:{$id}", $seed) % $normalizer + 1;
    }
}
