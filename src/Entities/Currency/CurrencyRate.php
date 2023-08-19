<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities\Currency;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Repositories\CurrencyRateRepository;


#[ORM\Entity(repositoryClass: CurrencyRateRepository::class)]
#[ORM\Table(name: 'catalog_currency_rate')]
class CurrencyRate
{

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Currency::class)]
    #[ORM\JoinColumn(name: '`main`')]
    private Currency $currencyMain;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Currency::class)]
    #[ORM\JoinColumn(name: '`convert`')]
    private Currency $currencyConvert;

    #[ORM\Column(type: 'float')]
    private float $rate;

    public function __toString(): string {
        return $this->currencyMain->getCode().$this->currencyConvert->getCode();
    }

    /**
     * @return mixed
     */
    public function getCurrencyMain()
    {
        return $this->currencyMain;
    }

    public function setCurrencyMain(mixed $currencyMain): void
    {
        $this->currencyMain = $currencyMain;
    }

    /**
     * @return mixed
     */
    public function getCurrencyConvert()
    {
        return $this->currencyConvert;
    }

    public function setCurrencyConvert(mixed $currencyConvert): void
    {
        $this->currencyConvert = $currencyConvert;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function setRate(float $rate): void
    {
        $this->rate = $rate;
    }
}
