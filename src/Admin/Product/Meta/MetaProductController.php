<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Meta;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductMeta;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route(
    path: '/admin/catalog/product/meta',
    name: '@catalog_product_meta',
    comment: 'Управление Meta-tags (продукт)'
)]
final class MetaProductController extends AdminController
{

    private Product $product;


    /**
     * @throws DependencyException
     * @throws NoResultException
     * @throws NotFoundException
     * @throws NotSupported
     */
    public function __construct(
        Container $container,
        Config $config,
        \EnjoysCMS\Module\Admin\Config $adminConfig,
        private readonly EntityManager $em,
    ) {
        parent::__construct($container, $config, $adminConfig);

        $this->product = $this->em->getRepository(Product::class)->find(
            $this->request->getQueryParams()['id'] ?? null
        ) ?? throw new NoResultException();
    }


    /**
     * @throws LoaderError
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __invoke(): ResponseInterface
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
            return $this->redirect->toUrl();
        }


        $rendererForm = $this->adminConfig->getRendererForm($form);

        $this->breadcrumbs->add('@catalog_products', 'Список продуктов')->setLastBreadcrumb(
            sprintf('META-данные: %s', $this->product->getName())
        );

        return $this->response(
            $this->twig->render(
                $this->templatePath . '/meta.twig',
                [
                    'product' => $this->product,
                    'subtitle' => 'Установка META данных HTML',
                    'form' => $rendererForm->output(),
                ]
            )
        );
    }

    private function getForm(): Form
    {
        $form = new Form();

        $form->setDefaults(
            [
                'title' => $this->product->getMeta()?->getTitle(),
                'keywords' => $this->product->getMeta()?->getKeyword(),
                'description' => $this->product->getMeta()?->getDescription()
            ]
        );

        $form->text('title', 'Название страницы для данного продукта')
            ->setDescription('&lt;title&gt; Переопределённое название конкретно этой страницы &lt;/title&gt;');

        $form->text('keywords', 'meta-keywords');
        $form->textarea('description', 'meta-description');

        $form->submit('submit1', 'Изменить');

        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        if (null === $meta = $this->em->getRepository(ProductMeta::class)->findOneBy(['product' => $this->product])) {
            $meta = new ProductMeta();
        }
        $meta->setTitle($this->request->getParsedBody()['title'] ?? null);
        $meta->setKeyword($this->request->getParsedBody()['keywords'] ?? null);
        $meta->setDescription($this->request->getParsedBody()['description'] ?? null);
        $meta->setProduct($this->product);
        $this->em->persist($meta);
        $this->em->flush();
    }

}
