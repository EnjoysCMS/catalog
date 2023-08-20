<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product;

use DeepCopy\DeepCopy;
use DeepCopy\Filter\Doctrine\DoctrineCollectionFilter;
use DeepCopy\Matcher\PropertyTypeMatcher;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\QueryException;
use Enjoys\Cookie\Exception;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\ContentEditor\ContentEditor;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Admin\Product\Form\CreateUpdateProductForm;
use EnjoysCMS\Module\Catalog\Admin\Product\Form\CreateUpdateUrlProductForm;
use EnjoysCMS\Module\Catalog\Admin\Product\Form\DeleteProductForm;
use EnjoysCMS\Module\Catalog\Admin\Product\Form\DeleteUrlProductForm;
use EnjoysCMS\Module\Catalog\Admin\Product\Form\MetaProductForm;
use EnjoysCMS\Module\Catalog\Admin\Product\Form\QuantityProductForm;
use EnjoysCMS\Module\Catalog\Admin\Product\Form\TagsProductForm;
use EnjoysCMS\Module\Catalog\Admin\Product\Urls\DeleteUrl;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Events\PostAddProductEvent;
use EnjoysCMS\Module\Catalog\Events\PostDeleteProductEvent;
use EnjoysCMS\Module\Catalog\Events\PostEditProductEvent;
use EnjoysCMS\Module\Catalog\Events\PreAddProductEvent;
use EnjoysCMS\Module\Catalog\Events\PreDeleteProductEvent;
use EnjoysCMS\Module\Catalog\Events\PreEditProductEvent;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('admin/catalog/product', '@catalog_product_')]
final class ProductController extends AdminController
{
    private ?Product $product;

