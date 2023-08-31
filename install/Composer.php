<?php

namespace EnjoysCMS\Module\Catalog\Install;

use App\Install\Functions\CommandsManage;
use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Composer\EventDispatcher\Event as BaseEvent;
use EnjoysCMS\Module\Catalog\Command\CurrencyRate;
use Exception;

class Composer
{

    private static array $commands = [
        CurrencyRate::class => []
    ];

    private static function getRootPath(BaseEvent $event): string
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
    public static function unregisterCommands(PackageEvent $event): void
    {
        $event->stopPropagation();
        if ($event->getOperation()->getPackage()->getName() !== "enjoyscms/catalog"){
            return;
        }
        $manage = new CommandsManage(self::getRootPath($event) . '/console.yml');
        $manage->unregisterCommands(array_keys(self::$commands));
        $manage->save();

    }
}
