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
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function create(string $filterType, FilterParams $filterParams): FilterInterface
    {
        $classString = self::$filters[$filterType] ?? throw new \RuntimeException(
            sprintf(
                '%s not mapped',
                $filterType
            )
        );
        return $this->container->make($classString, ['params' => $filterParams]);
    }

    /**
     * @return FilterInterface[]
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function createFromQueryString(array $data): array
    {
        foreach ($data as $filterType => $params) {
            $this->resolveFilters($filterType, $params);
        }

        return $this->result;
    }


    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function resolveFilters(string $filterType, array $params): void
    {
        $filterParamsResolved = $this->resolveParams($filterType, $params);
        foreach ($filterParamsResolved as $filterParams) {
            $this->result[] = $this->create($filterType, $filterParams);
        }
    }

    /**
     * @return FilterParams[]
     */
    private function resolveParams(string $filterType, array $params): array
    {
        switch ($filterType) {
            case 'option':
                $result = [];
                foreach ($params as $key => $values) {
                    $result[] = new FilterParams([
                        'optionKey' => $key,
                        'currentValues' => $values
                    ]);
                }
                return $result;
            case  'price':
                return [
                    new FilterParams([
                        'currentValues' => $params
                    ])
                ];
            default:
                return [new FilterParams($params)];
        }
    }

}
