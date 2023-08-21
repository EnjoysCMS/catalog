<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Events;

use EnjoysCMS\Module\Catalog\Entity\Category;
use Symfony\Contracts\EventDispatcher\Event;

final class PostEditCategoryEvent extends Event
{

    public function __construct(private Category $oldCategory, private Category $newCategory)
    {
    }

    public function getOldCategory(): Category
    {
        return $this->oldCategory;
    }

    public function getNewCategory(): Category
    {
        return $this->newCategory;
    }


}
