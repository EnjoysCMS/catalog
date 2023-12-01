<?php

namespace EnjoysCMS\Module\Catalog\Admin\Product\Options;

enum OptionType
{
    case ENUM;
    case TEXT;
    case NUMERIC;
    case BOOL;

    /**
     * @throws \ReflectionException
     */
    public static function tryFrom(int|string $name): ?OptionType
    {
        $reflection = new \ReflectionEnum(OptionType::class);

        return $reflection->hasCase($name)
            ? $reflection->getCase($name)->getValue()
            : null;
    }

    /**
     * @throws \ReflectionException
     */
    public static function from(int|string $name): OptionType
    {
        $reflection = new \ReflectionEnum(OptionType::class);

        return $reflection->hasCase($name)
            ? $reflection->getCase($name)->getValue()
            : throw new \ValueError(sprintf('"%s" is not a valid backing value for enum "%s"', $name, __CLASS__));
    }

    public static function toArray(): array
    {
        return array_column(OptionType::cases(), 'name');
    }
}

