<?php

namespace EnjoysCMS\Module\Catalog\Filters;

class FilterParams
{
    public function __construct(private array $params = [])
    {
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function __get(string $name)
    {
        return $this->params[$name] ?? null;
    }
}
