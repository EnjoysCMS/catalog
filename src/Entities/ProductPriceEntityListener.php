<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;


use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Entities\Currency\CurrencyRate;

final class ProductPriceEntityListener
{
    public function __construct(private ?Config $config = null)
    {
    }

    /** @ORM\PostLoad */
    public function postLoadHandler(ProductPrice $productPrice, LifecycleEventArgs $event)
    {
        if ($this->config === null){
            return;
        }

        $em = $event->getObjectManager();
        $currentCurrency = $em->getRepository(Currency::class)->find(
            $this->config->getModuleConfig()->get('currency')['default'] ?? 'RUB'
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
