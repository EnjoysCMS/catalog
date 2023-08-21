<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Events;

use EnjoysCMS\Module\Catalog\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

final class PostEditProductEvent extends Event
{

    public function __construct(private Product $oldProduct, private Product $newProduct)
    {
    }

    public function getOldProduct(): Product
    {
        return $this->oldProduct;
    }

    public function getNewProduct(): Product
    {
        return $this->newProduct;
    }


}
