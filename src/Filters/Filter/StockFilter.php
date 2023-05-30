<?php

namespace EnjoysCMS\Module\Catalog\Filters\Filter;

use Doctrine\ORM\QueryBuilder;

class StockFilter implements \EnjoysCMS\Module\Catalog\Filters\FilterInterface
{

    public function getParamName(): int|string
    {
        // TODO: Implement getParamName() method.
    }

    public function getType(): string
    {
        return 'stock';
    }

    public function getFormName(): string
    {
        return 'filter[stock]';
    }

    public function getTitle(): string
    {
        // TODO: Implement getTitle() method.
    }

    public function getPossibleValues(array $pids): array
    {
        // TODO: Implement getValues() method.
    }

    public function addFilterRestriction(QueryBuilder $qb): QueryBuilder
    {
        return $qb;
    }
}
