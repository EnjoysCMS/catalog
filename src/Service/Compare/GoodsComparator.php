<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Compare;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\Product;

final class GoodsComparator
{
    private Collection $goods;

    /**
     * @var OptionKey[]
     */
    private array $comparisonKeys = [];

    public function __construct()
    {
        $this->goods = new ArrayCollection();
    }

    public function addProduct(Product $product): void
    {
        if (!$this->goods->contains($product)) {
            $this->goods->add($product);
        }
    }



    /**
     * @param Product[] $products
     * @return void
     */
    public function addProducts(array $products): void
    {
        foreach ($products as $product) {
            $this->addProduct($product);
            foreach ($product->getOptions() as $options) {
                if (!$options['key']->isComparable()){
                    continue;
                }
                if (!in_array($options['key'], $this->comparisonKeys, true)) {
                    $this->comparisonKeys[] = $options['key'];
                }
            }
        }
    }

    public function removeProduct(Product $product): void
    {
        $this->goods->removeElement($product);
    }

    private function mapProductOnComparisonKeys(Product $product): array
    {
        $result['__product'] = $product;

        foreach ($this->comparisonKeys as $comparisonKey) {
            $value = $product->getValuesByOptionKey($comparisonKey);
            $result[$comparisonKey->__toString()] = ($value === [])
                ? null
                : implode(', ', $value);
        }

        return $result;
    }


    public function getComparisonValues(): array
    {
        return array_map(function ($product) {
            return $this->mapProductOnComparisonKeys($product);
        }, $this->goods->toArray());
    }

    /**
     * @return OptionKey[]
     */
    public function getComparisonKeys(): array
    {
        return $this->comparisonKeys;
    }

    public function count(): int
    {
        return count($this->goods);
    }

    /**
     * @return Collection
     */
    public function getGoods(): Collection
    {
        return $this->goods;
    }
}
