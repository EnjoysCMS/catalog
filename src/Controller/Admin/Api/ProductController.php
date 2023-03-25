<?php

namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;

use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Catalog\Config;
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
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
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


    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response,
        private Config $config
    ) {
        $this->response = $this->response->withHeader('content-type', 'application/json');
        $this->encoders = [new XmlEncoder(), new JsonEncoder()];
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
        $serializer = new Serializer([
            new ObjectNormalizer(
                nameConverter: new class() implements NameConverterInterface {

                    public function normalize(string $propertyName): string
                    {
                        return match ($propertyName) {
                            'defaultImage' => 'image',
                            'prices' => 'price',
                            default => $propertyName
                        };
                    }

                    public function denormalize(string $propertyName): string
                    {
                        return match ($propertyName) {
                            'image' => 'defaultImage',
                            default => $propertyName
                        };
                    }
                }
            )
        ], $this->encoders);
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
                        'prices',
                        'defaultImage'
                    ],
                    AbstractNormalizer::CALLBACKS => [
                        'defaultImage' => function ($image) {
                            return $this->config->getImageStorageUpload($image->getStorage())->getUrl(
                                $image->getFilename() . '_small.' . $image->getExtension()
                            );
                        },
                        'prices' => function ($prices) {
                            foreach ($prices as $price) {
                                if ($price->getPriceGroup()->getCode() === 'PUBLIC') {
                                    return [
                                        'price' => $price->getPrice(),
                                        'currency' => $price->getCurrency()->getCode(),
                                        'format' => $price->format()
                                    ];
                                }
                            }
                            return null;
                        }
                    ],
                ])
            ])
        );

        return $this->response;
    }
}
