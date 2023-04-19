<?php

namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Filter;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[Route(
    path: 'admin/catalog/api/get-category-filters',
    name: 'catalog/admin/api/get-category-filters',
    options: [
        'comment' => 'API: получение списка фильтров категорий '
    ],
    methods: [
        'GET'
    ]
)]
class CategoryFilters
{
    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response
    ) {
        $this->response = $this->response->withHeader('content-type', 'application/json');
    }

    /**
     * @throws NotSupported
     */
    public function __invoke(EntityManager $em): ResponseInterface
    {
        $filters = $em->getRepository(Filter::class)->findBy([
            'category' => $this->request->getQueryParams()['category'] ?? throw new \InvalidArgumentException(
                    sprintf('Category id not sent')
                )
        ]);

        $this->response->getBody()->write(
            json_encode($this->normalizeData($filters))
        );
        return $this->response;
    }

    /**
     * @throws ExceptionInterface
     */
    private function normalizeData(array $filters)
    {
        $encoders = [new JsonEncoder()];
        $serializer = new Serializer([
            new ObjectNormalizer(
                nameConverter: new class() implements NameConverterInterface {

                    public function normalize(string $propertyName): string
                    {
                        return match ($propertyName) {
                            'optionKey' => 'option',
                            default => $propertyName
                        };
                    }

                    public function denormalize(string $propertyName): string
                    {
                        return match ($propertyName) {
                            'option' => 'optionKey',
                            default => $propertyName
                        };
                    }
                }
            )
        ], $encoders);

        return $serializer->normalize($filters, JsonEncoder::FORMAT, [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return match ($object::class) {
                    Category::class => $object->getTitle(),
                };
            },
            AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 1,
            AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
            AbstractNormalizer::ATTRIBUTES => [
                'id',
                'optionKey',
                'type',
                'order'
            ],
            AbstractNormalizer::CALLBACKS => [
                'optionKey' => function (OptionKey $optionKey) {
                    return [
                        'id' => $optionKey->getId(),
                        'name' => $optionKey->__toString(),
                    ];
                }
            ],
        ]);
    }
}
