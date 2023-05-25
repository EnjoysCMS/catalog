<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Upload\Rule\MediaType;
use Enjoys\Upload\Rule\Size;
use Enjoys\Upload\UploadProcessing;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Events\PostUploadFile;
use EnjoysCMS\Module\Catalog\Events\PreUploadFile;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

final class UploadHandler
{
    private Filesystem $filesystem;
    private ?ThumbnailService $thumbnailService;

    public function __construct(private Config $config, private EventDispatcherInterface $dispatcher)
    {
        $this->filesystem = $this->config->getImageStorageUpload()->getFileSystem();
        $this->thumbnailService = $this->config->getThumbnailCreationService();
    }

    /**
     * @throws OptimisticLockException
     * @throws Throwable
     * @throws \Doctrine\ORM\ORMException
     * @throws ORMException
     * @throws FilesystemException
     */
    public function uploadFile(UploadedFileInterface $uploadedFile): UploadProcessing
    {
        $file = new UploadProcessing($uploadedFile, $this->filesystem);
        try {
            $newFilename = md5((string)microtime(true));
            $subDirectory = $newFilename[0] . '/' . $newFilename[1];

            $file->setFilename($newFilename);
            $file->addRules([
                (new Size())->setMaxSize(10 * 1024 * 1024),
                (new MediaType())->allow('image/*'),
//                (new Extension())->allow('jpg, png, jpeg, svg'),
            ]);


            $this->dispatcher->dispatch(new PreUploadFile($file));
            $file->upload($subDirectory);
            $this->dispatcher->dispatch(new PostUploadFile($file));

            $this->thumbnailService?->make(
                $file->getTargetPath(),
                $this->filesystem
            );

            return $file;
        } catch (Throwable $e) {
            if (null !== $location = $file->getTargetPath()) {
                $this->filesystem->delete($location);
            }

            throw $e;
        }
    }
}
