<?php

namespace EnjoysCMS\Module\Catalog\Service\Filters;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use EnjoysCMS\Module\Catalog\Service\Filters\Filter\OptionFilter;
use EnjoysCMS\Module\Catalog\Service\Filters\Filter\PriceFilter;
use EnjoysCMS\Module\Catalog\Service\Filters\Filter\StockFilter;
use EnjoysCMS\Module\Catalog\Service\Filters\Filter\VendorFilter;

class FilterFactory
{
    public static array $filters = [
        'option' => OptionFilter::class,
        'price' => PriceFilter::class,
        'stock' => StockFilter::class,
        'vendor' => VendorFilter::class,
    ];

    private array $result = [];

    public function __construct(private Container $container)
    {
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function create(string $filterType, FilterParams $filterParams): ?FilterInterface
    {
        $classString = self::$filters[$filterType] ?? null;
        if ($classString === null) {
            return null;
        }
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

        return array_filter($this->result, function (FilterInterface $filter){
            return is_null($filter) || $filter->isActiveFilter();
        });
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
