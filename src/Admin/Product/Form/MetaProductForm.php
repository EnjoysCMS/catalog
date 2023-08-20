<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Form;


use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductMeta;
use Psr\Http\Message\ServerRequestInterface;

final class MetaProductForm
{

    /**
     * @throws DependencyException
     * @throws NoResultException
     * @throws NotFoundException
     * @throws NotSupported
     */
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly EntityManager $em,
    ) {
    }

    public function getForm(Product $product): Form
    {
        $form = new Form();

        $form->setDefaults(
            [
                'title' => $product->getMeta()?->getTitle(),
                'keywords' => $product->getMeta()?->getKeyword(),
                'description' => $product->getMeta()?->getDescription()
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
    public function doAction(Product $product): void
    {
        if (null === $meta = $this->em->getRepository(ProductMeta::class)->findOneBy(['product' => $product])) {
            $meta = new ProductMeta();
        }
        $meta->setTitle($this->request->getParsedBody()['title'] ?? null);
        $meta->setKeyword($this->request->getParsedBody()['keywords'] ?? null);
        $meta->setDescription($this->request->getParsedBody()['description'] ?? null);
        $meta->setProduct($product);
        $this->em->persist($meta);
        $this->em->flush();
    }

}
