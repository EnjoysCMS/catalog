<?php

namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController
{


    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response
    ) {
        $this->response = $this->response->withHeader('content-type', 'application/json');
    }

    /**
     * @param EntityManager $em
     * @return ResponseInterface
     * @throws QueryException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    #[Route(
        path: 'admin/catalog/api/get-category-tree',
        name: 'catalog/admin/api/get-category-tree',
        options: [
            'comment' => 'API: получение списка категорий '
        ],
        methods: [
            'GET'
        ]
    )]
    public function getCategoryListForFormSelectElement(EntityManager $em): ResponseInterface
    {
        /** @var \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository $categoryRepository */
        $categoryRepository = $em->getRepository(Category::class);

        $node = null;
        $criteria = [];
        $orderBy = 'sort';
        $direction = 'asc';

        $categories = $categoryRepository->getFormFillArray(
            $node,
            $criteria,
            $orderBy,
            $direction
        );


        $this->response->getBody()->write(
            json_encode(
                array_merge([['id' => 0, 'title' => 'Все категории']],
                    array_map(function ($id, $title) {
                        return [
                            'id' => (string)$id,
                            'title' => $title
                        ];
                    }, array_keys($categories), $categories)),
            )
        );

        return $this->response;
    }

}
