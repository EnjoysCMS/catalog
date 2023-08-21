<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_product_meta')]
final class ProductMeta
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 70, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $keyword = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToOne(inversedBy: 'meta', targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id')]
    private Product $product;

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
        if (empty($title)) {
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
        if (empty($keyword)) {
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
        if (empty($description)) {
            $description = null;
        }
        $this->description = $description;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

}
