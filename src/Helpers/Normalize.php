<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Helpers;


class Normalize
{
    public static function toFloat(int|float|string $str): float
    {
        return (float)str_replace(',', '.', (string)$str);
    }

    /**
     * @deprecated
     */
    public static function toPrice(int|float|string $num): int
    {
        return self::floatPriceToInt($num);
    }

    public static function floatPriceToInt(int|float|string $num): int
    {
        return (int)round((Normalize::toFloat($num) * 100));
    }

    public static function intPriceToFloat(int|float $num): int|float
    {
        return $num / 100;
    }
}
