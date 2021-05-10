<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Image
 * @package EnjoysCMS\Module\Catalog\Entities
 * @ORM\Entity
 * @ORM\Table(name="images")
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

    public function getInfo()
    {
        return [
            'path' => $_ENV['UPLOAD_DIR'] . '/' . $this->getFilename() . '.' . $this->getExtension(),
            'glob_pattern' => $this->getGlobPattern(),
            'filename' => $this->getFilename(),
            'extension' => $this->getExtension()
        ];
    }

    public function getGlobPattern()
    {
        return $_ENV['UPLOAD_DIR'] . '/' . $this->getFilename() . '*.' . $this->getExtension();
    }

}