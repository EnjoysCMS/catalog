<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models\Admin\Product;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        RendererInterface $renderer,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->renderer = $renderer;
        $this->urlGenerator = $urlGenerator;
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        return [
            'form' => $this->renderer
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);

        $form->setDefaults(
            [
                'category' => $this->serverRequest->get('category_id')
            ]
        );


        $form->select('category', 'Категория')->fill(
            $this->entityManager->getRepository(
                Category::class
            )->getFormFillArray()
        )
        ;
        $form->text('name', 'Наименование');
        $form->text('url', 'URL');
        $form->textarea('description', 'Описание');

        $form->submit('add');
        return $form;
    }

    private function doAction()
    {
        /** @var Category|null $category */
        $category = $this->entityManager->getRepository(Category::class)->find($this->serverRequest->post('category'));
        $product = new Product();
        $product->setName($this->serverRequest->post('name'));
        $product->setDescription($this->serverRequest->post('description'));
        $product->setCategory($category);
        $product->setUrl(
            (empty($this->serverRequest->post('url'))) ? URLify::slug($product->getName()) : URLify::slug(
                $this->serverRequest->post('url')
            )
        );
        $product->setArticle(null);
        $product->setHide(false);
        $product->setActive(true);

        $this->entityManager->persist($product);
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }
}