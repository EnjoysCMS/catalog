<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller\Admin;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\Mapping\MappingException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Crud\Category\Add;
use EnjoysCMS\Module\Catalog\Crud\Category\Delete;
use EnjoysCMS\Module\Catalog\Crud\Category\Edit;
use EnjoysCMS\Module\Catalog\Crud\Category\Index;
use EnjoysCMS\Module\Catalog\Crud\Category\SetExtraFieldsToChildren;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('admin/catalog/category', '@catalog_admin_category_')]
final class Category extends AdminController
{

    /**
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route(
        name: 'list',
        comment: 'Просмотр списка категорий в админке'
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
     * @throws ExceptionRule
     * @throws ORMException
     * @throws RuntimeError
     * @throws DependencyException
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotFoundException
     */
    #[Route(
        path: '/add',
        name: 'add',
        comment: 'Добавление категорий'
    )]
    public function add(Add $add): ResponseInterface
    {
        $this->breadcrumbs->add('@catalog_admin_category_list', 'Список категорий');
        $this->breadcrumbs->setLastBreadcrumb('Добавление новой категории');

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/addcategory.twig',
                $add->getContext()
            )
        );
    }


    /**
     * @throws ExceptionRule
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws DependencyException
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotFoundException
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route(
        path: '/edit',
        name: 'edit',
        comment: 'Редактирование категорий'
    )]
    public function edit(Edit $edit): ResponseInterface
    {
        $context = $edit->getContext();

        $this->breadcrumbs->add('@catalog_admin_category_list', 'Список категорий');
        $this->breadcrumbs->setLastBreadcrumb(
            sprintf("Редактирование категории [%s]", $context['title'])
        );

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/editcategory.twig',
                $context
            )
        );
    }


    /**
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws ORMException
     * @throws MappingException
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/delete',
        name: 'delete',
        comment: 'Удаление категорий'
    )]
    public function delete(Delete $delete): ResponseInterface
    {
        $context = $delete->getContext();

        $this->breadcrumbs->add('@catalog_admin_category_list', 'Список категорий');
        $this->breadcrumbs->setLastBreadcrumb(
            sprintf("Удаление категории [%s]", $context['title'])
        );

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                $context
            )
        );
    }



    /**
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws ExceptionRule
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: '/set-extra-fields-to-children',
        name: 'set-extra-fields-to-children',
        comment: '[ADMIN] Установка extra fields всем дочерним категориям'
    )]
    public function setExtraFieldsToAllChildren(SetExtraFieldsToChildren $setExtraFieldsToChildren): ResponseInterface
    {
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                $setExtraFieldsToChildren->getContext()
            )
        );
    }
}
