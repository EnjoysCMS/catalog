<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Admin\Category;

use DeepCopy\DeepCopy;
use DeepCopy\Filter\Doctrine\DoctrineCollectionFilter;
use DeepCopy\Matcher\PropertyTypeMatcher;
use DI\Container;
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
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use EnjoysCMS\Core\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\Category;
use EnjoysCMS\Module\Catalog\Events\PostAddCategoryEvent;
use EnjoysCMS\Module\Catalog\Events\PostDeleteCategoryEvent;
use EnjoysCMS\Module\Catalog\Events\PostEditCategoryEvent;
use EnjoysCMS\Module\Catalog\Events\PreAddCategoryEvent;
use EnjoysCMS\Module\Catalog\Events\PreDeleteCategoryEvent;
use EnjoysCMS\Module\Catalog\Events\PreEditCategoryEvent;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('admin/catalog/category', '@catalog_category_')]
final class Controller extends AdminController
{
    public function __construct(Container $container, Config $config, \EnjoysCMS\Module\Admin\Config $adminConfig)
    {
        parent::__construct($container, $config, $adminConfig);
        $this->breadcrumbs->add('@catalog_category_list', 'Список категорий');
    }

    /**
     * @throws DependencyException
     * @throws LoaderError
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws QueryException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws NotSupported
     */
    #[Route(
        name: 'list',
        comment: 'Просмотр списка категорий в админке'
    )]
    public function index(EntityManager $em): ResponseInterface
    {
        $this->breadcrumbs->remove('@catalog_category_list')->setLastBreadcrumb('Категории');

        /** @var \EnjoysCMS\Module\Catalog\Repository\Category $repository */
        $repository = $em->getRepository(Category::class);

        $form = new Form();
        $form->hidden('nestable-output')->setAttribute(AttributeFactory::create('id', 'nestable-output'));
        $form->submit('save', 'Сохранить');


        if ($form->isSubmitted()) {
            (new SaveCategoryStructure($em))
            (
                json_decode($this->request->getParsedBody()['nestable-output'] ?? '')
            );

            $em->flush();
            return $this->redirect->toRoute('@catalog_category_list');
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/category.twig',
                [
                    'form' => $rendererForm->output(),
                    'categories' => $repository->getChildNodes(),
                ]
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
    public function add(Add $add, ContentEditor $contentEditor): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Добавление новой категории');

        $form = $add->getForm();

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new PreAddCategoryEvent());
            $category = $add->doAction();
            $this->dispatcher->dispatch(new PostAddCategoryEvent($category));
            return $this->redirect->toRoute('@catalog_category_list');
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/addcategory.twig',
                [
                    'subtitle' => 'Добавление категории',
                    'form' => $rendererForm,
                    'editorEmbedCode' => $contentEditor
                            ->withConfig($this->config->getEditorConfigCategoryDescription())
                            ->setSelector('#description')
                            ->getEmbedCode()
                        . $contentEditor
                            ->withConfig($this->config->getEditorConfigCategoryShortDescription())
                            ->setSelector('#shortDescription')
                            ->getEmbedCode(),
                ]
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
    public function edit(Edit $edit, ContentEditor $contentEditor): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb(
            sprintf("Редактирование категории [%s]", $edit->getCategory()->getTitle())
        );

        $form = $edit->getForm();

        if ($form->isSubmitted()) {
            $copier = new DeepCopy();
            $copier->addFilter(
                new DoctrineCollectionFilter(),
                new PropertyTypeMatcher('Doctrine\Common\Collections\Collection')
            );
            /** @var Category $oldCategory */
            $oldCategory = $copier->copy($edit->getCategory());
            $this->dispatcher->dispatch(new PreEditCategoryEvent($oldCategory));
            $edit->doAction();
            $this->dispatcher->dispatch(new PostEditCategoryEvent($oldCategory, $edit->getCategory()));
            return $this->redirect->toRoute('@catalog_category_list');
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/editcategory.twig',
                [
                    'title' => $edit->getCategory()->getTitle(),
                    'subtitle' => 'Изменение категории',
                    'form' => $rendererForm,
                    'editorEmbedCode' => $contentEditor
                            ->withConfig($this->config->getEditorConfigCategoryDescription())
                            ->setSelector('#description')
                            ->getEmbedCode()
                        . $contentEditor
                            ->withConfig($this->config->getEditorConfigCategoryShortDescription())
                            ->setSelector('#shortDescription')
                            ->getEmbedCode(),

                ]
            )
        );
    }


    /**
     * @throws DependencyException
     * @throws LoaderError
     * @throws MappingException
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '/delete',
        name: 'delete',
        comment: 'Удаление категорий'
    )]
    public function delete(Delete $delete): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb(
            sprintf("Удаление категории [%s]", $delete->getCategory()->getTitle())
        );

        $form = $delete->getForm();

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new PreDeleteCategoryEvent($delete->getCategory()));
            $delete->doAction();
            $this->dispatcher->dispatch(new PostDeleteCategoryEvent($delete->getCategory()));
            return $this->redirect->toRoute('@catalog_category_list');
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                [
                    'title' => $delete->getCategory()->getTitle(),
                    'subtitle' => 'Удаление категории',
                    'form' => $rendererForm,
                ]
            )
        );
    }


    /**
     * @throws DependencyException
     * @throws ExceptionRule
     * @throws LoaderError
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        path: '/set-extra-fields-to-children',
        name: 'set-extra-fields-to-children',
        comment: '[ADMIN] Установка extra fields всем дочерним категориям'
    )]
    public function setExtraFieldsToAllChildren(SetExtraFieldsToChildren $setExtraFieldsToChildren): ResponseInterface
    {
        $form = $setExtraFieldsToChildren->getForm();
        if ($form->isSubmitted()) {
            $setExtraFieldsToChildren->doActionRecursive($setExtraFieldsToChildren->getCategory()->getChildren());
           return $this->redirect->toRoute('@catalog_category_list');
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                [
                    'title' => sprintf(
                        "Установка extra fields из %s в  дочерние категории",
                        $setExtraFieldsToChildren->getCategory()->getTitle()
                    ),
                    'subtitle' => 'Установка extra fields',
                    'form' => $rendererForm,
                ]
            )
        );
    }
}
