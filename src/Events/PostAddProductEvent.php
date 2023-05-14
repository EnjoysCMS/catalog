<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Events;

use EnjoysCMS\Module\Catalog\Entities\Product;
use Symfony\Contracts\EventDispatcher\Event;

final class PostAddProductEvent extends Event
{

    public function __construct(private Product $product)
    {
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

}
