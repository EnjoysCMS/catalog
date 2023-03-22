<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;


use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Currency\CurrencyRate;

final class ProductPriceEntityListener
{
    public function __construct(private Config $config)
    {
    }

    public function preUpdate(ProductPrice $productPrice, PreUpdateEventArgs $eventArgs): void
    {
        if ($this->config === null) {
            return;
        }
        $eventArgs->setNewValue('price', $eventArgs->getOldValue('price'));
        $eventArgs->setNewValue('updatedAt', $eventArgs->getOldValue('updatedAt'));
    }


    public function postLoad(ProductPrice $productPrice, LifecycleEventArgs $event): void
    {
        $em = $event->getObjectManager();
        $this->convertPrice($productPrice, $em);
    }


    private function convertPrice(ProductPrice $productPrice, ObjectManager $em): void
    {
        if ($this->config === null) {
            return;
        }

        $currentCurrency = $this->config->getCurrentCurrency();

        $currencyRate = $em->getRepository(CurrencyRate::class)->find(
            ['currencyMain' => $productPrice->getCurrency(), 'currencyConvert' => $currentCurrency]
        );
        $productPrice->setPrice($productPrice->getPrice() * $currencyRate?->getRate() ?? 1);
        $productPrice->setCurrentCurrency($currentCurrency);
    }
}
