<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use EnjoysCMS\Module\Catalog\Crud\Category\Add;
use EnjoysCMS\Module\Catalog\Crud\Category\Delete;
use EnjoysCMS\Module\Catalog\Crud\Category\Edit;
use EnjoysCMS\Module\Catalog\Crud\Category\Index;
use EnjoysCMS\Module\Catalog\Crud\Category\SaveCategoryStructure;
use EnjoysCMS\Module\Catalog\Crud\Category\SetExtraFieldsToChildren;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final class Category extends AdminController
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/category',
        name: 'catalog/admin/category',
        options: [
            'comment' => 'Просмотр списка категорий в админке'
        ]
    )]
    public function index(Index $index): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Категории');

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/category.twig',
                $index->getContext()
            )
        );
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/category/add',
        name: 'catalog/admin/category/add',
        options: [
            'comment' => 'Добавление категорий'
        ]
    )]
    public function add(Add $add): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/addcategory.twig',
                $add->getContext()
            )
        );
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/category/edit',
        name: 'catalog/admin/category/edit',
        options: [
            'comment' => 'Редактирование категорий'
        ]
    )]
    public function edit(Edit $edit): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/editcategory.twig',
                $edit->getContext()
            )
        );
    }

    /**
     * @throws ORMException
     */
    #[Route(
        path: 'admin/catalog/category/save_category_structure',
        name: 'catalog/admin/category/save_category_structure',
        options: [
            'comment' => 'Редактирование категорий (структура, json)'
        ],
        methods: [
            'post'
        ]
    )]
    public function saveCategoryStructure(EntityManager $em, ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->container->call(
                SaveCategoryStructure::class,
                ['data' => \json_decode($request->getBody()->getContents())]
            );
            $em->flush();
            return $this->jsonResponse('saved');
        } catch (\Throwable $e) {
            $response = $this->jsonResponse($e->getMessage());
            return $response->withStatus(401);
        }
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(
        path: 'admin/catalog/category/delete',
        name: 'catalog/admin/category/delete',
        options: [
            'comment' => 'Удаление категорий'
        ]
    )]
    public function delete(Delete $delete): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                $delete->getContext()
            )
        );
    }


    /**
     * @throws NoResultException
     */
    #[Route(
        path: 'admin/catalog/tools/category/get-extra-fields',
        name: '@a/catalog/tools/category/get-extra-fields',
        options: [
            'comment' => '[JSON] Получение списка extra fields'
        ]
    )]
    public function getExtraFieldsJson(
        EntityManager $entityManager,
        ServerRequestInterface $request
    ): ResponseInterface {
        $result = [];

        /** @var \EnjoysCMS\Module\Catalog\Entities\Category $category */
        $category = $entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Category::class)->find(
            $request->getParsedBody()['id'] ?? ''
        );

        if ($category === null) {
            throw new NoResultException();
        }

        $extraFields = $category->getParent()?->getExtraFields() ?? [];

        foreach ($extraFields as $key) {
            $result[$key->getId()] = $key->getName() . (($key->getUnit()) ? ' (' . $key->getUnit() . ')' : '');
        }

        return $this->jsonResponse($result);
    }

    #[Route(
        path: 'admin/catalog/tools/category/set-extra-fields-to-children',
        name: '@a/catalog/tools/category/set-extra-fields-to-children',
        options: [
            'comment' => '[ADMIN] Установка extra fields всем дочерним категориям'
        ]
    )]
    public function setExtraFieldsToAllChildren(SetExtraFieldsToChildren $setExtraFieldsToChildren): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                $setExtraFieldsToChildren->getContext()            )
        );
    }
}
