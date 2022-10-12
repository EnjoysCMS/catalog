<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;


use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Entities\Currency\CurrencyRate;

final class ProductPriceEntityListener
{
    public function __construct(private ?Config $config = null)
    {
    }

    public function preUpdate(ProductPrice $productPrice, PreUpdateEventArgs $eventArgs)
    {
        if ($this->config === null) {
            return;
        }
        $eventArgs->setNewValue('price', $eventArgs->getOldValue('price'));
        $eventArgs->setNewValue('updatedAt', $eventArgs->getOldValue('updatedAt'));
    }


    public function postLoad(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        $em = $event->getObjectManager();
        $this->convertPrice($productPrice, $em);
    }


    private function convertPrice(ProductPrice $productPrice, ObjectManager $em)
    {
        if ($this->config === null) {
            return;
        }

        $currentCurrency = $em->getRepository(Currency::class)->find(
            $this->config->getModuleConfig()->get('currency')['default'] ?? null
        ) ?? throw new \InvalidArgumentException(
            'Default currency value not valid'
        );

        $currencyRate = $em->getRepository(CurrencyRate::class)->find(
            ['currencyMain' => $productPrice->getCurrency(), 'currencyConvert' => $currentCurrency]
        );
        $productPrice->setPrice($productPrice->getPrice() * $currencyRate?->getRate() ?? 1);
        $productPrice->setCurrentCurrency($currentCurrency);
    }

    /**
     * @param Config|null $config
     */
    public function setConfig(?Config $config): void
    {
        $this->config = $config;
    }
}
