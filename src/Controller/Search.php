<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Breadcrumbs\BreadcrumbCollection;
use EnjoysCMS\Core\Pagination\Pagination;
use EnjoysCMS\Core\Setting\Setting;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\Category;
use EnjoysCMS\Module\Catalog\Entity\Image;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\OptionValue;
use EnjoysCMS\Module\Catalog\Entity\Product;
use EnjoysCMS\Module\Catalog\Service\Search\DefaultSearch;
use EnjoysCMS\Module\Catalog\Service\Search\SearchInterface;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use function trim;

final class Search extends PublicController
{
    private array $optionKeys;
    private SearchInterface $searchClass;


    /**
     * @throws NotSupported
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(
        ServerRequestInterface $request,
        Environment $twig,
        Config $config,
        Setting $setting,
        ResponseInterface $response,
        Container $container
    ) {
        parent::__construct($request, $twig, $config, $setting, $response);

        $this->optionKeys = explode(',', $this->config->getSearchOptionField());
        $this->searchClass = $container->get($config->get('searchClass', DefaultSearch::class));
        $this->searchClass->setOptionKeys($this->optionKeys);
        $this->searchClass->setSearchQuery($this->getSearchQuery());
    }

    private function getSearchQuery(): string
    {
        $searchQuery = trim($this->request->getQueryParams()['q'] ?? $this->request->getParsedBody()['q'] ?? '');

        if (mb_strlen($searchQuery) < $this->config->get('minSearchChars', 3)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Слишком короткое слово для поиска (нужно минимум %s символа)',
                    $this->config->get('minSearchChars', 3)
                )
            );
        }

        return $searchQuery;
    }


    #[Route(
        path: '/catalog/api/search/',
        name: 'catalog/api/search'
    )]
    public function apiSearch(): ResponseInterface
    {
        $serializer = new Serializer(
            normalizers: [new ObjectNormalizer()],
            encoders: [new JsonEncoder()]
        );

        try {
            $pagination = new Pagination(
                $this->request->getQueryParams()['page'] ?? 1, $this->config->get('limitItems', 30)
            );
            $searchResult = $this->searchClass->getResult($pagination->getOffset(), $pagination->getLimitItems());

            $response = $this->responseJson(
                $serializer->normalize(
                    $searchResult,
                    JsonEncoder::FORMAT,
                    [
                        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                            return match ($object::class) {
                                Category::class => $object->getTitle(),
                                Product::class => $object->getName(),
                            };
                        },
                        AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 1,
                        AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
                        AbstractNormalizer::ATTRIBUTES => [
                            'searchQuery',
                            'countResult',
                            //'optionKeys',
                            'result' => [
                                'id',
                                'name',
                                'slug',
                                //'prices',
                                'defaultImage',
                                'options'
                            ]
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
                            'options' => function (array $options) {
                                $result = [];
                                /** @var list<array{key: OptionKey, values?: non-empty-list<OptionValue>}> $options */
                                foreach ($options as $option) {
                                    if (!in_array($option['key']->getId(), $this->optionKeys)) {
                                        continue;
                                    }
                                    $result[] = [
                                        'key' => $option['key']->getName(),
                                        'unit' => $option['key']->getUnit(),
                                        'values' => array_map(function ($item) {
                                            return $item->getValue();
                                        }, $option['values'] ?? []),
                                        'optionName' => $option['key']->__toString(),
                                    ];
                                }
                                return $result;
                            }
                        ]
                    ]
                )
            );
        } catch (Exception|Throwable $e) {
            $response = $this->responseJson(['error' => $e->getMessage()]);
        } finally {
            return $response;
        }
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route(
        path: '/catalog/search',
        name: 'catalog/search',
        priority: 2
    )]
    public function search(
        BreadcrumbCollection $breadcrumbs,
        UrlGeneratorInterface $urlGenerator
    ): ResponseInterface {
        $pagination = new Pagination(
            $this->request->getQueryParams()['page'] ?? 1, $this->config->get('limitItems', 30)
        );

        try {
            $searchResult = $this->searchClass->getResult($pagination->getOffset(), $pagination->getLimitItems());
            $pagination->setTotalItems($searchResult->getCountResult());
        } catch (Throwable $e) {
            $searchResult = [
                'error' => $e
            ];
        }

        $breadcrumbs->add($urlGenerator->generate('catalog/index'), 'Каталог');
        $breadcrumbs->setLastBreadcrumb('Поиск');

        $template_path = '@m/catalog/search.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/search.twig';
        }

        return $this->responseText(
            $this->twig->render($template_path, [
                'pagination' => $pagination,
                'searchResult' => $searchResult,
                'breadcrumbs' => $breadcrumbs
            ])
        );
    }
}
