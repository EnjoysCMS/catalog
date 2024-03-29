<?php

namespace EnjoysCMS\Module\Catalog\Repositories;

use Doctrine\ORM\EntityRepository;

class Setting extends EntityRepository
{
    public function findAllKeyVar(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.key, s.value')
            ->getQuery()
            ->getResult('KeyPair');
    }
}
