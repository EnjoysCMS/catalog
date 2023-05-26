<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @ORM\Column(name="title", type="string", length=255)
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
    private ?Category $parent;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     */
    private Collection $children;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private ?string $img = null;

    /**
     * @ORM\ManyToMany(targetEntity="OptionKey")
     * @ORM\JoinTable(name="catalog_category_optionkey")
     */
    private Collection $extraFields;

    /**
     * @ORM\Column(type="string", nullable=true, options={"default": null})
     */
    private ?string $customTemplatePath = null;

    /**
     * @ORM\OneToOne(targetEntity="CategoryMeta", mappedBy="category", cascade={"persist", "remove"})
     */
    private ?CategoryMeta $meta = null;

    public function __construct()
    {
        $this->extraFields = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
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

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setParent(Category $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function addClosure(CategoryClosure $closure): void
    {
        $this->closures[] = $closure;
    }

    public function setLevel($level): void
    {
        $this->level = $level;
    }

    public function getLevel(): int
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


    public function getChildren(): Collection
    {

        $iterator = $this->children->getIterator();

        /** @var ArrayCollection $c */
        $iterator->uasort(function ($first, $second){
            return $first->getSort() <=> $second->getSort();
        });

        return new ArrayCollection(iterator_to_array($iterator));
    }


    public function getExtraFields(): Collection
    {
        return $this->extraFields;
    }

    public function removeExtraFields(): void
    {
        $this->extraFields = new ArrayCollection();
    }

    public function addExtraField(OptionKey $field): void
    {
        if ($this->extraFields->contains($field)){
            return;
        }
        $this->extraFields->add($field);
    }

    public function getCustomTemplatePath(): ?string
    {
        return $this->customTemplatePath;
    }

    public function setCustomTemplatePath(?string $customTemplatePath): void
    {
        $this->customTemplatePath = $customTemplatePath;
    }


    public function getMeta(): CategoryMeta
    {
        return $this->meta ?? new CategoryMeta();
    }


    public function setMeta(?CategoryMeta $meta): void
    {
        $meta->setCategory($this);
        $this->meta = $meta;
    }
}
