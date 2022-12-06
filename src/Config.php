<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog;


use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\StorageUpload\StorageUploadInterface;
use EnjoysCMS\Module\Catalog\Crud\Images\ThumbnailService\ThumbnailServiceInterface;

final class Config
{

    private ModuleConfig $config;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->config = $factory->make(ModuleConfig::class, ['moduleName' => 'enjoyscms/catalog']);
    }


    public function getModuleConfig(): ModuleConfig
    {
        return $this->config;
    }

    public function getImageStorageUpload($storageName = null): StorageUploadInterface
    {
        $storageName = $storageName ?? $this->config->get('productImageStorage');

        $storageUploadConfig = $this->config->get('storageUploads')[$storageName] ?? throw new \RuntimeException(
                sprintf('Not set config `storageUploads.%s`', $storageName)
            );
        /** @var class-string $storageUploadClass */
        $storageUploadClass = key($storageUploadConfig);
        return new $storageUploadClass(...current($storageUploadConfig));
    }

    public function getFileStorageUpload($storageName = null): StorageUploadInterface
    {
        $storageName = $storageName ?? $this->config->get('productFileStorage');

        $storageUploadConfig = $this->config->get('storageUploads')[$storageName] ?? throw new \RuntimeException(
                sprintf('Not set config `storageUploads.%s`', $storageName)
            );
        /** @var class-string $storageUploadClass */
        $storageUploadClass = key($storageUploadConfig);
        return new $storageUploadClass(...current($storageUploadConfig));
    }

    public function getThumbnailService(): ThumbnailServiceInterface
    {
        $thumbnailServiceConfig = $this->config->get('thumbnailService');
        /** @var class-string $thumbnailServiceClass */
        $thumbnailServiceClass = key($thumbnailServiceConfig);
        return new $thumbnailServiceClass(...current($thumbnailServiceConfig));
    }

    public function getAdminTemplatePath(): string
    {
        try {
            $templatePath = getenv('ROOT_PATH'). $this->config->get('adminTemplateDir');
        } catch (\InvalidArgumentException) {
            $templatePath = __DIR__ . '/../template/admin';
        }

        $realpath = realpath($templatePath);

        if ($realpath === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Template admin path is invalid: %s. Check parameter `adminTemplateDir` in config',
                    $templatePath
                )
            );
        }
        return $realpath;
    }


    /**
     * @deprecated
     */
    public function get(string $key): mixed
    {
        return $this->config->get($key);
    }

}
