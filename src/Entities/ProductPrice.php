<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;
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
     * @ORM\Column(type="datetime", )
     */
    private \DateTime $date;

    /**
     * @ORM\ManyToOne(targetEntity="PriceGroup")
     */
    private $priceGroup;

    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="prices")
     */
    private Product $product;

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


    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setDate(): void
    {
        $this->date = new \DateTime();
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


}