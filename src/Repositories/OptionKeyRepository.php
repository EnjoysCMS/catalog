<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;

final class OptionKeyRepository extends EntityRepository
{

    public function like(string $field, string $value)
    {
        return $this->createQueryBuilder('k')
            ->select('k')
            ->where("k.{$field} LIKE :value ")
            ->setParameter('value', $value . '%')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY)
        ;
    }

    public function getOptionKey(string $name, string $unit): OptionKey
    {
        $optionKey = $this->findOneBy(
            [
                'name' => $name,
                'unit' => $unit
            ]
        );
        if ($optionKey !== null) {
            return $optionKey;
        }
        $optionKey = new OptionKey();
        $optionKey->setName($name);
        $optionKey->setUnit($unit);
        $this->_em->persist($optionKey);
        $this->_em->flush();
        return $optionKey;
    }
}
