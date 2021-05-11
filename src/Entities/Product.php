<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\Common\Collections\ArrayCollection;
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
    private ?string $article = null;
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
        $this->name = $name;
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
    public function getArticle(): ?string
    {
        return $this->article;
    }

    /**
     * @param string|null $article
     */
    public function setArticle(?string $article): void
    {
        $this->article = $article;
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

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function getSlug(): string
    {
        $category = $this->getCategory();
        $slug = '{CATEGORY_NOT_ISSET}';
        if ($category instanceof Category) {
            $slug = $category->getSlug();
        }
        return $slug . '/' . $this->getUrl();
    }

    public function getImages()
    {
        return $this->images;
    }


}
