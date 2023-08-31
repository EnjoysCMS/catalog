<?php

namespace EnjoysCMS\Module\Catalog\Install;

use App\Install\Functions\CommandsManage;
use Composer\Script\Event;
use EnjoysCMS\Module\Catalog\Command\CurrencyRate;
use Exception;

class Composer
{

    private static array $commands = [
        CurrencyRate::class => []
    ];

    private static function getRootPath(Event $event): string
    {
        return dirname($event->getComposer()->getConfig()->getConfigSource()->getName());
    }

    public static function assetsInstall(): void
    {
        passthru(sprintf('cd %s && yarn install', realpath(__DIR__ . '/..')));
    }

    /**
     * @throws Exception
     */
    public static function registerCommands(Event $event): void
    {
        $manage = new CommandsManage(self::getRootPath($event) . '/console.yml');
        $manage->registerCommands(self::$commands);
        $manage->save();
    }

    /**
     * @throws Exception
     */
    public static function unregisterCommands(Event $event): void
    {
        $manage = new CommandsManage(self::getRootPath($event) . '/console.yml');
        $manage->unregisterCommands(array_keys(self::$commands));
        $manage->save();
    }
}
