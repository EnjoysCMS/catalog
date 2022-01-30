<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Tags;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TagsList implements ModelInterface
{

    private ObjectRepository|EntityRepository|Repositories\Product $productRepository;
    protected Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
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

//        Assets::css([__DIR__ . '/../../../../../node_modules/bootstrap-tagsinput/dist/bootstrap-tagsinput.css']);
//        Assets::js([__DIR__ . '/../../../../../node_modules/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js']);

        return [
            'product' => $this->product,
            'subtitle' => 'Управление тегами',
            'form' => $renderer->render(),
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

        $form->text('tags', 'Теги')->setDescription('Теги через запятую')->setAttributes(
            ['placeholder' => '']
        );

        $form->submit('submit1', 'Изменить');

        return $form;
    }

    protected function doAction()
    {
        $tags = array_map('trim', array_unique(explode(',', $this->serverRequest->post('tags'))));
        $manageTags = new TagsManager($this->em);
        $this->product->clearTags();
        $this->product->addTagsFromArray($manageTags->getTagsFromArray($tags));
        $this->em->flush();

        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }


}
