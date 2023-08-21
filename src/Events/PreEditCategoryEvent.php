<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Events;

use EnjoysCMS\Module\Catalog\Entity\Category;
use Symfony\Contracts\EventDispatcher\Event;

final class PreEditCategoryEvent extends Event
{

    public function __construct(private Category $category)
    {
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
