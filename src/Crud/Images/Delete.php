<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Images;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Image;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{

    private Image $image;

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
        $this->image = $this->entityManager->getRepository(Image::class)->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();
    }

    /**
     * @throws OptimisticLockException
     * @throws FilesystemException
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }


        return [
            'form' => $this->renderer,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                'Удаление изображения',
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
        $filesystem = $this->config->getImageStorageUpload($this->image->getStorage())->getFileSystem();


        $product = $this->image->getProduct();

        $filesystem->delete($this->image->getFilename() . '.' . $this->image->getExtension());
        $filesystem->delete($this->image->getFilename() . '_small.' . $this->image->getExtension());
        $filesystem->delete($this->image->getFilename() . '_large.' . $this->image->getExtension());

        $this->entityManager->remove($this->image);
        $this->entityManager->flush();

        if ($this->image->isGeneral()) {
            $nextImage = $product->getImages()->first();
            if ($nextImage instanceof Image) {
                $nextImage->setGeneral(true);
            }
            $this->entityManager->flush();
        }

        $this->redirect->http(
            $this->urlGenerator->generate('catalog/admin/product/images', ['product_id' => $product->getId()]),
            emit: true
        );
    }
}
