<?php

namespace EnjoysCMS\Module\Catalog\Filters;

use Doctrine\ORM\QueryBuilder;

interface FilterInterface
{
    public function getFormName(): string;

    public function getTitle(): string;

    public function getPossibleValues(array $pids): array;

    public function getType(): string;

    public function addFilterRestriction(QueryBuilder $qb): QueryBuilder;
}
