<?php

namespace EnjoysCMS\Module\Catalog\Filters;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use EnjoysCMS\Module\Catalog\Filters\Filter\OptionFilter;
use EnjoysCMS\Module\Catalog\Filters\Filter\PriceFilter;
use EnjoysCMS\Module\Catalog\Filters\Filter\StockFilter;

class FilterFactory
{
    public static array $filters = [
        'option' => OptionFilter::class,
        'price' => PriceFilter::class,
        'stock' => StockFilter::class
    ];

    private array $result = [];

    public function __construct(private Container $container)
    {
    }

    /**
     * @return FilterInterface[]
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function createFromArray(array $data): array
    {
        foreach ($data as $filterClass => $params) {
            $this->resolveFilters($filterClass, $params);
        }

        return $this->result;
    }


    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function resolveFilters(int|string $filterClass, array $params): void
    {
        $this->buildFilter(self::$filters[$filterClass], $params);
    }

    private function resolveParams(string $filterClassString, array $params): array
    {
        return match ($filterClassString) {
            OptionFilter::class => [
                'optionKey' => $key = array_key_first($params),
                'currentValues' => $params[$key]
            ],
            PriceFilter::class => [
                'currentValues' => $params
            ],
            default => $params,
        };
    }

    private function buildFilter(mixed $filterClass, array $params)
    {
        switch ($filterClass) {
            case OptionFilter::class:
                foreach ($params as $key => $values) {
                    $this->result[] = $this->container->make(OptionFilter::class, [
                        'optionKey' => $key,
                        'currentValues' => $values
                    ]);
                }
                break;
            case  PriceFilter::class:
                $this->result[] = $this->container->make(PriceFilter::class, [
                    'currentValues' => $params
                ]);
                break;
        }
    }

}