    public function __construct(Container $container, Config $config, \EnjoysCMS\Module\Admin\Config $adminConfig)
    {
        parent::__construct($container, $config, $adminConfig);

        $this->product = $container->get(EntityManager::class)->getRepository(Product::class)->find(
            $this->request->getAttribute('product_id') ?? $this->request->getQueryParams()['product_id'] ?? 0
        );

        $this->breadcrumbs->add('@catalog_product_list', 'Список товаров (продуктов)');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route(
        path: 's',
        name: 'list',
        comment: 'Просмотр товаров'
    )]
    public function list(): ResponseInterface
    {
        $this->breadcrumbs->remove('@catalog_product_list')->setLastBreadcrumb('Список товаров (продуктов)');
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/products.twig'
            )
        );
    }

    /**
     * @throws ExceptionRule
     * @throws ORMException
     * @throws RuntimeError
     * @throws Exception
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
        path: '/add',
        name: 'add',
        comment: 'Добавление товара'
    )]
    public function add(CreateUpdateProductForm $add, ContentEditor $contentEditor): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb('Добавление товара');

        $form = $add->getForm();

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new PreAddProductEvent());
            $product = $add->doAction();
            $this->dispatcher->dispatch(new PostAddProductEvent($product));
            return $this->redirect->toRoute('@catalog_product_list');
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/addproduct.twig',
                [
                    'form' => $rendererForm,
                    'editorEmbedCode' => $contentEditor
                        ->withConfig($this->config->getEditorConfigProductDescription())
                        ->setSelector('#description')
                        ->getEmbedCode()
                ]
            )
        );
    }


    #[Route(
        path: '/{product_id}/edit',
        name: 'edit',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Редактирование товара'
    )]
    public function edit(CreateUpdateProductForm $edit, ContentEditor $contentEditor): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();
        $this->breadcrumbs->setLastBreadcrumb(
            sprintf('Редактирование общей информации `%s`', $product->getName()),
        );

        $form = $edit->getForm($product);


        if ($form->isSubmitted()) {
            $copier = new DeepCopy();
            $copier->addFilter(
                new DoctrineCollectionFilter(),
                new PropertyTypeMatcher('Doctrine\Common\Collections\Collection')
            );
            /** @var Product $oldProduct */
            $oldProduct = $copier->copy($product);

            $this->dispatcher->dispatch(
                new PreEditProductEvent($oldProduct)
            );

            $edit->doAction($product);

            $this->dispatcher->dispatch(new PostEditProductEvent($oldProduct, $product));

            $this->redirect->toRoute('@catalog_product_list', emit: true);
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);
        $rendererForm->setOptions([
            'custom-switch' => true
        ]);

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/editproduct.twig',
                [
                    'form' => $rendererForm,
                    'product' => $product,
                    'subtitle' => 'Редактирование',
                    'editorEmbedCode' => $contentEditor
                        ->withConfig($this->config->getEditorConfigProductDescription())
                        ->setSelector('#description')
                        ->getEmbedCode(),
                ]
            )
        );
    }

    #[Route(
        path: '/{product_id}/delete',
        name: 'delete',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Удаление товара'
    )]
    public function delete(DeleteProductForm $deleteProductForm): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();
        $this->breadcrumbs->setLastBreadcrumb(sprintf('Удаление товара: `%s`', $product->getName()));
        $form = $deleteProductForm->getForm();

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new PreDeleteProductEvent($this->product));
            $deleteProductForm->doAction($product);
            $this->dispatcher->dispatch(new PostDeleteProductEvent($this->product));
            return $this->redirect->toRoute('@catalog_product_list');
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                [
                    'product' => $product,
                    'form' => $rendererForm,
                ]
            )
        );
    }

    #[Route(
        path: '/{product_id}/tags',
        name: 'tags',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Теги товара'
    )]
    public function manageTags(TagsProductForm $tagsProductForm): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();
        $form = $tagsProductForm->getForm($product);

        $this->breadcrumbs->setLastBreadcrumb(sprintf('Менеджер тегов: %s', $product->getName()));

        if ($form->isSubmitted()) {
            $tagsProductForm->doAction($product);
            return $this->redirect->toUrl();
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/tags.twig',
                [
                    'product' => $product,
                    'subtitle' => 'Управление тегами',
                    'form' => $rendererForm->output(),
                ]
            )
        );
    }

    #[Route(
        path: '/{product_id}/quantity',
        name: 'quantity',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Установка количества на товар'
    )]
    public function manage(QuantityProductForm $quantityProductForm): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();
        $form = $quantityProductForm->getForm($product);
        $this->breadcrumbs->setLastBreadcrumb(
            sprintf('Настройка количества: `%s`', $product->getName())
        );

        if ($form->isSubmitted()) {
            $quantityProductForm->doAction($product);

            return$this->redirect->toUrl();
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);



        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                [
                    'product' => $product,
                    'form' => $rendererForm,
                    'subtitle' => 'Установка количества',
                ]
            )
        );
    }

    /**
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws DependencyException
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotFoundException
     * @throws NoResultException
     */
    #[Route(
        path: '/{product_id}/meta',
        name: 'meta',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Управление Meta-tags (продукт)'
    )]
    public function manageMeta(MetaProductForm $metaProductForm): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();
        $form = $metaProductForm->getForm($product);

        if ($form->isSubmitted()) {
            $metaProductForm->doAction($product);
            return $this->redirect->toUrl();
        }


        $rendererForm = $this->adminConfig->getRendererForm($form);

        $this->breadcrumbs
            ->add(['@catalog_product_edit', ['product_id' => $product->getId()]], $product->getName())
            ->setLastBreadcrumb(
                sprintf('META-данные: %s', $product->getName())
            );

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                [
                    'product' => $product,
                    'subtitle' => 'Установка META данных HTML',
                    'form' => $rendererForm,
                ]
            )
        );
    }

    #[Route(
        path: '/{product_id}/urls',
        name: 'urls',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Просмотр URLs товара'
    )]
    public function manageUrls(): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();

        $this->breadcrumbs
            ->add(['@catalog_product_edit', ['product_id' => $product->getId()]], $product->getName())
            ->setLastBreadcrumb(
                sprintf('Менеджер ссылок: %s', $this->product->getName())
            );


        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/urls/manage.twig',
                [
                    'product' => $product,
                    'subtitle' => 'URLs',
                ]
            )
        );
    }

    #[Route(
        path: '/{product_id}/urls/edit',
        name: 'urls_edit',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Редактирование URL'
    )]
    public function editUrls(CreateUpdateUrlProductForm $editUrl): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();
        $routeData = ['@catalog_product_urls', ['product_id' => $this->product->getId()]];

        $form = $editUrl->getForm($product);

        if ($form->isSubmitted()) {
            $editUrl->doAction($product);
            return $this->redirect->toRoute(...$routeData);
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        $this->breadcrumbs->add($routeData, 'Менеджер ссылок')
            ->setLastBreadcrumb('Редактирование ссылки');

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                [
                    'product' => $this->product,
                    'form' => $rendererForm,
                    'subtitle' => 'Редактирование URL',
                ]
            )
        );
    }

    #[Route(
        path: '/{product_id}/urls/add',
        name: 'urls_add',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Добавление URL'
    )]
    public function addUrl(CreateUpdateUrlProductForm $addUrl): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();
        $routeData = ['@catalog_product_urls', ['product_id' => $this->product->getId()]];
        $form = $addUrl->getForm($product);

        if ($form->isSubmitted()) {
            $addUrl->doAction($product);
            return $this->redirect->toRoute(...$routeData);
        }
        $rendererForm = $this->adminConfig->getRendererForm($form);
        $this->breadcrumbs->add($routeData, 'Менеджер ссылок')
            ->setLastBreadcrumb('Добавление ссылки');
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/form.twig',
                [
                    'product' => $this->product,
                    'form' => $rendererForm,
                    'subtitle' => 'Добавление URL',
                ]
            )
        );
    }

    #[Route(
        path: '/{product_id}/urls/delete',
        name: 'urls_delete',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Удаление URL'
    )]
    public function deleteUrl(DeleteUrlProductForm $deleteUrl): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();

        $routeData = ['@catalog_product_urls', ['product_id' => $this->product->getId()]];

        $form = $deleteUrl->getForm($product);

        if ($form->isSubmitted()) {
            $deleteUrl->doAction($product);
            return $this->redirect->toRoute(...$routeData);
        }
        $rendererForm = $this->adminConfig->getRendererForm($form);
        $this->breadcrumbs->add($routeData, 'Менеджер ссылок')
            ->setLastBreadcrumb('Удаление ссылки');
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/urls/delete.twig',
                [
                    'product' => $this->product,
                    'form' => $rendererForm->output(),
                    'subtitle' => 'Удаление URL',
                ]
            )
        );
    }

    #[Route(
        path: '/{product_id}/urls/makedefault',
        name: 'urls_make_default',
        requirements: [
            'product_id' => '\d'
        ],
        comment: 'Сделать URL основным'
    )]
    public function makeDefault(EntityManager $em): ResponseInterface
    {
        $product = $this->product ?? throw new NoResultException();

        $routeData = ['@catalog_product_urls', ['product_id' => $this->product->getId()]];


        $setFlag = false;
        /** @var Url $url */
        foreach ($product->getUrls() as $url) {
            if ($url->getId() === (int)($this->request->getQueryParams()['url_id'] ?? null)) {
                $url->setDefault(true);
                $setFlag = true;
                continue;
            }
            $url->setDefault(false);
        }

        if ($setFlag === false) {
            throw new InvalidArgumentException('Url id is invalid');
        }

        $em->flush();

        return $this->redirect->toRoute(...$routeData);
    }
}
