<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Compare;

final class CompareKey
{
    public function __construct(
        public readonly string|int $key,
        public readonly string $value,
        public readonly array $args = []
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
