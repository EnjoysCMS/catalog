<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Class Product
 * @package EnjoysCMS\Module\Catalog\Entities
 * @ORM\Entity(repositoryClass="EnjoysCMS\Module\Catalog\Repositories\Product")
 * @ORM\Table(name="products")
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;
    /**
     * @ORM\Column(type="string")
     */
    private string $name;
    /**
     * @ORM\Column(type="text")
     */
    private string $description;
    /**
     * @ORM\Column(type="string", nullable=true, options={"default": null})
     */
    private ?string $articul = null;
    /**
     * @ORM\Column(type="string")
     */
    private string $url;
    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private bool $hide = false;
    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private bool $active = true;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     */
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="Image", mappedBy="product")
     */
    private $images;

    /**
     * @ORM\OneToOne(targetEntity="ProductMeta", mappedBy="product", cascade={"persist", "remove"})
     */
    private ?ProductMeta $meta = null;

    /**
     * @ORM\ManyToMany(targetEntity="ProductTag")
     * @ORM\JoinTable(name="products_tags",
     *      joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     *      )
     */
    private $tags;

    /**
     * @ORM\ManyToMany(targetEntity="OptionValue")
     * @ORM\JoinTable(name="products_options")
     */
    private $options;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->options = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = \trim($name);
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getArticul(): ?string
    {
        return $this->articul;
    }

    /**
     * @param string|null $articul
     */
    public function setArticul(?string $articul): void
    {
        $this->articul = $articul;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return bool
     */
    public function isHide(): bool
    {
        return $this->hide;
    }

    /**
     * @param bool $hide
     */
    public function setHide(bool $hide): void
    {
        $this->hide = $hide;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }


    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     */
    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function getSlug(): string
    {
        $category = $this->getCategory();

        $slug = null;
        if ($category instanceof Category) {
            $slug = $category->getSlug() . '/';
        }

        return $slug . $this->getUrl();
    }

    public function getImages()
    {
        return $this->images;
    }


    public function getTags()
    {
        return $this->tags;
    }


    public function addTag(ProductTag $tag): void
    {
        $this->tags->add($tag);
    }

    public function clearTags()
    {
        $this->tags = new ArrayCollection();
    }


    public function getMeta(): ?ProductMeta
    {
        return $this->meta;
    }

    /**
     * @param ProductTag[] $tags
     */
    public function addTagsFromArray(array $tags)
    {
        foreach ($tags as $tag) {
            if (!($tag instanceof ProductTag)) {
                continue;
            }
            $this->addTag($tag);
        }
    }

    public function getOptionsCollection()
    {
        return $this->options;
    }


    public function getOptions()
    {
        $result = [];
        /** @var OptionValue $option */
        foreach ($this->options as $i => $option) {
            /** @var OptionKey $key */
            $key = $option->getOptionKey();
            $result[$key->getId()]['key'] = $key;
            $result[$key->getId()]['values'][] = $option;
        }
        sort($result);
        return $result;
    }

    public function getValuesByOptionKey($optionKey): array
    {
        return array_filter($this->options->toArray(), function ($item) use ($optionKey) {
            if ($item->getOptionKey() === $optionKey) {
                return $item;
            }
        });
    }

    public function clearOptions()
    {
        $this->options = new ArrayCollection();
    }


    public function addOption(OptionValue $option): void
    {
        if($this->options->contains($option)){
            return;
        }
        $this->options->add($option);
    }
}
