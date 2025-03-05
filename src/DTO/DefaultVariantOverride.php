<?php

namespace Unleash\Client\DTO;

use Override;

final class DefaultVariantOverride implements VariantOverride
{
    /**
     * @readonly
     */
    private string $field;
    /**
     * @var array<string>
     * @readonly
     */
    private array $values;
    /**
     * @param array<string> $values
     */
    public function __construct(string $field, array $values)
    {
        $this->field = $field;
        $this->values = $values;
    }
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return array<string>
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
