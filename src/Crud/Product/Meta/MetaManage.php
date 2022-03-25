<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Meta;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductMeta;
use EnjoysCMS\Module\Catalog\Repositories;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


final class MetaManage implements ModelInterface
{
    private ObjectRepository|EntityRepository|Repositories\Product $productRepository;
    protected Product $product;
    private ObjectRepository|EntityRepository $metaRepository;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->metaRepository = $this->em->getRepository(ProductMeta::class);
        $this->product = $this->getProduct();
    }


    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->find($this->requestWrapper->getQueryData('id'));
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
    }


    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $renderer = new Bootstrap4();
        $renderer->setForm($form);


        return [
            'product' => $this->product,
            'subtitle' => 'Установка META данных HTML',
            'form' => $renderer->render(),
        ];
    }

    protected function getForm(): Form
    {
        $form = new Form();

        $form->setMethod('post');

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

    protected function doAction(): void
    {
        if (null === $meta = $this->metaRepository->findOneBy(['product' => $this->product])) {
            $meta = new ProductMeta();
        }
        $meta->setTitle($this->requestWrapper->getPostData('title'));
        $meta->setKeyword($this->requestWrapper->getPostData('keywords'));
        $meta->setDescription($this->requestWrapper->getPostData('description'));
        $meta->setProduct($this->product);
        $this->em->persist($meta);
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }
}
