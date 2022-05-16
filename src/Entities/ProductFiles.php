<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="catalog_product_files")
 */
class ProductFiles
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(type="string")
     */
    private string $filename;

    /**
     * @ORM\Column(type="integer")
     */
    private int $filesize;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private bool $status = true;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $downloads = 0;


    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="files")
     */
    private Product $product;


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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getDownloads(): int
    {
        return $this->downloads;
    }

    /**
     * @param int $downloads
     */
    public function setDownloads(int $downloads): void
    {
        $this->downloads = $downloads;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @return int
     */
    public function getFilesize(): int
    {
        return $this->filesize;
    }

    /**
     * @param int $filesize
     */
    public function setFilesize(int $filesize): void
    {
        $this->filesize = $filesize;
    }

    /**
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }
}
