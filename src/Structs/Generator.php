<?php

namespace EliPett\CodeGeneration\Structs;

class Generator
{
    private $name;
    private $stubs;

    public function __construct(string $name, array $stubs)
    {
        $this->name = $name;
        $this->stubs = $stubs;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStubs(): array
    {
        return $this->stubs;
    }
}
