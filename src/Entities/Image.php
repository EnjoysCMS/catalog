<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Image
 * @package EnjoysCMS\Module\Catalog\Entities
 * @ORM\Entity
 * @ORM\Table(name="catalog_product_images")
 */
class Image
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;
    /**
     * @ORM\Column(type="string")
     */
    private string $filename;
    /**
     * @ORM\Column(type="string")
     */
    private string $extension;

    /**
     * @var string
     * @ORM\Column(type="string", length=50, options={"default": "image_default"})
     */
    private string $storage;


    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private bool $general = false;

    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="images")
     */
    private Product $product;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function setStorage(string $storage): void
    {
        $this->storage = $storage;
    }

    public function isGeneral(): bool
    {
        return $this->general;
    }

    public function setGeneral(bool $general): void
    {
        $this->general = $general;
    }

}
