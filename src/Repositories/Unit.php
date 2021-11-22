<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\EntityRepository;

final class Unit extends EntityRepository
{
    public function like(string $query)
    {
        return $this->getFindAllBuilder()
            ->andWhere('p.name LIKE :query')
            ->setParameter('query', $query . '%')
            ->getQuery()
            ->getResult();
    }
}