<?php

declare(strict_types=1);

class Command
{
    public $Name       = '';
    public $Function   = '';
    public $Parameters = [];

    /**
     * @param string $controllerName
     * @param string $functionName
     * @param array  $paramArray
     */
    public function __construct(string $controllerName, string $functionName, array $paramArray)
    {
        $this->Parameters = $paramArray;
        $this->Name       = $controllerName;
        $this->Function   = $functionName;
    }

    /**
     * @return string
     */
    public function getControllerName(): string
    {
        return $this->Name;
    }

    /**
     * @return string
     */
    public function getFunction(): string
    {
        return $this->Function;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return (array) $this->Parameters;
    }
}
