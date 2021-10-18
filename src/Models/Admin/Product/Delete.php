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

final class Delete implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;
    private ?Product $product;

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




        return [
            'product' => $this->product,
            'form' => $this->renderer,
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);

        $form->header('Подтвердите удаление!');
        $form->submit('delete');
        return $form;
    }

    private function doAction()
    {
        $this->removeImages();
        $this->removeUrls();

        $this->entityManager->remove($this->product);

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }

    private function removeImages(): void
    {
        foreach ($this->product->getImages() as $image) {
            foreach (glob($image->getGlobPattern()) as $item) {
                @unlink($item);
            }
            $this->entityManager->remove($image);
        }
    }

    private function removeUrls()
    {
        foreach ($this->product->getUrls() as $url) {
            $this->entityManager->remove($url);
        }
    }
}