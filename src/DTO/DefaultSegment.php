<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultSegment implements Segment
{
    /**
     * @param array<Constraint> $constraints
     */
    public function __construct(
        private int $id,
        private array $constraints,
    ) {
    }

    #[Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[Override]
    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
