<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Tags;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories;

class TagsList implements ModelInterface
{

    private ObjectRepository|EntityRepository|Repositories\Product $productRepository;
    protected Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private RendererInterface $renderer
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
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

        $this->renderer->setForm($form);

        return [
            'product' => $this->product,
            'subtitle' => 'Управление тегами',
            'form' => $this->renderer->output()
        ];
    }

    protected function getForm(): Form
    {
        $form = new Form();

        $form->setMethod('post');

        $form->setDefaults([
                               'tags' => implode(
                                   ',',
                                   array_map(function ($tag) {
                                       return \trim($tag->getName());
                                   }, $this->product->getTags()->toArray())
                               )
                           ]);

        $form->text('tags', 'Теги')->setDescription('Теги через запятую')->setAttrs(
            AttributeFactory::createFromArray(['placeholder' => ''])
        );

        $form->submit('submit1', 'Изменить');

        return $form;
    }

    protected function doAction(): void
    {
        $tags = array_map('trim', array_unique(explode(',', $this->requestWrapper->getPostData('tags'))));
        $manageTags = new TagsManager($this->em);
        $this->product->clearTags();
        $this->product->addTagsFromArray($manageTags->getTagsFromArray($tags));
        $this->em->flush();

        Redirect::http();
    }


}
