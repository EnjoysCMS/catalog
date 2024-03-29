<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Events\PostDeleteProductEvent;
use EnjoysCMS\Module\Catalog\Events\PreDeleteProductEvent;
use League\Flysystem\FilesystemException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{

    private Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private RedirectInterface $redirect,
        private Config $config,
        private EventDispatcherInterface $dispatcher,
    ) {
        $this->product = $this->em->getRepository(Product::class)->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws FilesystemException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->dispatcher->dispatch(new PreDeleteProductEvent($this->product));
            $this->doAction();
            $this->dispatcher->dispatch(new PostDeleteProductEvent($this->product));
            $this->redirect->toRoute('catalog/admin/products', emit: true);

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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws FilesystemException
     */
    private function doAction(): void
    {
        $this->removeImages();
        $this->removeUrls();
        $this->removePrices();
        $this->removeFiles();
        $this->em->remove($this->product->getQuantity());
        $this->em->remove($this->product);

        $this->em->flush();
    }

    /**
     * @throws ORMException
     * @throws FilesystemException
     */
    private function removeImages(): void
    {
        foreach ($this->product->getImages() as $image) {
            $fs = $this->config->getImageStorageUpload($image->getStorage())->getFileSystem();
            $fs->delete($image->getFilename().'.'.$image->getExtension());
            $fs->delete($image->getFilename().'_large.'.$image->getExtension());
            $fs->delete($image->getFilename().'_small.'.$image->getExtension());
            $this->em->remove($image);
        }
    }

    /**
     * @throws ORMException
     * @throws FilesystemException
     */
    private function removeFiles(): void
    {
        foreach ($this->product->getFiles() as $file) {
            $fs = $this->config->getFileStorageUpload($file->getStorage())->getFileSystem();
            $fs->delete($file->getFilePath());
            $this->em->remove($file);
        }
    }

    /**
     * @throws ORMException
     */
    private function removePrices(): void
    {
        foreach ($this->product->getPrices() as $price) {
            $this->em->remove($price);
        }
    }

    /**
     * @throws ORMException
     */
    private function removeUrls(): void
    {
        foreach ($this->product->getUrls() as $url) {
            $this->em->remove($url);
        }
    }
}
