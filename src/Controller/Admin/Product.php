<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Admin\Product\Add;
use EnjoysCMS\Module\Catalog\Admin\Product\Delete;
use EnjoysCMS\Module\Catalog\Admin\Product\Edit;
use EnjoysCMS\Module\Catalog\Admin\Product\Tags\TagsList;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Product extends AdminController
{





    #[Route(
        path: 'admin/catalog/tools/find-products',
        name: '@a/catalog/tools/find-products',
        options: [
            'comment' => '[JSON] Получение списка продукции (поиск)'
        ]
    )]
    public function findProductsByLike(
        EntityManager $entityManager,
        ServerRequestInterface $request
    ): ResponseInterface {
        $matched = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class)->like(
            $request->getQueryParams()['query'] ?? null
        );

        $result = [
            'items' => array_map(function ($item) {
                /** @var \EnjoysCMS\Module\Catalog\Entities\Product $item */
                return [
                    'id' => $item->getId(),
                    'title' => $item->getName(),
                    'category' => $item->getCategory()->getFullTitle()
                ];
            }, $matched),
            'total_count' => count($matched)
        ];
        return $this->jsonResponse($result);
    }


}
