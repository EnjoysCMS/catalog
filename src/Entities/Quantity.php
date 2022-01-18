<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="catalog_product_quantity")
 * @ORM\Entity
 */
final class Quantity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity="Product", inversedBy="quantity")
     */
    private $product;

    /**
     * @ORM\Column(type="float", options={"default": 0})
     */
    private float $qty = 0;

    /**
     * @ORM\Column(type="float", options={"default": 0})
     */
    private float $reserve = 0;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }


    public function getReserve(): float|int
    {
        return $this->reserve;
    }


    public function setReserve(float|int $reserve): self
    {
        $this->reserve = $reserve;
        return $this;
    }


    public function getQty(): float|int
    {
        return $this->qty;
    }


    public function setQty(float|int $qty): self
    {
        $this->qty = $qty;
        return $this;
    }


    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }


    public function getProduct(): Product
    {
        return $this->product;
    }
}
