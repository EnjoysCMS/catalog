<?php

namespace EnjoysCMS\Module\Catalog\Filters\Filter;

use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Filters\FilterInterface;

class StockFilter implements FilterInterface
{

    public function __toString(): string
    {
        return 'Только в наличии';
    }

    public function getPossibleValues(array $productIds): array
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

    public function getFormElement($form, $values): Form
    {
        // TODO: Implement getFormElement() method.
    }
}
