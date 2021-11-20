<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Helpers;


class Normalize
{
    public static function toFloat(int|float|string $str): float
    {
        return (float)str_replace(',', '.', (string)$str);
    }

    public static function toPrice(int|float|string $num): int
    {
        return (int)round((Normalize::toFloat($num) * 100));
    }
}