<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


/**
 * @Gedmo\Tree(type="closure")
 * @Gedmo\TreeClosure(class="EnjoysCMS\Module\Catalog\Entities\CategoryClosure")
 * @ORM\Entity(repositoryClass="EnjoysCMS\Module\Catalog\Repositories\Category")
 * @ORM\Table(name="catalog_categories")
 */
class Category
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private string $title;

    /**
     * This parameter is optional for the closure strategy
     *
     * @ORM\Column(name="level", type="integer", nullable=true)
     * @Gedmo\TreeLevel
     */
    private int $level;


    /**
     * @ORM\Column(name="sort", type="integer", options={"default": 0})
     */
    private int $sort;

    /**
     * @ORM\Column(type="string")
     */
    private string $url;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private ?string $shortDescription = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private bool $status = true;
    /**
     * @Gedmo\TreeParent
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     */
    private $children;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private ?string $img = null;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="OptionKey")
     * @ORM\JoinTable(name="catalog_category_optionkey")
     */
    private  $extraFields;

    public function __construct()
    {
        $this->extraFields = new ArrayCollection();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }


    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): void
    {
        $this->shortDescription = $shortDescription;
    }


    public function isStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }


    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): void
    {
        $this->img = $img;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setParent(Category $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @return Category|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function addClosure(CategoryClosure $closure)
    {
        $this->closures[] = $closure;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getLevel()
    {
        return $this->level;
    }


    public function getSort(): int
    {
        return $this->sort;
    }


    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }


    public function getUrl(): string
    {
        return $this->url;
    }


    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getSlug(): string
    {
        $parent = $this->getParent();
        if ($parent === null) {
            return $this->getUrl();
        }
        return $parent->getSlug() . '/' . $this->getUrl();
    }

    public function getFullTitle(string $separator = " / ", bool $reverse = false): string
    {
        $parent = $this->getParent();
        if ($parent === null) {
            return $this->getTitle();
        }

        if ($reverse === true) {
            return $this->getTitle() . $separator . $parent->getFullTitle($separator, $reverse);
        }
        return $parent->getFullTitle($separator) . $separator . $this->getTitle();
    }

    public function getOnlyParentFullTitle($separator = " / "): string
    {
        $parent = $this->getParent();

        if ($parent === null) {
            return '';
        }

        return $parent->getFullTitle($separator);
    }

    public function getBreadcrumbs(): array
    {
        $parent = $this->getParent();
        $data = [
            [
                'slug' => $this->getSlug(),
                'title' => $this->getTitle(),
            ]
        ];
        if ($parent === null) {
            return $data;
        }

        return array_merge($parent->getBreadcrumbs(), $data);
    }


    public function getChildren()
    {

        $iterator = $this->children->getIterator();

        /** @var ArrayCollection $c */
        $iterator->uasort(function ($first, $second){
            return $first->getSort() <=> $second->getSort();
        });

        return new ArrayCollection(iterator_to_array($iterator));
    }


    /**
     * @return ArrayCollection|null
     */
    public function getExtraFields()
    {
        return $this->extraFields;
    }

    public function removeExtraFields()
    {
        $this->extraFields = new ArrayCollection();
    }

    public function addExtraField(OptionKey $field)
    {
        if ($this->extraFields->contains($field)){
            return;
        }
        $this->extraFields->add($field);
    }


//
//
//    public function setExtraFields(?array $extraFields): void
//    {
//        $this->extraFields = $extraFields;
//    }
}
