<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repository;


use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\OptionValue;

final class OptionValueRepository extends EntityRepository
{

    public function like(string $field, string $value, ?OptionKey $key = null)
    {
        return $this->getLikeQuery($field, $value, $key)->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    public function getLikeQuery(string $field, string $value, ?OptionKey $key = null): \Doctrine\ORM\Query
    {
        return $this->getLikeQueryBuilder($field, $value, $key)->getQuery();
    }

    public function getLikeQueryBuilder(string $field, string $value, ?OptionKey $key = null): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('v')
            ->select('v')
            ->where("v.{$field} LIKE :value ")
            ->setParameter('value', $value . '%')
            ->andWhere('v.optionKey = :optionKey')
            ->setParameter('optionKey', $key)
        ;
    }

    public function getOptionValue(string $value, OptionKey $optionKey): OptionValue
    {
        $optionValue = $this->findOneBy(
            [
                'optionKey' => $optionKey,
                'value' => $value
            ]
        );
        if ($optionValue !== null) {
            return $optionValue;
        }
        $optionValue = new OptionValue();
        $optionValue->setOptionKey($optionKey);
        $optionValue->setValue($value);
        $this->_em->persist($optionValue);
        $this->_em->getUnitOfWork()->commit($optionValue);
        return $optionValue;
    }


}
