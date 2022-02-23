<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog;


use DI\FactoryInterface;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use Psr\Container\ContainerInterface;

final class Config
{

    public static function getConfig(ContainerInterface $container): ModuleConfig
    {
        return $container
            ->get(FactoryInterface::class)
            ->make(ModuleConfig::class, ['moduleName' => 'enjoyscms/catalog'])
            ;
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
}
