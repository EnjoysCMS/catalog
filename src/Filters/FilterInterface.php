<?php

namespace EnjoysCMS\Module\Catalog\Filters;

use Doctrine\ORM\QueryBuilder;
use Enjoys\Forms\Form;

interface FilterInterface
{
    public function getTitle(): string;

    public function getPossibleValues(array $pids): array;

    public function addFilterRestriction(QueryBuilder $qb): QueryBuilder;

    public function getFormDefaults(array $values): array;

    public function addFormElement(Form $form, $values): Form;
}
