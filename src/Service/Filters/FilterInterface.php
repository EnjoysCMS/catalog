<?php

namespace EnjoysCMS\Module\Catalog\Service\Filters;

use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;

interface FilterInterface extends \Stringable
{

    public function getPossibleValues(array $productIds): array;

    public function addFilterQueryBuilderRestriction(QueryBuilder $qb): QueryBuilder;

    public function getFormElement(Form $form, $values): Form;
}
