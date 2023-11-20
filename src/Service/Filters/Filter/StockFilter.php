<?php

namespace EnjoysCMS\Module\Catalog\Service\Filters\Filter;

use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;

class StockFilter implements FilterInterface
{
    public function __toString(): string
    {
        return '';
    }


    public function getBadgeValue(): string
    {
        return 'Только в наличии';
    }


    public function getPossibleValues(array $productIds): array
    {
        return [0, 1];
    }

    public function addFilterQueryBuilderRestriction(QueryBuilder $qb): QueryBuilder
    {
        $qb->andWhere('(q.qty-q.reserve) > 0');
        return $qb;
    }

    public function getFormElement($form, $values): Form
    {
        $form->checkbox($this->getFormName(), 'Наличие в магазине')
            ->addClass('form-switch', Form::ATTRIBUTES_FILLABLE_BASE)
            ->fill([1 => 'Только в наличии']);
        return $form;
    }

    public function getFormName(): string
    {
        return 'filter[stock]';
    }

    public function isActiveFilter(): bool
    {
        return true;
    }
}
