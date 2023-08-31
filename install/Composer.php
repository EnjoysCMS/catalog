<?php

namespace EnjoysCMS\Module\Catalog\Install;

use App\Install\Functions\CommandsManage;
use Composer\EventDispatcher\Event as BaseEvent;
use Composer\Installer\PackageEvent;
use Composer\Script\Event;
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
    public static function registerCommands(Event $event): void
    {
        $manage = new CommandsManage(
            dirname($event->getComposer()->getConfig()->getConfigSource()->getName()) . '/console.yml'
        );
        $manage->registerCommands(self::$commands);
        $manage->save();
    }
}
