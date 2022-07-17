<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog;


use DI\FactoryInterface;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Module\Catalog\Crud\Images\ThumbnailService\ThumbnailServiceInterface;
use EnjoysCMS\Module\Catalog\StorageUpload\StorageUploadInterface;
use Psr\Container\ContainerInterface;

final class Config
{

    private ModuleConfig $config;

    public function __construct(ContainerInterface $container)
    {
        $this->config = self::getConfig($container);
    }

    public static function getConfig(ContainerInterface $container): ModuleConfig
    {
        return $container
            ->get(FactoryInterface::class)
            ->make(ModuleConfig::class, ['moduleName' => 'enjoyscms/catalog'])
        ;
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

    public static function getAdminTemplatePath(ContainerInterface $container): string
    {
        $config = self::getConfig($container)->getAll();
//        dd( $config['adminTemplateDir'] ??=  __DIR__ . '/../template/admin');
        $templatePath = isset($config['adminTemplateDir']) ? $_ENV['PROJECT_DIR'] . $config['adminTemplateDir'] : __DIR__ . '/../template/admin';
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


    public function get(string $key)
    {
        return $this->config->get($key);
    }
}
