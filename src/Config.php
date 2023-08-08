<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Enjoys\Session\Session;
use EnjoysCMS\Core\Modules\ModuleCollection;
use EnjoysCMS\Core\StorageUpload\StorageUploadInterface;
use EnjoysCMS\Module\Catalog\Crud\Images\ThumbnailService;
use EnjoysCMS\Module\Catalog\Crud\Images\ThumbnailService\ThumbnailServiceInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use Exception;
use Gedmo\Tree\TreeListener;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class Config
{

    private const MODULE_NAME = 'enjoyscms/catalog';


    /**
     * @throws Exception
     */
    public function __construct(
        private \Enjoys\Config\Config $config,
        private Container $container,
        private Session $session,
        private EntityManager $em,
        private LoggerInterface $logger,
        ModuleCollection $moduleCollection
    ) {
        $module = $moduleCollection->find(self::MODULE_NAME) ?? throw new InvalidArgumentException(
            sprintf(
                'Module %s not found. Name must be same like packageName in module composer.json',
                self::MODULE_NAME
            )
        );


        if (file_exists($module->path . '/config.yml')) {
            $config->addConfig(
                [
                    self::MODULE_NAME => file_get_contents($module->path . '/config.yml')
                ],
                ['flags' => Yaml::PARSE_CONSTANT],
                \Enjoys\Config\Config::YAML,
                false
            );
        }
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config->get(self::MODULE_NAME);
        }
        return $this->config->get(sprintf('%s->%s', self::MODULE_NAME, $key), $default);
    }


    public function all(): array
    {
        return $this->config->get();
    }


    public function getCurrentCurrencyCode(): string
    {
        return $this->session->get('catalog')['currency'] ?? $this->get(
            'currency->default'
        ) ?? throw new InvalidArgumentException(
            'Default currency value not valid'
        );
    }

    /**
     * @throws NotSupported
     */
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
        return $this->session->get('catalog')['sort'] ?? $this->get(
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
        return (string)($this->session->get('catalog')['limitItems'] ?? $this->get(
            'limitItems'
        ) ?? throw new InvalidArgumentException('limitItems not set'));
    }

    public function setPerPage(string $perpage = null): void
    {
        $allowedPerPage = $this->get(
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
        $storageName = $storageName ?? $this->get('productImageStorage');

        $storageUploadConfig = $this->get(sprintf('storageUploads->%s', $storageName)) ?? throw new RuntimeException(
            sprintf('Not set config `storageUploads->%s`', $storageName)
        );
        /** @var class-string $storageUploadClass */
        $storageUploadClass = key($storageUploadConfig);
        return new $storageUploadClass(...current($storageUploadConfig));
    }

    public function getFileStorageUpload($storageName = null): StorageUploadInterface
    {
        $storageName = $storageName ?? $this->get('productFileStorage');

        $storageUploadConfig = $this->get(sprintf('storageUploads->%s', $storageName)) ?? throw new RuntimeException(
            sprintf('Not set config `storageUploads->%s`', $storageName)
        );
        /** @var class-string $storageUploadClass */
        $storageUploadClass = key($storageUploadConfig);
        return new $storageUploadClass(...current($storageUploadConfig));
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @deprecated
     */
    public function getThumbnailService(): ThumbnailServiceInterface
    {
        return $this->container->get($this->get('thumbnailService'));
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getThumbnailCreationService(): ?ThumbnailService
    {
        $classString = $this->get(
            'thumbnailCreationService',
            ThumbnailService\DefaultThumbnailCreationService::class
        );

        try {
            if ($classString === null) {
                return null;
            }
            return $this->container->get($classString);
        } catch (DependencyException|NotFoundException) {
            $this->logger->warning(sprintf('ThumbnailService %s not found. Use %s', $classString, ThumbnailService\DefaultThumbnailCreationService::class));
            return $this->container->get(ThumbnailService\DefaultThumbnailCreationService::class);
        }
    }

    public function getAdminTemplatePath(): string
    {
        try {
            $templatePath = getenv('ROOT_PATH') . $this->get(
                    'adminTemplateDir',
                    throw new InvalidArgumentException()
                );
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
        return $this->get('editor->productDescription');
    }

    public function getEditorConfigCategoryDescription()
    {
        return $this->get('editor->categoryDescription');
    }

    public function getEditorConfigCategoryShortDescription()
    {
        return $this->get('editor->categoryShortDescription');
    }

    public function getDefaultPriceGroup()
    {
        return $this->get('priceGroup->default', 'ROZ');
    }

}
