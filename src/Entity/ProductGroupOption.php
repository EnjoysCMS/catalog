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
    private OptionKey $optionKey;

    #[ORM\Column(name: '`order`', type: 'integer', options: ['default' => 0])]
    private int $order = 0;

    /**
     * button - по умолчанию
     * thumbs - показ миниатюр продуктов, корректно работает только при одной опции
     * image - показ миниатюр, но эти миниатюры настраиваются на каждое значение
     * color - показ выбор цвета, настраиваются на каждое значение, сопоставляются цвета HTML строковые и hex
     */
    #[ORM\Column(type: 'string', options: ['default' => 'button'])]
    private string $type = 'button';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extra = null;

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

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): void
    {
        $this->extra = $extra;
    }

}
