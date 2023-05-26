<?php

namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="EnjoysCMS\Module\Catalog\Repositories\FilterRepository")
 * @ORM\Table(name="catalog_filters")
 */
class Filter
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     */
    private Category $category;

    /**
     * @ORM\ManyToOne(targetEntity="OptionKey")
     */
    private OptionKey $optionKey;

    /**
     * @ORM\Column(type="string")
     */
    private string $type;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $order;

    public function getId(): int
    {
        return $this->id;
    }


    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getOptionKey(): OptionKey
    {
        return $this->optionKey;
    }

    public function setOptionKey(OptionKey $optionKey): void
    {
        $this->optionKey = $optionKey;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }
}
