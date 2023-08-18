<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Admin\Product\Form;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use League\Flysystem\FilesystemException;

final class DeleteProductForm
{
    /**
     * @throws NoResultException
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly Config $config,
    ) {
    }


    public function getForm(): Form
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
    public function doAction(Product $product): void
    {
        $this->removeImages($product);
        $this->removeUrls($product);
        $this->removePrices($product);
        $this->removeFiles($product);
        $this->em->remove($product->getQuantity());
        $this->em->remove($product);

        $this->em->flush();
    }

    /**
     * @throws ORMException
     * @throws FilesystemException
     */
    private function removeImages(Product $product): void
    {
        foreach ($product->getImages() as $image) {
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
    private function removeFiles(Product $product): void
    {
        foreach ($product->getFiles() as $file) {
            $fs = $this->config->getFileStorageUpload($file->getStorage())->getFileSystem();
            $fs->delete($file->getFilePath());
            $this->em->remove($file);
        }
    }

    /**
     * @throws ORMException
     */
    private function removePrices(Product $product): void
    {
        foreach ($product->getPrices() as $price) {
            $this->em->remove($price);
        }
    }

    /**
     * @throws ORMException
     */
    private function removeUrls(Product $product): void
    {
        foreach ($product->getUrls() as $url) {
            $this->em->remove($url);
        }
    }
}
