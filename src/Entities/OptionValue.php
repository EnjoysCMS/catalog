<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Repository\OptionValueRepository;

#[ORM\Table(name: 'catalog_product_option_values')]
#[ORM\Entity(repositoryClass: OptionValueRepository::class)]
class OptionValue
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $value;

    #[ORM\ManyToOne(targetEntity: OptionKey::class)]
    #[ORM\JoinColumn(name: 'key_id', referencedColumnName: 'id')]
    private $optionKey;

    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'options')]
    private $products;

    public function __construct() {
        $this->products = new ArrayCollection();
        $this->optionKey = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getOptionKey()
    {
        return $this->optionKey;
    }

    public function setOptionKey(mixed $optionKey): void
    {
        $this->optionKey = $optionKey;
    }

    /**
     * @return ArrayCollection
     */
    public function getProducts()
    {
        return $this->products;
    }

}
