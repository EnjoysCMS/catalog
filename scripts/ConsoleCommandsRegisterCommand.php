<?php

namespace EnjoysCMS\Module\Catalog\Composer\Scripts;

use EnjoysCMS\Core\Composer\Scripts\RegisterCommandsToConsoleCommand;
use EnjoysCMS\Module\Catalog\Command\CurrencyRate;

class ConsoleCommandsRegisterCommand extends RegisterCommandsToConsoleCommand
{
    protected string $moduleName = 'Catalog';

    protected array $commands = [
        CurrencyRate::class => []
    ];

}
