<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models\Admin\Product;

use App\Module\Admin\Core\ModelInterface;

final class Index implements ModelInterface
{

    public function getContext(): array
    {
        return [];
    }
}