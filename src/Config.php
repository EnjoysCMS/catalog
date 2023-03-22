<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog;


use DI\DependencyException;
use DI\FactoryInterface;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Enjoys\Session\Session;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\StorageUpload\StorageUploadInterface;
use EnjoysCMS\Module\Catalog\Crud\Images\ThumbnailService\ThumbnailServiceInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use InvalidArgumentException;
use RuntimeException;

final class Config
{

    private ModuleConfig $config;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(FactoryInterface $factory, private Session $session, private EntityManager $em)
    {
        $this->config = $factory->make(ModuleConfig::class, ['moduleName' => 'enjoyscms/catalog']);
    }


    public function all(): ModuleConfig
    {
        return $this->config;
    }

    public function get(string $key, $default = null): mixed
    {
        return $this->config->get($key, $default);
    }

    public function getCurrentCurrencyCode(): string
    {
        return $this->session->get('catalog')['currency'] ?? $this->config->get(
            'currency'
        )['default'] ?? throw new InvalidArgumentException(
            'Default currency value not valid'
        );
    }

    public function getCurrentCurrency(): Currency
    {
        return $this->em->getRepository(Currency::class)->find(
            $this->getCurrentCurrencyCode()
        ) ?? throw new InvalidArgumentException(
            'Default currency value not valid'
        );
    }

    public function setCurrencyCode(?string $code): void
    {
        $this->session->set([
            'catalog' => array_merge(
                $this->session->get('catalog', []),
                ['currency' => $code],
            )
        ]);
    }

    public function getSortMode(): ?string
    {
        return $this->session->get('catalog')['sort'] ?? $this->config->get(
            'sort'
        );
    }

    public function setSortMode(string $mode = null): void
    {
        $this->session->set([
            'catalog' => array_merge(
                $this->session->get('catalog', []),
                ['sort' => $mode],
            )
        ]);
    }


    public function getPerPage(): string
    {
        return (string)($this->session->get('catalog')['limitItems'] ?? $this->config->get(
            'limitItems'
        ) ?? throw new InvalidArgumentException('limitItems not set'));
    }

    public function setPerPage(string $perpage = null): void
    {
        $allowedPerPage = $this->config->get(
            'allowedPerPage'
        ) ?? throw new InvalidArgumentException('allowedPerPage not set');

        if (!in_array((int)$perpage, $allowedPerPage, true)) {
            return;
        }

        $this->session->set([
            'catalog' => array_merge(
                $this->session->get('catalog', []),
                ['limitItems' => $perpage],
            )
        ]);
    }

    public function getImageStorageUpload($storageName = null): StorageUploadInterface
    {
        $storageName = $storageName ?? $this->config->get('productImageStorage');

        $storageUploadConfig = $this->config->get('storageUploads')[$storageName] ?? throw new RuntimeException(
            sprintf('Not set config `storageUploads.%s`', $storageName)
        );
        /** @var class-string $storageUploadClass */
        $storageUploadClass = key($storageUploadConfig);
        return new $storageUploadClass(...current($storageUploadConfig));
    }

    public function getFileStorageUpload($storageName = null): StorageUploadInterface
    {
        $storageName = $storageName ?? $this->config->get('productFileStorage');

        $storageUploadConfig = $this->config->get('storageUploads')[$storageName] ?? throw new RuntimeException(
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
            $templatePath = getenv('ROOT_PATH') . $this->config->get('adminTemplateDir');
        } catch (InvalidArgumentException) {
            $templatePath = __DIR__ . '/../template/admin';
        }

        $realpath = realpath($templatePath);

        if ($realpath === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'Template admin path is invalid: %s. Check parameter `adminTemplateDir` in config',
                    $templatePath
                )
            );
        }
        return $realpath;
    }

    public function getEditorConfigProductDescription()
    {
        return $this->config->get('editor')['productDescription'] ?? null;
    }

    public function getEditorConfigCategoryDescription()
    {
        return $this->config->get('editor')['categoryDescription'] ?? null;
    }

    public function getEditorConfigCategoryShortDescription()
    {
        return $this->config->get('editor')['categoryShortDescription'] ?? null;
    }

}
