<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog;


use Doctrine\ORM\EntityManager;
use Enjoys\Session\Session;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;

final class DynamicConfig
{
    private ModuleConfig $moduleConfig;

    public function __construct(Config $config, private Session $session, private EntityManager $em)
    {
        $this->moduleConfig = $config->getModuleConfig();
    }

    public function getModuleConfig(): ModuleConfig
    {
        return $this->moduleConfig;
    }

    public function getCurrentCurrencyCode(): string
    {
        return $this->session->get('catalog')['currency'] ?? $this->moduleConfig->get(
            'currency'
        )['default'] ?? throw new \InvalidArgumentException(
            'Default currency value not valid'
        );
    }

    public function getCurrentCurrency(): Currency
    {
        return $this->em->getRepository(Currency::class)->find(
            $this->getCurrentCurrencyCode()
        ) ?? throw new \InvalidArgumentException(
            'Default currency value not valid'
        );
    }
}
