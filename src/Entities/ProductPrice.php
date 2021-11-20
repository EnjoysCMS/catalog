<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Helpers\Normalize;

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
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $date;

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

    /**
     * @return \DateTimeImmutable
     */
    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @param \DateTimeImmutable $date
     */
    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }


    public function getPriceGroup()
    {
        return $this->priceGroup;
    }


    public function setPriceGroup(PriceGroup $priceGroup): void
    {
        $this->priceGroup = $priceGroup;
    }


}