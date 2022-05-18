<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product;

use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{

    private ?Product $product;

    /**
     * @throws NotFoundException
     */
    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->product = $this->entityManager->getRepository(Product::class)->find(
            $this->requestWrapper->getQueryData('id', 0)
        );
        if ($this->product === null) {
            throw new NotFoundException(
                sprintf('Not found by id: %s', $this->requestWrapper->getQueryData('id'))
            );
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
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Удаление товара: `%s`', $this->product->getName()),
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();

        $form->header('Подтвердите удаление!');
        $form->submit('delete');
        return $form;
    }

    private function doAction(): void
    {
        $this->removeImages();
        $this->removeUrls();
        $this->removePrices();
        $this->removeFiles();
        $this->entityManager->remove($this->product->getQuantity());
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

    private function removeFiles(): void
    {
        foreach ($this->product->getFiles() as $file) {
//            foreach (glob($image->getGlobPattern()) as $item) {
//                @unlink($item);
//            }
            $this->entityManager->remove($file);
        }
    }

    private function removePrices(): void
    {
        foreach ($this->product->getPrices() as $price) {
            $this->entityManager->remove($price);
        }
    }

    private function removeUrls(): void
    {
        foreach ($this->product->getUrls() as $url) {
            $this->entityManager->remove($url);
        }
    }
}
