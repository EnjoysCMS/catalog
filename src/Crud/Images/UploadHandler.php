<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Upload\Rule\MediaType;
use Enjoys\Upload\Rule\Size;
use Enjoys\Upload\UploadProcessing;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Crud\Images\ThumbnailService\ThumbnailServiceInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

final class UploadHandler
{
    private Filesystem $filesystem;
    private ThumbnailServiceInterface $thumbnailService;

    public function __construct(private Config $config)
    {
        $this->filesystem = $this->config->getImageStorageUpload()->getFileSystem();
        $this->thumbnailService = $this->config->getThumbnailService();
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
            $file->upload($subDirectory);

            $this->checkMemory($fileContent = $this->filesystem->read($file->getTargetPath()));

            $this->thumbnailService->make(
                $this->filesystem,
                $file->getTargetPath(),
                $fileContent
            );

            return $file;
        } catch (Throwable $e) {
            if (null !== $location = $file->getTargetPath()) {
                $this->filesystem->delete($location);
            }

            throw $e;
        }
    }


    private function checkMemory(string $fileContent): void
    {
        $memoryLimit = (int)ini_get('memory_limit') * pow(1024, 2);
        $imageInfo = getimagesizefromstring($fileContent);
        $memoryNeeded = round(
            ($imageInfo[0] * $imageInfo[1] * ($imageInfo['bits'] ?? 1) * ($imageInfo['channels'] ?? 1) / 8 + Pow(
                    2,
                    16
                )) * 1.65
        );
        if (function_exists('memory_get_usage') && memory_get_usage() + $memoryNeeded > $memoryLimit) {
            if (!$this->config->get('allocatedMemoryDynamically')) {
                throw new RuntimeException(
                    sprintf(
                        'The allocated memory (%s MiB) is not enough for image processing. Needed: %s MiB',
                        $memoryLimit / pow(1024, 2),
                        ceil((memory_get_usage() + $memoryNeeded) / pow(1024, 2))
                    )
                );
            }

            ini_set(
                'memory_limit',
                (integer)ini_get('memory_limit') + ceil(
                    (memory_get_usage() + $memoryNeeded) / pow(1024, 2)
                ) . 'M'
            );
        }
    }
}
