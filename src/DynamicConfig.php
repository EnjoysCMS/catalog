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

    public function setCurrencyCode(?string $code)
    {
        $this->session->set([
            'catalog' => array_merge(
                $this->session->get('catalog', []),
                ['currency' => $code],
            )
        ]);
    }

    public function getSortMode(): ?string
    {
        return $this->session->get('catalog')['sort'] ?? $this->moduleConfig->get(
            'sort'
        );
    }

    public function setSortMode(string $mode = null): void
    {
        $this->session->set([
            'catalog' => array_merge(
                $this->session->get('catalog', []),
                ['sort' => $mode],
            )
        ]);
    }


    public function getPerPage(): string
    {
        return (string)($this->session->get('catalog')['limitItems'] ?? $this->moduleConfig->get(
            'limitItems'
        ) ?? throw new \InvalidArgumentException('limitItems not set'));
    }

    public function setPerPage(string $perpage = null): void
    {
        $allowedPerPage = $this->moduleConfig->get(
            'allowedPerPage'
        ) ?? throw new \InvalidArgumentException('allowedPerPage not set');

        if (!in_array((int)$perpage, $allowedPerPage, true)) {
            return;
        }

        $this->session->set([
            'catalog' => array_merge(
                $this->session->get('catalog', []),
                ['limitItems' => $perpage],
            )
        ]);
    }

}
