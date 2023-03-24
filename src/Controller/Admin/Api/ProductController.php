<?php

namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;

use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ProductController
{

    /**
     * @var EncoderInterface[]
     */
    private array $encoders;
    /**
     * @var AbstractNormalizer[]
     */
    private array $normalizers;

    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response
    ) {
        $this->response = $this->response->withHeader('content-type', 'application/json');
        $this->encoders = [new XmlEncoder(), new JsonEncoder()];
        $this->normalizers = [new ObjectNormalizer()];
    }

    /**
     * @throws ExceptionInterface
     * @throws NotFoundException
     */
    #[Route(
        path: 'admin/catalog/api/get-products',
        name: 'catalog/admin/api/get-products',
        options: [
            'comment' => 'API: получение списка продуктов'
        ],
        methods: [
            'GET'
        ]
    )]
    public function getProducts(ProductService $productsService): ResponseInterface
    {
        $serializer = new Serializer($this->normalizers, $this->encoders);
        $limit = (int)($this->request->getQueryParams()['length'] ?? 10);
        $page = ((int)($this->request->getQueryParams()['start'] ?? 0) / $limit) + 1;
        $products = $productsService->getProducts($page, $limit);

        $this->response->getBody()->write(
            json_encode([
                'draw' => $this->request->getQueryParams()['draw'] ?? null,
                'recordsTotal' => $products['pagination']->getTotalItems(),
                'recordsFiltered' => $products['pagination']->getTotalItems(),
                'data' => $serializer->normalize($products['products'], JsonEncoder::FORMAT, [
                    AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                        return match ($object::class) {
                            Category::class => $object->getTitle(),
                            Product::class => $object->getName(),
                        };
                    },
                    AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 1,
                    AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
                    AbstractNormalizer::ATTRIBUTES => [
                        'id',
                        'name',
                        'productCode',
                        'hide',
                        'active',
                        'category' => [
                            'title',
                            'slug',
                            'breadcrumbs'
                        ],
                        'slug',
                        'defaultImage' => [
                            'filename',
                            'extension',
                            'storage'
                        ],
                    ]
                ])
            ])
        );

        return $this->response;
    }
}
