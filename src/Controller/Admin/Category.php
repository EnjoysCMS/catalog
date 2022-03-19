<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Crud\Category\Add;
use EnjoysCMS\Module\Catalog\Crud\Category\Delete;
use EnjoysCMS\Module\Catalog\Crud\Category\Edit;
use EnjoysCMS\Module\Catalog\Crud\Category\Index;
use EnjoysCMS\Module\Catalog\Crud\Category\SetExtraFieldsToChildren;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Category extends AdminController
{

    /**
     * @Route(
     *     path="admin/catalog/category",
     *     name="catalog/admin/category",
     *     options={
     *      "aclComment": "Просмотр списка категорий в админке"
     *     }
     * )
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/category.twig',
            $this->getContext($this->container->get(Index::class))
        ));
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function add(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/addcategory.twig',
            $this->getContext($this->container->get(Add::class))
        ));
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function edit(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/editcategory.twig',
            $this->getContext($this->container->get(Edit::class))
        ));
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function delete(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(Delete::class))
        ));
    }


    /**
     * @Route (
     *     path="admin/catalog/tools/category/get-extra-fields",
     *     name="@a/catalog/tools/category/get-extra-fields",
     *     options={
     *      "aclComment": "[JSON] Получение списка extra fields"
     *     }
     * )
     *
     * @throws NoResultException
     */
    public function getExtraFieldsJson(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
    ): ResponseInterface {
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

        return $this->responseJson($result);
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
    public function setExtraFieldsToAllChildren(): ResponseInterface
    {
        return $this->responseText($this->view(
            $this->templatePath . '/form.twig',
            $this->getContext($this->container->get(SetExtraFieldsToChildren::class))
        ));
    }
}
