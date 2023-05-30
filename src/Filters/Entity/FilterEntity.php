<?php

namespace EnjoysCMS\Module\Catalog\Filters\Entity;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Entities\Category;

/**
 * @ORM\Entity
 * @ORM\Table(name="catalog_category_filters")
 */
class FilterEntity
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="EnjoysCMS\Module\Catalog\Entities\Category")
     */
    private Category $category;

    /**
     * @ORM\Column(type="json")
     */
    private array $params;

    /**
     * @ORM\Column(type="string")
     */
    private string $formType;

    /**
     * @ORM\Column(name="order_filter", type="integer", options={"default": 0})
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

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function setFormType(string $formType): void
    {
        $this->formType = $formType;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }


    public function getParams(): array
    {
        return $this->params;
    }


    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getClassString()
    {
        return $this->params['classString'] ?? throw new \RuntimeException('classString not set');
    }

    public function getClassParams(): array
    {
        return $this->params['classParams'] ?? [];
    }
}
