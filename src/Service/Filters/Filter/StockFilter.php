<?php

namespace EnjoysCMS\Module\Catalog\Service\Filters\Filter;

use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;

class StockFilter implements FilterInterface
{
    public function __toString(): string
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
        $form->checkbox('filter[stock]')
            ->addClass('form-switch', Form::ATTRIBUTES_FILLABLE_BASE)
            ->fill([1 => $this->__toString()]);
        return $form;
    }
}
