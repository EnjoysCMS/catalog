<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Repository\UrlRepository;

#[ORM\Entity(repositoryClass: UrlRepository::class)]
class Url
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 500)]
    private string $path;

    #[ORM\Column(name: '`default`', type: 'boolean')]
    private bool $default;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'urls')]
    private Product $product;


    public function isDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }


    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }


    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }


    public function getId(): int
    {
        return $this->id;
    }


}
