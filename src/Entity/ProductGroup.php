<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Repository\ProductGroupRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: ProductGroupRepository::class)]
#[ORM\Table(name: 'catalog_products_group')]
class ProductGroup
{


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $title;

    /**
     * @var Collection<Product> $products
     */
    #[ORM\JoinTable(name: 'catalog_group_products')]
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Product::class)]
    private Collection $products;

    /**
     * @var Collection<OptionKey> $options
     */
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

    /**
     * @return Collection<Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function removeProducts(?array $products = null): void
    {
        if ($products === null) {
            foreach ($this->products as $product) {
                $this->removeProduct($product);
            }
            $this->products = new ArrayCollection();
            return;
        }

        foreach ($products as $product) {
            $this->removeProduct($product);
            $this->products->removeElement($product);
        }
    }

    public function removeProduct(Product $product): void
    {
        $this->products->removeElement($product);
        $product->setGroup(null);
    }

    public function addProduct(Product $product): void
    {
        if ($this->products->contains($product)) {
            return;
        }
        $this->products->add($product);
        $product->setGroup($this);
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

    /**
     * @return Collection<OptionKey>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function removeOptions(?array $options = null): void
    {
        if ($options === null) {
            $this->options = new ArrayCollection();
            return;
        }

        foreach ($options as $option) {
            $this->options->removeElement($option);
        }
    }

    public function addOption(OptionKey $optionKey): void
    {
        if ($this->options->contains($optionKey)) {
            return;
        }
        $this->options->add($optionKey);
    }

    public function getOptionsValues(): \WeakMap
    {
        $values = new \WeakMap();
        foreach ($this->getOptions() as $option) {
            $values[$option] = [];
            foreach ($this->getProducts() as $product) {
                $values[$option] = array_merge($values[$option], $product->getValuesByOptionKey($option));
            }
            sort($values[$option], SORT_NATURAL);
            $values[$option] = array_unique($values[$option], SORT_REGULAR);
        }
        return $values;
    }


    public function getDefaultOptionsByProduct(Product $product): array
    {
        $defaultOptions = [];

        foreach ($this->options as $option) {
            $defaultOptions[$option->getId()] = $product->getOptionsCollection()->findFirst(
                function ($key, $item) use ($option) {
                    return $item->getOptionKey() === $option;
                }
            );
        }
        return $defaultOptions;
    }


}
