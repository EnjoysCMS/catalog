<?php

namespace EnjoysCMS\Module\Catalog\Filters;

use Doctrine\ORM\QueryBuilder;

interface FilterInterface
{
        public function getTitle(): string;
    public function getPossibleValues(array $pids): array;
    public function addFilterRestriction(QueryBuilder $qb): QueryBuilder;
//    public function getFormName(): string;
//    public function getFormType(): string;
    public function getFormDefaults(array $values): array;
}
