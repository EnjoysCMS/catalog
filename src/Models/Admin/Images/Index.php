<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models\Admin\Images;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Entities\Product;

final class Index implements ModelInterface
{

    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getContext(): array
    {

        return [
            'products' => ''//$this->entityManager->getRepository(Product::class)->findAll()
        ];
    }
}