<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Helpers\Normalize;

/**
 * @ORM\Entity
 * @ORM\Table(name="catalog_product_prices")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
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
     * @ORM\Column(name="updated_at", type="datetime_immutable", columnDefinition="DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP")
     */
    private \DateTimeImmutable $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="PriceGroup")
     */
    private $priceGroup;

    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="prices")
     */
    private Product $product;

    /**
     * @ORM\ManyToOne(targetEntity="EnjoysCMS\Module\Catalog\Entities\Currency\Currency")
     */
    private Currency $currency;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return (string) $this->getPrice();
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


    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }




    public function getPriceGroup()
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


}