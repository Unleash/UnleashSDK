<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultStrategy implements Strategy
{
    /**
     * @readonly
     * @var string
     */
    private $name;
    /**
     * @var array<string, string>
     * @readonly
     */
    private $parameters = [];
    /**
     * @var array<Constraint>
     * @readonly
     */
    private $constraints = [];
    /**
     * @var array<Segment>
     * @readonly
     */
    private $segments = [];
    /**
     * @readonly
     * @var bool
     */
    private $nonexistentSegments = false;
    /**
     * @var array<Variant>
     * @readonly
     */
    private $variants = [];
    /**
     * @param array<string,string> $parameters
     * @param array<Constraint>    $constraints
     * @param array<Segment>       $segments
     * @param array<Variant>       $variants
     */
    public function __construct(string $name, array $parameters = [], array $constraints = [], array $segments = [], bool $nonexistentSegments = false, array $variants = [])
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->constraints = $constraints;
        $this->segments = $segments;
        $this->nonexistentSegments = $nonexistentSegments;
        $this->variants = $variants;
    }
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<Constraint>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @return array<Segment>
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @return array<Variant>
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    public function hasNonexistentSegments(): bool
    {
        return $this->nonexistentSegments;
    }
}
