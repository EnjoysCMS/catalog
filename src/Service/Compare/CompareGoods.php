<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Compare;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use EnjoysCMS\Module\Catalog\Entity\Product;

final class CompareGoods
{


    private Collection $goods;

    /**
     * @var CompareKey[]
     */
    private array $compareKeys;

    public function __construct()
    {
        $this->goods = new ArrayCollection();


        $this->compareKeys = [
            new CompareKey('name', ''),
            new CompareKey('vendor', 'Производитель'),
            new CompareKey('vendorCode', 'Артикул'),
            new CompareKey('price', 'Цена', ['ROZ']),
        ];
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
                $compareKey = new CompareKey('valuesByOptionKey', (string)$options['key'], [$options['key']]);
                if (!in_array($compareKey, $this->compareKeys)) {
                    $this->compareKeys[] = $compareKey;
                }
            }
        }
    }


    public function removeProduct(Product $product): void
    {
        $this->goods->removeElement($product);
    }

    private function mapProductOnCompareKeys(Product $product): array
    {
        $result = [];

        foreach ($this->compareKeys as $compareKey) {
            $method = 'get' . ucfirst($compareKey->key);
            $value = $product->$method(...$compareKey->args);

            $result[$compareKey->value] = ($value === '' || $value === [])
                ? null
                : (string)(is_array($value)
                    ? implode(', ', $value)
                    : $value
                );
        }

        return $result;
    }

    public function getComparedTable()
    {
        $result = array_map(function ($product) {
            return $this->mapProductOnCompareKeys($product);
        }, $this->goods->toArray());

        dd($this->compareKeys, $result);
    }
}
