<?php

namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;

use DI\Container;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\Category\SaveCategoryStructure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function json_decode;

#[Route('admin/catalog/api/category', '@catalog_admin_api~category_')]
class Category
{

    public function __construct(
        private ResponseInterface $response,
        private readonly Container $container,
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
        path: '/get/tree',
        name: 'get_tree',
        methods: [
            'GET'
        ],
        comment: 'API: получение списка категорий '
    )]
    public function getCategoryListForFormSelectElement(EntityManager $em): ResponseInterface
    {
        /** @var \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository $categoryRepository */
        $categoryRepository = $em->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class);

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

    #[Route(
        path: '/structure/save',
        name: 'save_structure',
        methods: [
            'post'
        ],
        comment: 'Редактирование категорий (структура, json)'
    )]
    public function saveCategoryStructure(EntityManager $em, ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->container->call(
                SaveCategoryStructure::class,
                ['data' => json_decode($request->getBody()->getContents())]
            );
            $em->flush();
            $this->response->getBody()->write(
                json_encode('saved')
            );
            return $this->response;
        } catch (Throwable $e) {
            $this->response->getBody()->write(
                json_encode($e->getMessage())
            );
            return $this->response->withStatus(401);
        }
    }

    /**
     * @throws NotSupported
     * @throws NoResultException
     */
    #[Route(
        path: '/extra-fields/get',
        name: 'get_extra_fields',
        comment: '[JSON] Получение списка extra fields'
    )]
    public function getExtraFieldsJson(
        EntityManager $em,
        ServerRequestInterface $request
    ): ResponseInterface {
        $result = [];

        /** @var \EnjoysCMS\Module\Catalog\Entities\Category $category */
        $category = $em->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class)->find(
            $request->getParsedBody()['id'] ?? ''
        );

        if ($category === null) {
            throw new NoResultException();
        }

        $extraFields = $category->getParent()?->getExtraFields() ?? [];

        foreach ($extraFields as $key) {
            $result[$key->getId()] = $key->getName() . (($key->getUnit()) ? ' (' . $key->getUnit() . ')' : '');
        }

        $this->response->getBody()->write(
            json_encode($result)
        );
        return $this->response;
    }

}
