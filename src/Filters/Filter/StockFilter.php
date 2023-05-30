<?php

namespace EnjoysCMS\Module\Catalog\Filters\Filter;

use Doctrine\ORM\QueryBuilder;

class StockFilter implements \EnjoysCMS\Module\Catalog\Filters\FilterInterface
{

    public function getTitle(): string
    {
        return 'Только в наличии';
    }

    public function getPossibleValues(array $pids): array
    {
        return [];
    }

    public function addFilterRestriction(QueryBuilder $qb): QueryBuilder
    {
        return $qb;
    }


    public function getFormName(): string
    {
        return 'filter[stock]';
    }


    public function getFormType(): string
    {
        return 'checkbox-on-off';
    }

    public function getFormDefaults(array $values): array
    {
        return [];
    }
}
