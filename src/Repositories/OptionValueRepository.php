<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;

final class OptionValueRepository extends EntityRepository
{

    public function like(string $field, string $value, ?OptionKey $key = null)
    {
        return  $this->createQueryBuilder('v')
            ->select('v')
            ->where("v.{$field} LIKE :value ")
            ->setParameter('value', $value . '%')
            ->andWhere('v.optionKey = :optionKey')
            ->setParameter('optionKey', $key)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

}
