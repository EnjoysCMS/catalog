<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

final class OptionKeyRepository extends EntityRepository
{

    public function like(string $field, string $value)
    {
        return  $this->createQueryBuilder('k')
            ->select('k')
            ->where("k.{$field} LIKE :value ")
            ->setParameter('value', $value . '%')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

}