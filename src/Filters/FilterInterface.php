<?php

namespace EnjoysCMS\Module\Catalog\Filters;

use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;

interface FilterInterface extends \Stringable
{

    public function getPossibleValues(array $productIds): array;

    public function addFilterRestriction(QueryBuilder $qb): QueryBuilder;

    public function getFormElement(Form $form, $values): Form;
}
