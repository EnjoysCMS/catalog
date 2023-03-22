<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Meta;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductMeta;
use EnjoysCMS\Module\Catalog\Repositories;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


final class MetaManage implements ModelInterface
{
    private EntityRepository|Repositories\Product $productRepository;
    private Product $product;
    private EntityRepository $metaRepository;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RendererInterface $renderer,
        private RedirectInterface $redirect,
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->metaRepository = $this->em->getRepository(ProductMeta::class);
        $this->product = $this->productRepository->find(
            $this->request->getQueryParams()['id'] ?? null
        ) ?? throw new NoResultException();
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
            $this->em->flush();
            $this->redirect->http(emit: true);
        }


        $this->renderer->setForm($form);


        return [
            'product' => $this->product,
            'subtitle' => 'Установка META данных HTML',
            'form' => $this->renderer->output(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('META-данные: %s', $this->product->getName()),
            ],
        ];
    }

    protected function getForm(): Form
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
    protected function doAction(): void
    {
        if (null === $meta = $this->metaRepository->findOneBy(['product' => $this->product])) {
            $meta = new ProductMeta();
        }
        $meta->setTitle($this->request->getParsedBody()['title'] ?? null);
        $meta->setKeyword($this->request->getParsedBody()['keywords'] ?? null);
        $meta->setDescription($this->request->getParsedBody()['description'] ?? null);
        $meta->setProduct($this->product);
        $this->em->persist($meta);
    }
}
