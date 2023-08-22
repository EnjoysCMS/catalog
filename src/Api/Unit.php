<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Api;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Module\Catalog\Entity\ProductUnit;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/tools/find-product-units',
    name: '@a/catalog/tools/find-product-units',
    options: [
        'comment' => '[JSON] Получение списка unit'
    ]
)]
final class Unit extends AbstractController
{
    public function __invoke(
        EntityManager $em,
    ) {
        $matched = $em->getRepository(ProductUnit::class)->like(
            $this->request->getQueryParams()['query'] ?? null
        );

        return $this->json([
            'items' => array_map(function ($item) {
                /** @var ProductUnit $item */
                return [
                    'id' => $item->getId(),
                    'title' => $item->getName()
                ];
            }, $matched),
            'total_count' => count($matched)
        ]);
    }
}
