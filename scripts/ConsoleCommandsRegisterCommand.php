<?php

namespace EnjoysCMS\Module\Catalog\Composer\Scripts;

use EnjoysCMS\Core\Console\Command\AbstractCommandsRegister;
use EnjoysCMS\Module\Catalog\Command\CurrencyRate;

class ConsoleCommandsRegisterCommand extends AbstractCommandsRegister
{
    protected string $moduleName = 'Catalog';

    protected array $commands = [
        CurrencyRate::class => []
    ];

}
