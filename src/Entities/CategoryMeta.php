<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="catalog_category_meta")
 */
final class CategoryMeta
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=70, nullable=true)
     */
    private ?string $title = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private ?string $keyword = null;


    /**
     * @var string|null
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\OneToOne(targetEntity="Category", inversedBy="meta")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    private Category $category;


    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getId(): int
    {
        return $this->id;
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        if(empty($title)){
            $title = null;
        }
        $this->title = $title;
    }

    public function getKeyword(): ?string
    {

        return $this->keyword;
    }

    public function setKeyword(?string $keyword): void
    {
        if(empty($keyword)){
            $keyword = null;
        }
        $this->keyword = $keyword;
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = empty($description) ? null : $description;
    }

}
