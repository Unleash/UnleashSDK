<?php

namespace Unleash\Client\Bootstrap;

use JsonSerializable;
use Override;
use Traversable;

final class JsonSerializableBootstrapProvider implements BootstrapProvider
{
    /**
     * @var JsonSerializable|array<mixed>|Traversable<mixed>
     * @readonly
     */
    private $data;
    /**
     * @param JsonSerializable|array<mixed>|Traversable<mixed> $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }
    /**
     * @return array<mixed>|JsonSerializable|Traversable<mixed>
     */
    public function getBootstrap()
    {
        return $this->data;
    }
}
