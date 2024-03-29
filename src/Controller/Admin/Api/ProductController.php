<?php

namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
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

class ProductController
{

    /**
     * @var EncoderInterface[]
     */
    private array $encoders;


    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response,
        private UrlGeneratorInterface $urlGenerator,
        private EntityManager $em,
        private Config $config
    ) {
        $this->response = $this->response->withHeader('content-type', 'application/json');
        $this->encoders = [new XmlEncoder(), new JsonEncoder()];
    }

    /**
     * @throws ExceptionInterface
     * @throws NotFoundException
     * @throws QueryException
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
        $criteria = [];

        $serializer = new Serializer([
            new ObjectNormalizer(
                nameConverter: new class() implements NameConverterInterface {

                    public function normalize(string $propertyName): string
                    {
                        return match ($propertyName) {
                            'prices' => 'price',
                            default => $propertyName
                        };
                    }

                    public function denormalize(string $propertyName): string
                    {
                        return match ($propertyName) {
                            default => $propertyName
                        };
                    }
                }
            )
        ], $this->encoders);

        $limit = (int)($this->request->getQueryParams()['length'] ?? 10);

        $page = ((int)($this->request->getQueryParams()['start'] ?? 0) / $limit) + 1;


        /** @var \EnjoysCMS\Module\Catalog\Repositories\Category|EntityRepository $categoryRepository */
        $categoryRepository = $this->em->getRepository(Category::class);


        $categoryId = $this->request->getQueryParams()['categoryId'] ?? '0';
        $categoryCriteria = Criteria::create()
            ->where(
                Criteria::expr()->in(
                    'p.category',
                    $categoryRepository->getAllIds(
                        $categoryRepository->find($categoryId)
                    )
                )
            )
        ;

        if ($categoryId === '0') {
            $categoryCriteria->orWhere(Criteria::expr()->eq('p.category', null));
        }


        $criteria[] = $categoryCriteria;

        $search = (empty(
            $this->request->getQueryParams()['search']['value'] ?? null
        )) ? null : $this->request->getQueryParams()['search']['value'];

        if ($search !== null) {
            $searchCriteria = Criteria::create();
            foreach ($this->config->get('admin->searchFields', []) as $field) {
                $searchCriteria->orWhere(Criteria::expr()->contains($field, $search));
            }
            $criteria[] = $searchCriteria;
        }

        $orders = ['p.id' => 'desc'];
        foreach ($this->request->getQueryParams()['order'] ?? [] as $item) {
            $orders[$this->request->getQueryParams()['columns'][$item['column']]['name']] = $item['dir'];
        }

        $products = $productsService->getProducts(
            page: $page,
            limit: $limit,
            criteria: $criteria,
            orders: $orders
        );
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
                        'urls',
                        'prices',
                        'defaultImage',
                        'images'
                    ],
                    AbstractNormalizer::CALLBACKS => [
                        'defaultImage' => function (?Image $image) {
                            if ($image === null) {
                                return null;
                            }
                            $storage = $this->config->getImageStorageUpload($image->getStorage());
                            return [
                                'original' => $storage->getUrl(
                                    $image->getFilename() . '.' . $image->getExtension()
                                ),
                                'small' => $storage->getUrl(
                                    $image->getFilename() . '_small.' . $image->getExtension()
                                ),
                                'large' => $storage->getUrl(
                                    $image->getFilename() . '_large.' . $image->getExtension()
                                ),
                            ];
                        },
                        'images' => function (Collection $images) {
                            return array_map(function ($image) {
                                /** @var Image $image */
                                $storage = $this->config->getImageStorageUpload($image->getStorage());
                                return [
                                    'original' => $storage->getUrl(
                                        $image->getFilename() . '.' . $image->getExtension()
                                    ),
                                    'small' => $storage->getUrl(
                                        $image->getFilename() . '_small.' . $image->getExtension()
                                    ),
                                    'large' => $storage->getUrl(
                                        $image->getFilename() . '_large.' . $image->getExtension()
                                    ),
                                ];
                            }, $images->toArray());
                        },
                        'prices' => function (Collection $prices) {
                            foreach ($prices as $price) {
                                /** @var ProductPrice $price */
                                if ($price->getPriceGroup()->getCode() === 'PUBLIC') {
                                    return [
                                        'price' => $price->getPrice(),
                                        'currency' => $price->getCurrency()->getCode(),
                                        'format' => $price->format()
                                    ];
                                }
                            }
                            return null;
                        },
                        'urls' => function (Collection $urls) {
                            return array_map(function ($url) {
                                /** @var Url $url */
                                return $this->urlGenerator->generate('catalog/product', [
                                    'slug' => $url->getProduct()->getSlug($url->getPath())
                                ]);
                            }, $urls->toArray());
                        }
                    ],
                ])
            ])
        );

        return $this->response;
    }
}
