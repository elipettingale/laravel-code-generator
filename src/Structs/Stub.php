<?php

namespace EliPett\CodeGeneration\Structs;

class Stub
{
    private $stubPath;
    private $targetDirectory;
    private $targetFileName;

    public function __construct($stubPath, $targetDirectory, $targetFileName)
    {
        $this->stubPath = $stubPath;
        $this->targetDirectory = $targetDirectory;
        $this->targetFileName = $targetFileName;
    }

    public function getStubPath(): string
    {
        return $this->stubPath;
    }

    public function getTargetPath(): string
    {
        return $this->targetDirectory . '/' . $this->targetFileName;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function getTargetFileName(): string
    {
        return $this->targetFileName;
    }
}
