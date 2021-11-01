<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Helpers;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Helpers\HelpersBase;
use EnjoysCMS\Module\Catalog\Config;

final class Setting extends HelpersBase
{
    static public function get(string $key, $defaults = null)
    {
        $moduleConfig = Config::getConfig(self::$container)->getAll();
        return self::$container->get(EntityManager::class)->getRepository(
                \EnjoysCMS\Module\Catalog\Entities\Setting::class
            )->findOneBy(['key' => $key])?->getValue() ?? $moduleConfig[$key] ?? $defaults;
    }
}