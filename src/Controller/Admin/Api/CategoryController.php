<?php

namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductPrice;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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

        $this->response->getBody()->write(
            json_encode(
                ['0' => 'Все категории'] + $categoryRepository->getFormFillArray(
                    $node,
                    $criteria,
                    $orderBy,
                    $direction
                ),
            )
        );

        return $this->response;
    }
}
