<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Helpers\Normalize;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="catalog_product_prices")
 */
final class ProductPrice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    private int $price;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private \DateTime $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="PriceGroup")
     */
    private $priceGroup;

    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="prices", cascade={"persist"})
     */
    private Product $product;

    /**
     * @ORM\ManyToOne(targetEntity="EnjoysCMS\Module\Catalog\Entities\Currency\Currency")
     */
    private Currency $currency;

    /**
     * @ORM\ManyToOne(targetEntity="ProductUnit")
     */
    private ProductUnit $unit;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->getFormatted();
    }

    public function getFormatted(
        string $decimal_separator = '.',
        string $thousands_separator = ' ',
        string $nbsp = '&nbsp;'
    ): string {
        return str_replace(
            ' ',
            $nbsp,
            $this->getCurrency()->getLeft() .
            number_format(
                $this->getPrice(),
                $this->getCurrency()->getPrecision(),
                $decimal_separator,
                $thousands_separator
            ) .
            $this->getCurrency()->getRight()
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPrice(): float
    {
        return $this->price / 100;
    }

    /**
     * @param numeric|numeric-string $price
     */
    public function setPrice($price): void
    {
        $this->price = Normalize::toPrice($price);
    }


    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }


    public function getPriceGroup(): PriceGroup
    {
        return $this->priceGroup;
    }


    public function setPriceGroup(PriceGroup $priceGroup): void
    {
        $this->priceGroup = $priceGroup;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    /**
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @param Currency $currency
     */
    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return ProductUnit
     */
    public function getUnit(): ProductUnit
    {
        return $this->unit;
    }

    /**
     * @param ProductUnit $unit
     */
    public function setUnit(ProductUnit $unit): void
    {
        $this->unit = $unit;
    }
}
