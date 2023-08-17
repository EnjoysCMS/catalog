<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Urls;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route('admin/catalog/product/urls', '@catalog_product_urls_')]
final class UrlsProductController extends AdminController
{
    private Product $product;

    public function __construct(Container $container, Config $config, \EnjoysCMS\Module\Admin\Config $adminConfig)
    {
        parent::__construct($container, $config, $adminConfig);

        $this->product = $container->get(EntityManager::class)->getRepository(Product::class)->find(
            $this->request->getQueryParams()['product_id'] ?? null
        ) ?? throw new NoResultException();

        $this->breadcrumbs->add('@catalog_products', 'Список продуктов')
            ->add(['@catalog_product_urls_list', ['product_id' => $this->product->getId()]]);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route(
        name: 'list',
        comment: 'Просмотр URLs товара'
    )]
    public function manage(): ResponseInterface
    {
        $this->breadcrumbs->setLastBreadcrumb(
            sprintf('Менеджер ссылок: %s', $this->product->getName())
        );

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/urls/manage.twig',
                [
                    'product' => $this->product,
                    'subtitle' => 'URLs',
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
     */
    #[Route(
        path: '/edit',
        name: 'edit',
        options: [
            'comment' => '[ADMIN] Редактирование URL'
        ]
    )]
    public function edit(EditUrl $editUrl): ResponseInterface
    {
        $form = $editUrl->getForm($this->product);

        if ($form->isSubmitted()) {
            $editUrl->doAction($this->product);
            return $this->redirect->toRoute(
                '@catalog_product_urls_list',
                ['product_id' => $this->product->getId()]
            );
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        $this->breadcrumbs->setLastBreadcrumb('Редактирование ссылки');

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/urls/edit.twig',
                [
                    'product' => $this->product,
                    'form' => $rendererForm->output(),
                    'subtitle' => 'Редактирование URL',
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
     */
    #[Route(
        path: '/add',
        name: 'add',
        options: [
            'comment' => '[ADMIN] Добавление URL'
        ]
    )]
    public function add(AddUrl $addUrl): ResponseInterface
    {
        $form = $addUrl->getForm();

        if ($form->isSubmitted()) {
            $addUrl->doAction();
            return $this->redirect->toRoute(
                '@catalog_product_urls_list',
                ['product_id' => $this->product->getId()]
            );
        }
        $rendererForm = $this->adminConfig->getRendererForm($form);
        $this->breadcrumbs->setLastBreadcrumb('Добавление ссылки');
        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/urls/add.twig',
                [
                    'product' => $this->product,
                    'form' => $rendererForm->output(),
                    'subtitle' => 'Добавление URL',
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
     */
    #[Route(
        path: '/delete',
        name: 'delete',
        options: [
            'comment' => '[ADMIN] Удаление URL'
        ]
    )]
    public function delete(DeleteUrl $deleteUrl): ResponseInterface
    {
        $form = $deleteUrl->getForm();

        if ($form->isSubmitted()) {
            $deleteUrl->doAction();
            return $this->redirect->toRoute(
                '@catalog_product_urls_list',
                ['product_id' => $this->product->getId()]
            );
        }
        $rendererForm = $this->adminConfig->getRendererForm($form);
        $this->breadcrumbs->setLastBreadcrumb('Удаление ссылки');
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

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route(
        path: '/makedefault',
        name: 'makedefault',
        options: [
            'comment' => '[ADMIN] Сделать URL основным'
        ]
    )]
    public function makeDefault(EntityManager $em): ResponseInterface
    {
        $setFlag = false;
        /** @var Url $url */
        foreach ($this->product->getUrls() as $url) {
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

        return $this->redirect->toRoute(
            '@catalog_product_urls_list',
            ['product_id' => $this->product->getId()]
        );
    }
}
