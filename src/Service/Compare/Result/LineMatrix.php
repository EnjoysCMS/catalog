<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Compare\Result;

use EnjoysCMS\Module\Catalog\Service\Compare\ComparisonGoods;

final class LineMatrix
{

    private bool $removeRepeat = false;
    private bool $removeNull = true;

    public function __construct(private readonly ComparisonGoods $comparisonGoods)
    {
    }

    public function getData(): array
    {
        $mappedGoods = $this->comparisonGoods->getComparisonValues();

        $data = [];
        foreach (array_keys(current($mappedGoods)) as $key) {
            $values = \array_column($mappedGoods, $key);

            if ($this->isRemoveRepeat()) {
                if (\count(\array_unique($values, SORT_REGULAR)) === 1) {
                    continue;
                }
            }

            if ($this->isRemoveNull()) {
                if (\array_filter($values) === []) {
                    continue;
                }
            }

            $data = \array_merge($data, [$key => $values]);
        }
        return $data;
    }

    public function isRemoveRepeat(): bool
    {
        return $this->removeRepeat;
    }

    public function setRemoveRepeat(bool $removeRepeat): LineMatrix
    {
        $this->removeRepeat = $removeRepeat;
        return $this;
    }

    public function isRemoveNull(): bool
    {
        return $this->removeNull;
    }

    public function setRemoveNull(bool $removeNull): LineMatrix
    {
        $this->removeNull = $removeNull;
        return $this;
    }

}
