<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Helpers;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Helpers\HelpersBase;
use EnjoysCMS\Module\Catalog\Config;

final class Setting extends HelpersBase
{

    public static function get(string $key, string|null $defaults = null)
    {
        $moduleConfig = self::$container->get(Config::class)->getModuleConfig()->getAll();
        return self::$container->get(EntityManager::class)->getRepository(
                \EnjoysCMS\Module\Catalog\Entities\Setting::class
            )->findOneBy(['key' => $key])?->getValue() ?? $moduleConfig[$key] ?? $defaults;
    }

}
