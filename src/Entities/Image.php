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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
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

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function setStorage(string $storage): void
    {
        $this->storage = $storage;
    }

    /**
     * @return bool
     */
    public function isGeneral(): bool
    {
        return $this->general;
    }

    /**
     * @param bool $general
     */
    public function setGeneral(bool $general): void
    {
        $this->general = $general;
    }

    /**
     * @return (mixed|string)[]
     *
     * @psalm-return array{path: string, glob_pattern: mixed, filename: string, extension: string}
     */
    public function getInfo(): array
    {
        return [
            'path' => $_ENV['UPLOAD_DIR'] . '/catalog/' . $this->getFilename() . '.' . $this->getExtension(),
            'glob_pattern' => $this->getGlobPattern(),
            'filename' => $this->getFilename(),
            'extension' => $this->getExtension()
        ];
    }

    public function getGlobPattern(): string
    {
        return $_ENV['UPLOAD_DIR'] . '/catalog/' . $this->getFilename() . '*.' . $this->getExtension();
    }

}
