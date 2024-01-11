<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_group_products_option')]
class ProductGroupOption
{

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ProductGroup::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ProductGroup $productGroup;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: OptionKey::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private readonly OptionKey $optionKey;

    public function __construct(
        ProductGroup $productGroup,
        OptionKey $optionKey,
    ) {
        $this->productGroup = $productGroup;
        $this->optionKey = $optionKey;
    }

    public function __toString(): string
    {
        return $this->optionKey->__toString();
    }

    public function getOptionKey(): OptionKey
    {
        return $this->optionKey;
    }

    public function getProductGroup(): ProductGroup
    {
        return $this->productGroup;
    }

}
