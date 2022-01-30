<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin;


use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Crud\Product\Add;
use EnjoysCMS\Module\Catalog\Crud\Product\Delete;
use EnjoysCMS\Module\Catalog\Crud\Product\Edit;
use EnjoysCMS\Module\Catalog\Crud\Product\Index;
use EnjoysCMS\Module\Catalog\Crud\Product\Tags\TagsList;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Product extends BaseController
{

    private string $templatePath;

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->templatePath = Template::getAdminTemplatePath();
    }

    /**
     * @Route(
     *     path="admin/catalog/products",
     *     name="catalog/admin/products",
     *     options={
     *      "aclComment": "Просмотр товаров в админке"
     *     }
     * )
     * @return string
     */
    public function index(): string
    {
        return $this->view(
            $this->templatePath . '/products.twig',
            $this->getContext($this->container->get(Index::class))
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/product/add",
     *     name="catalog/admin/product/add",
     *     options={
     *      "aclComment": "Добавление товара"
     *     }
     * )
     * @return string
     */
    public function add(): string
    {
        return $this->view(
            $this->templatePath . '/addproduct.twig',
            $this->getContext($this->container->get(Add::class))
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/product/edit",
     *     name="catalog/admin/product/edit",
     *     options={
     *      "aclComment": "Редактирование товара"
     *     }
     * )
     * @return string
     */
    public function edit(): string
    {
        return $this->view(
            $this->templatePath . '/editproduct.twig',
            $this->getContext($this->container->get(Edit::class))
        );
    }

    /**
     * @Route(
     *     path="admin/catalog/product/delete",
     *     name="catalog/admin/product/delete",
     *     options={
     *      "aclComment": "Удаление товара"
     *     }
     * )
     * @return string
     */
    public function delete(): string
    {
        return $this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(Delete::class))
        );
    }

    /**
     * @Route(
     *     path="admin/catalog/product/tags",
     *     name="@a/catalog/product/tags",
     *     options={
     *      "aclComment": "Просмотр тегов товара"
     *     }
     * )
     * @return string
     */
    public function manageTags(): string
    {
        return $this->view(
            $this->templatePath . '/product/tags/tags_list.twig',
            $this->getContext($this->container->get(TagsList::class))
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/tools/find-products",
     *     name="@a/catalog/tools/find-products",
     *     options={
     *      "aclComment": "[JSON] Получение списка продукции (поиск)"
     *     }
     * )
     */
    public function findProductsByLike(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        Response $response,
        SapiEmitter $emitter
    ) {
        $matched = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class)->like(
            $serverRequest->get(
                'query'
            )
        );
        $response = $response->withHeader('content-type', 'application/json');

        $result = [
            'items' => array_map(function ($item) {
                /** @var \EnjoysCMS\Module\Catalog\Entities\Product $item */
                return [
                    'id' => $item->getId(),
                    'title' => $item->getName(),
                    'category' => $item->getCategory()->getFullTitle()
                ];
            }, $matched),
            'total_count' => count($matched)
        ];
        $response->getBody()->write(
            json_encode($result)
        );
        $emitter->emit($response);
    }


}
