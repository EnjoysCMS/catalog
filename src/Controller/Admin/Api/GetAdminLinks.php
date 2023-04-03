<?php

namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;

use EnjoysCMS\Core\Components\AccessControl\ACL;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouteCollection;

#[Route(
    path: 'admin/catalog/api/get-admin-links',
    name: 'catalog/admin/api/get-admin-links',
    options: [
        'comment' => 'API: получение списка административных ссылок '
    ],
    methods: [
        'POST'
    ]
)]
class GetAdminLinks
{

    private const ROUTES = [
        [
            'route' => 'catalog/admin/product/edit',
            'params' => [
                'id'
            ],
            'title' => 'Редактирование',
            'icon' => 'fa fa-edit'
        ],
        [
            'route' => '@a/catalog/product/quantity',
            'params' => [
                'id'
            ],
            'title' => 'Количество',
            'icon' => 'fa fa-cubes'
        ],

        [
            'route' => 'catalog/admin/product/images',
            'params' => [
                'product_id'
            ],
            'title' => 'Управление изображениями',
            'icon' => 'fa fa-image'
        ],
        [
            'route' => '@a/catalog/product/tags',
            'params' => [
                'id'
            ],
            'title' => 'Теги',
            'icon' => 'fa fa-tags'
        ],
        [
            'route' => '@a/catalog/product/options',
            'params' => [
                'id'
            ],
            'title' => 'Параметры',
            'icon' => 'fa fa-list'
        ],
        [
            'route' => '@a/catalog/product/urls',
            'params' => [
                'id'
            ],
            'title' => 'URLs',
            'icon' => 'fa fa-link'
        ],
        [
            'route' => '@a/catalog/product/files',
            'params' => [
                'id'
            ],
            'title' => 'Файлы',
            'icon' => 'fa fa-file'
        ],
        [
            'route' => '@a/catalog/product/prices',
            'params' => [
                'id'
            ],
            'title' => 'Цены',
            'icon' => 'fa fa-dollar-sign'
        ],
        [
            'route' => '@a/catalog/product/meta',
            'params' => [
                'id'
            ],
            'title' => 'SEO',
            'icon' => 'fa fa-globe'
        ],
        [
            'route' => 'catalog/admin/product/delete',
            'params' => [
                'id'
            ],
            'title' => 'Удаление',
            'icon' => 'fa fa-trash',
            'color' => 'danger'
        ],
    ];

    private array $mapParams = [
        'id' => 'id',
        'product_id' => 'id',
    ];

    public function __construct(private ServerRequestInterface $request, private ResponseInterface $response)
    {
        $this->response = $this->response->withHeader('content-type', 'application/json');
    }

    public function __invoke(UrlGeneratorInterface $urlGenerator, ACL $ACL, RouteCollection $routeCollection): ResponseInterface
    {
        $result = [];
        foreach (self::ROUTES as $route) {
            $aclInfo = $this->getAclActionAndCommentByRoute($route['route'], $routeCollection);
            if ($aclInfo === null) {
                continue;
            }
            if (!$ACL->access(...$aclInfo)) {
                continue;
            }
            $result[] = [
                'link' => $urlGenerator->generate($route['route'], $this->buildParamsFroUrlGenerator($route['params'])),
                'title' => $route['title'],
                'icon' => $route['icon'],
                'color' => $route['color'] ?? 'default',
            ];
        }
        $this->response->getBody()->write(json_encode($result));
        return $this->response;
    }

    public static function getRoutes(): array
    {
        return array_map(function ($item){
            return $item['route'];
        }, self::ROUTES);
    }

    private function buildParamsFroUrlGenerator(array $params): array
    {
        $this->request = $this->request->withParsedBody(json_decode($this->request->getBody(), true));
        $result = [];
        foreach ($params as $param) {
            $result[$param] = $this->request->getParsedBody(
            )[$this->mapParams[$param] ?? throw new \InvalidArgumentException(
                sprintf('%s not associated', $param)
            )] ?? null;
        }
        return $result;
    }

    private function getAclActionAndCommentByRoute(string $route, RouteCollection $routeCollection): ?array
    {
        try {
            $routeInfo = $routeCollection->get($route);
            if ($routeInfo === null) {
                throw new InvalidArgumentException(sprintf('Не найден маршрут %s', $route));
            }
            $action = $routeInfo->getDefault('_controller');
            if (is_array($action)) {
                $action = implode('::', $routeInfo->getDefault('_controller'));
            }
            $comment = $routeInfo->getOption('aclComment');
            return [$action, (string)$comment];
        } catch (\Throwable) {
            return null;
        }
    }
}
