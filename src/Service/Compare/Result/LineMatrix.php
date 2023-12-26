<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Compare\Result;

use EnjoysCMS\Module\Catalog\Service\Compare\ComparisonGoods;

final class LineMatrix
{

    public function __construct(private readonly ComparisonGoods $comparisonGoods)
    {
    }

    public function getData(): array
    {
        $mappedGoods = $this->comparisonGoods->getComparisonValues();

        $data = [];
        foreach (array_keys(current($mappedGoods)) as $key) {
            $values = \array_column($mappedGoods, $key);
            if (\array_filter($values) === []) {
                continue;
            }
            $data = \array_merge($data, [$key => $values]);
        }
        return $data;
    }

}
