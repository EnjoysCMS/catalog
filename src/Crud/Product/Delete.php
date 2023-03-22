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
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{

    private Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private RedirectInterface $redirect,
        private Config $config
    ) {
        $this->product = $this->entityManager->getRepository(Product::class)->find(
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
        $this->entityManager->remove($this->product->getQuantity());
        $this->entityManager->remove($this->product);

        $this->entityManager->flush();
        $this->redirect->http($this->urlGenerator->generate('catalog/admin/products'), emit: true);
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
            $this->entityManager->remove($image);
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
            $this->entityManager->remove($file);
        }
    }

    /**
     * @throws ORMException
     */
    private function removePrices(): void
    {
        foreach ($this->product->getPrices() as $price) {
            $this->entityManager->remove($price);
        }
    }

    /**
     * @throws ORMException
     */
    private function removeUrls(): void
    {
        foreach ($this->product->getUrls() as $url) {
            $this->entityManager->remove($url);
        }
    }
}
