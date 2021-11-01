<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="product_option_values")
 * @ORM\Entity(repositoryClass="\EnjoysCMS\Module\Catalog\Repositories\OptionValueRepository")
 */
class OptionValue
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private string $value;

    /**
     * @ORM\ManyToOne(targetEntity="OptionKey")
     * @ORM\JoinColumn(name="key_id", referencedColumnName="id")
     */
    private $optionKey;

    /**
     * @ORM\ManyToMany(targetEntity="Product", mappedBy="options")
     */
    private $products;

    public function __construct() {
        $this->products = new ArrayCollection();
        $this->optionKey = new ArrayCollection();
    }


    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
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

    /**
     * @param mixed $optionKey
     */
    public function setOptionKey($optionKey): void
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