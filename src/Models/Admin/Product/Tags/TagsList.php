<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models\Admin\Product\Tags;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductTag;
use EnjoysCMS\Module\Catalog\Repositories;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TagsList implements ModelInterface
{

    private ObjectRepository|EntityRepository|Repositories\Product $productRepository;
    private Product $product;
    private ObjectRepository|EntityRepository $tagRepository;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->tagRepository = $this->em->getRepository(ProductTag::class);
        $this->product = $this->getProduct();
    }


    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->find($this->serverRequest->get('id'));
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
            'form' => $renderer->render(),
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();

        $form->setMethod('post');

        $form->setDefaults([
                               'tags' => implode(
                                   ', ',
                                   array_map(function ($tag) {
                                       return $tag->getName();
                                   }, $this->product->getTags()->toArray())
                               )
                           ]);

        $form->text('tags', 'Теги')->setDescription('Теги через запятую')->setAttributes(
            ['tabindex' => 105, 'data-role' => 'tagsinput', 'placeholder' => '']
        );

        $form->submit('submit1', 'Изменить');

        return $form;
    }

    private function doAction()
    {
        $tags = array_map('trim', explode(',', $this->serverRequest->post('tags')));

        $this->product->clearTags();

        foreach ($tags as $tag) {
            if(empty($tag)){
                continue;
            }
            $tagEntity = $this->tagRepository->findOneBy(['name' => $tag]);

            if ($tagEntity === null) {
                $tagEntity = new ProductTag();
                $tagEntity->setName($tag);
                $this->em->persist($tagEntity);
            }

            $this->product->setTag($tagEntity);
        }

        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }
}