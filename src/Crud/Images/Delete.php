<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Images;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{

    private EntityManager $entityManager;
    private ServerRequestInterface $serverRequest;
    private RendererInterface $renderer;
    private UrlGeneratorInterface $urlGenerator;
    private ?Image $image;

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


        $this->image = $this->entityManager->getRepository(Image::class)->find(
            $this->serverRequest->get('id', 0)
        );



        if ($this->image === null) {
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
        $product = $this->image->getProduct();
        foreach (glob($this->image->getGlobPattern()) as $item) {
            @unlink($item);
        }
        $this->entityManager->remove($this->image);
        $this->entityManager->flush();

        if($this->image->isGeneral()){
            $nextImage = $product->getImages()->first();
            if($nextImage instanceof Image){
                $nextImage->setGeneral(true);
            }
            $this->entityManager->flush();
        }

        Redirect::http($this->urlGenerator->generate('catalog/admin/product/images', ['product_id' => $product->getId()]));
    }
}
