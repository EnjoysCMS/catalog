<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Helpers;


use EnjoysCMS\Core\Components\Helpers\HelpersBase;
use EnjoysCMS\Module\Catalog\Config;

final class Template extends HelpersBase
{

    public static function getAdminTemplatePath(): string
    {
        $config = Config::getConfig(self::$container)->getAll();
        $templatePath = isset($config['adminTemplateDir']) ? $_ENV['PROJECT_DIR'] . $config['adminTemplateDir'] : __DIR__ . '/../../template/admin';
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