<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models\Admin\Product;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use EnjoysCMS\WYSIWYG\Summernote\Summernote;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Edit implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;
    private Environment $twig;
    private ?Product $product;

    public function __construct(
        EntityManager $entityManager,
        ServerRequestInterface $serverRequest,
        RendererInterface $renderer,
        UrlGeneratorInterface $urlGenerator,
        Environment $twig
    ) {
        $this->entityManager = $entityManager;
        $this->serverRequest = $serverRequest;
        $this->renderer = $renderer;
        $this->urlGenerator = $urlGenerator;
        $this->twig = $twig;


        $this->product = $this->entityManager->getRepository(Product::class)->find(
            $this->serverRequest->get('id', 0)
        )
        ;
        if ($this->product === null) {
            Error::code(404);
        }
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $wysiwyg = new WYSIWYG(new Summernote());
        $wysiwyg->setTwig($this->twig);


        return [
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#description'),
        ];
    }

    private function getForm(): Form
    {
        $defaults = [
            'name' => $this->product->getName(),
            'url' => $this->product->getUrl(),
            'description' => $this->product->getDescription(),
        ];

        $category = $this->product->getCategory();
        if ($category instanceof Category) {
            $defaults['category'] = $category->getId();
        }

        $form = new Form(['method' => 'post']);

        $form->setDefaults($defaults);


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

        $this->product->setName($this->serverRequest->post('name'));
        $this->product->setDescription($this->serverRequest->post('description'));
        $this->product->setCategory($category);
        $this->product->setUrl(
            (empty($this->serverRequest->post('url'))) ? URLify::slug(
                $this->product->getName()
            ) : $this->serverRequest->post('url')
        );

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }
}