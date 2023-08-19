<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;

use function trim;


#[ORM\Entity(repositoryClass: \EnjoysCMS\Module\Catalog\Repositories\Product::class)]
#[ORM\Table(name: 'catalog_products')]
class Product
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', length: 64, unique: true, nullable: true, options: ['default' => null])]
    private ?string $productCode = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $hide = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: ProductGroup::class)]
    private $group = null;


    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Image::class)]
    private Collection $images;


    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductFiles::class)]
    private Collection $files;


    #[ORM\OneToOne(mappedBy: 'product', targetEntity: ProductMeta::class, cascade: ['persist', 'remove'])]
    private ?ProductMeta $meta = null;

    #[ORM\JoinTable(name: 'catalog_products_tags')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: ProductTag::class)]
    private Collection $tags;

    #[ORM\ManyToMany(targetEntity: OptionValue::class)]
    #[ORM\JoinTable(name: 'catalog_products_options')]
    private Collection $options;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Url::class, cascade: ['persist'])]
    private Collection $urls;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductPrice::class, cascade: ['persist'])]
    private Collection $prices;

    #[ORM\OneToOne(mappedBy: 'product', targetEntity: Quantity::class, cascade: ['persist'])]
    private $quantity;

    #[ORM\Column(name: 'max_discount', type: 'integer', nullable: true)]
    private ?int $maxDiscount = null;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    private ?ProductUnit $unit = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->options = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->prices = new ArrayCollection();
        $this->files = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }


    public function getMaxDiscount(): ?int
    {
        return $this->maxDiscount;
    }

    public function setMaxDiscount(?int $maxDiscount): void
    {
        $this->maxDiscount = $maxDiscount;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(?string $productCode): void
    {
        $this->productCode = $productCode;
    }


    public function isHide(): bool
    {
        return $this->hide;
    }

    public function setHide(bool $hide): void
    {
        $this->hide = $hide;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }


    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    /**
     * @throws Exception
     */
    public function getSlug(string $lastPartSlug = null): string
    {
        $category = $this->getCategory();

        $slug = null;
        if ($category instanceof Category) {
            $slug = $category->getSlug() . '/';
        }

        return $slug . ($lastPartSlug ?? $this->getUrl()->getPath());
    }

    /**
     * @return Collection<Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function getDefaultImage(): ?Image
    {
        $image = $this->images->filter(fn($i) => $i->isGeneral())->first();
        if (!($image instanceof Image)) {
            return null;
        }
        return $image;
    }

    /**
     * @return Collection<ProductFiles>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }


    /**
     * @return Collection<ProductTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }


    public function addTag(ProductTag $tag): void
    {
        $this->tags->add($tag);
    }

    public function clearTags(): void
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
    public function addTagsFromArray(array $tags): void
    {
        foreach ($tags as $tag) {
            if (!($tag instanceof ProductTag)) {
                continue;
            }
            $this->addTag($tag);
        }
    }

    /**
     * @return Collection<OptionValue>
     */
    public function getOptionsCollection(): Collection
    {
        return $this->options;
    }


    /**
     * @return (OptionKey|OptionValue[])[][]
     *
     * @psalm-return list<array{key: OptionKey, values?: non-empty-list<OptionValue>}>
     */
    public function getOptions(): array
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
        return array_filter(
            $this->options->toArray(),
            function ($item) use ($optionKey) {
                return $item->getOptionKey() === $optionKey;
            }
        );
    }

    public function clearOptions(): void
    {
        $this->options = new ArrayCollection();
    }

    public function removeOption(OptionValue $optionValue): void
    {
        if ($this->options->contains($optionValue)) {
            $this->options->removeElement($optionValue);
        }
    }

    public function removeOptionByKey(OptionKey $optionKey): void
    {
        /** @var OptionValue $option */
        foreach ($this->options as $option) {
            if ($option->getOptionKey() === $optionKey) {
                $this->options->removeElement($option);
            }
        }
    }

    public function addOption(OptionValue $option): void
    {
        if ($this->options->contains($option)) {
            return;
        }
        $this->options->add($option);
    }

    /**
     * @return Collection<Url>
     */
    public function getUrls(): Collection
    {
        return $this->urls;
    }

    private ?Url $currentUrl = null;

    /**
     * @throws Exception
     */
    public function getCurrentUrl(): Url
    {
        return $this->currentUrl ?? $this->getUrl();
    }

    public function setCurrentUrl(Url $currentUrl = null): void
    {
        $this->currentUrl = $currentUrl;
    }

    /**
     * @return void
     */
    public function addUrl(Url $url)
    {
        if ($this->urls->contains($url)) {
            return;
        }
        $this->urls->add($url);
    }

    /**
     * @throws Exception
     */
    public function getUrl(): Url
    {
        $url = $this->getUrls()->filter(function ($item) {
            return $item->isDefault();
        })->current();

        if ($url instanceof Url) {
            return $url;
        }

        $url = new Url();
        $url->setPath(sprintf('Not set urls for product with id: %d', $this->getId()));
        return $url;
//        throw new \Exception(sprintf('Not set urls for product with id: %d', $this->getId()));
    }

    public function getUrlById(int $id): ?Url
    {
        foreach ($this->getUrls() as $url) {
            if ($url->getId() === $id) {
                return $url;
            }
        }
        return null;
    }

    /**
     * @return Collection<ProductPrice>
     */
    public function getPrices(): Collection
    {
        return $this->prices;
    }

    public function getPrice(string $code): ?ProductPrice
    {
        /** @var ProductPrice $price */
        foreach ($this->prices as $price) {
            if ($price->getPriceGroup()->getCode() === $code) {
                return $price;
            }
        }
        return null;
    }

    public function addPrice(ProductPrice $productPrice = null): void
    {
        if ($productPrice === null) {
            return;
        }

        $productPrice->setProduct($this);
        $this->prices->set($productPrice->getPriceGroup()->getCode(), $productPrice);
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getQuantity(): Quantity
    {
        if ($this->quantity === null) {
            $this->quantity = new Quantity();
            $this->quantity->setProduct($this);
        }
        return $this->quantity;
    }


    public function setQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
    }


    public function getGroup(): ?ProductGroup
    {
        return $this->group;
    }


    public function setGroup(?ProductGroup $group): void
    {
        $this->group = $group;
    }


    public function getUnit(): ?ProductUnit
    {
        return $this->unit;
    }

    public function setUnit(?ProductUnit $unit): void
    {
        $this->unit = $unit;
    }


}
