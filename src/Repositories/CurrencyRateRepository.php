<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;


use Doctrine\ORM\EntityRepository;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;

class CurrencyRateRepository extends EntityRepository
{
    public function getAllRatesByCurrency(Currency $currency)
    {
        return array_unique(array_merge(
            $this->findBy(['currencyMain' => $currency]),
            $this->findBy(['currencyConvert' => $currency]),
        ));
    }
    public function removeAllRatesByCurrency(Currency $currency)
    {
        foreach ($this->getAllRatesByCurrency($currency) as $rate) {
            $this->getEntityManager()->remove($rate);
        }
    }
}
