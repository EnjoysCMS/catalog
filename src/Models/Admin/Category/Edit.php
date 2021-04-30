<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Category;


use App\Module\Admin\Core\ModelInterface;

final class Edit implements ModelInterface
{
    public function getContext(): array
    {
        return [];
    }
}