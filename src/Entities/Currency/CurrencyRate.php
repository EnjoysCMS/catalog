<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities\Currency;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="EnjoysCMS\Module\Catalog\Repositories\CurrencyRateRepository")
 * @ORM\Table(name="catalog_currency_rate")
 */
class CurrencyRate
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(name="`main`")
     */
    private Currency $currencyMain;
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumn(name="`convert`")
     */
    private Currency $currencyConvert;

    /**
     * @ORM\Column(type="float")
     */
    private float $rate;

    public function __toString() {
        return $this->currencyMain->getCode().$this->currencyConvert->getCode();
    }

    /**
     * @return mixed
     */
    public function getCurrencyMain()
    {
        return $this->currencyMain;
    }

    /**
     * @param mixed $currencyMain
     */
    public function setCurrencyMain($currencyMain): void
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

    /**
     * @param mixed $currencyConvert
     */
    public function setCurrencyConvert($currencyConvert): void
    {
        $this->currencyConvert = $currencyConvert;
    }

    /**
     * @return float
     */
    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * @param float $rate
     */
    public function setRate(float $rate): void
    {
        $this->rate = $rate;
    }
}
