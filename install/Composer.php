<?php

namespace EnjoysCMS\Module\Catalog\Install;

use Enjoys\Config\Config;
use EnjoysCMS\Core\Console\Utils\CommandsManage;
use EnjoysCMS\Module\Catalog\Command\CurrencyRate;
use Exception;

class Composer
{

    private static array $commands = [
        CurrencyRate::class => []
    ];


    public static function assetsInstall(): void
    {
        passthru(sprintf('cd %s && yarn install', realpath(__DIR__ . '/..')));
    }

    /**
     * @throws Exception
     */
    public static function registerCommands(): void
    {
        $container = include __DIR__ . '/../../../bootstrap.php';
        $manage = new CommandsManage(config: $container->get(Config::class));
        $manage->registerCommands(self::$commands);
        $manage->save();
    }
}
