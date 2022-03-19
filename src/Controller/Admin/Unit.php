<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Entities\ProductUnit;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/tools/find-product-units',
    name: '@a/catalog/tools/find-product-units',
    options: [
        'aclComment' => '[JSON] Получение списка unit'
    ]
)]
final class Unit extends AdminController
{
    public function __invoke(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        Response $response,
        SapiEmitter $emitter
    ) {
        $matched = $entityManager->getRepository(ProductUnit::class)->like(
            $serverRequest->get(
                'query'
            )
        );

        $result = [
            'items' => array_map(function ($item) {
                /** @var ProductUnit $item */
                return [
                    'id' => $item->getId(),
                    'title' => $item->getName()
                ];
            }, $matched),
            'total_count' => count($matched)
        ];

        return $this->responseJson($result);
    }
}
