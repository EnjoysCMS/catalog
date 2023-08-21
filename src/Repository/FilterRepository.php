<?php

namespace EnjoysCMS\Module\Catalog\Repository;

use Doctrine\ORM\EntityRepository;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\OptionValue;

class FilterRepository extends EntityRepository
{


    public function getValues(OptionKey $optionKey, array $pids)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('v')
            ->from(OptionValue::class, 'v')
            ->where('v.optionKey = :optionKey')
            ->andWhere(':pids MEMBER OF v.products')
            ->setParameters([
                'pids' => $pids,
                'optionKey' => $optionKey
            ])->getQuery()->getResult();
    }

}
