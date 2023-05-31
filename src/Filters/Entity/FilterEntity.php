<?php

namespace EnjoysCMS\Module\Catalog\Filters\Entity;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Filters\FilterParams;

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
    private string $filterType;

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

    public function getFilterType(): string
    {
        return $this->filterType;
    }

    public function setFilterType(string $filterType): void
    {
        $this->filterType = $filterType;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }


    public function getParams(): FilterParams
    {
        return new FilterParams($this->params);
    }


    public function setParams(array $params): void
    {
        $this->params = $params;
    }

}
