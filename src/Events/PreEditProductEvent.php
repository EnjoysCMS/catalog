<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Events;

use EnjoysCMS\Module\Catalog\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

final class PreEditProductEvent extends Event
{

    public function __construct(private Product $product)
    {
    }

    public function getProduct(): Product
    {
        return $this->product;
    }
}
