<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_products_group')]
class ProductGroup
{


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $title;

    #[ORM\JoinTable(name: 'catalog_group_products')]
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Product::class)]
    private Collection $products;

    #[ORM\ManyToMany(targetEntity: OptionKey::class)]
    #[ORM\JoinTable(name: 'catalog_group_products_optionkey')]
    private Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): void
    {
        if ($this->products->contains($product)) {
            return;
        }
        $this->products->add($product);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string|UuidInterface $id): void
    {
        if (is_string($id)) {
            $id = Uuid::fromString($id);
        }
        $this->id = $id->toString();
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function removeOptions(): void
    {
        $this->options = new ArrayCollection();
    }

    public function addOption(OptionKey $optionKey): void
    {
        if ($this->options->contains($optionKey)){
            return;
        }
        $this->options->add($optionKey);
    }


}
