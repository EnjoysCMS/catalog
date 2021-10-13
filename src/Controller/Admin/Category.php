<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use App\Module\Admin\BaseController;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Helpers\Template;
use EnjoysCMS\Module\Catalog\Models\Admin\Category\Add;
use EnjoysCMS\Module\Catalog\Models\Admin\Category\Delete;
use EnjoysCMS\Module\Catalog\Models\Admin\Category\Edit;
use EnjoysCMS\Module\Catalog\Models\Admin\Category\Index;
use EnjoysCMS\Module\Catalog\Models\Admin\Category\SetExtraFieldsToChildren;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Category extends BaseController
{

    private string $templatePath;

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct($this->container);
        $this->templatePath = Template::getAdminTemplatePath();
    }


    /**
     * @Route(
     *     path="admin/catalog/category",
     *     name="catalog/admin/category",
     *     options={
     *      "aclComment": "Просмотр списка категорий в админке"
     *     }
     * )
     * @return string
     */
    public function index(): string
    {
        return $this->view(
            $this->templatePath . '/category.twig',
            $this->getContext($this->container->get(Index::class))
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/category/add",
     *     name="catalog/admin/category/add",
     *     options={
     *      "aclComment": "Добавление категорий"
     *     }
     * )
     * @return string
     */
    public function add(): string
    {
        return $this->view(
            $this->templatePath . '/addcategory.twig',
            $this->getContext($this->container->get(Add::class))
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/category/edit",
     *     name="catalog/admin/category/edit",
     *     options={
     *      "aclComment": "Редактирование категорий"
     *     }
     * )
     * @return string
     */
    public function edit(): string
    {
        return $this->view(
            $this->templatePath . '/editcategory.twig',
            $this->getContext($this->container->get(Edit::class))
        );
    }


    /**
     * @Route(
     *     path="admin/catalog/category/delete",
     *     name="catalog/admin/category/delete",
     *     options={
     *      "aclComment": "Удаление категорий"
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
     *     path="admin/catalog/tools/category/get-extra-fields",
     *     name="@a/catalog/tools/category/get-extra-fields",
     *     options={
     *      "aclComment": "[JSON] Получение списка extra fields"
     *     }
     * )
     * @throws NoResultException
     */
    public function getExtraFieldsJson(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        Response $response,
        SapiEmitter $emitter
    ) {
        $result = [];

        /** @var \EnjoysCMS\Module\Catalog\Entities\Category $category */
        $category = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class)->find(
            $serverRequest->post('id')
        );

        if ($category === null) {
            throw new NoResultException();
        }

        $extraFields = $category->getParent()?->getExtraFields() ?? [];

        foreach ($extraFields as $key) {
            $result[$key->getId()] = $key->getName() . (($key->getUnit()) ? ' (' . $key->getUnit() . ')' : '');
        }

        $response = $response->withHeader('content-type', 'application/json');
        $response->getBody()->write(json_encode($result));
        $emitter->emit($response);
    }

    /**
     * @Route(
     *     path="admin/catalog/tools/category/set-extra-fields-to-children",
     *     name="@a/catalog/tools/category/set-extra-fields-to-children",
     *     options={
     *      "aclComment": "[ADMIN] Установка extra fields всем дочерним категориям"
     *     }
     * )
     */
    public function setExtraFieldsToAllChildren() {
        return $this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(SetExtraFieldsToChildren::class))
        );
    }
}