<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Helpers;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Helpers\HelpersBase;
use EnjoysCMS\Module\Catalog\Config;

final class Setting extends HelpersBase
{
    /**
     * @param null|string $defaults
     *
     * @psalm-param ''|'|'|null $defaults
     */
    static public function get(string $key, string|null $defaults = null)
    {
        $moduleConfig = Config::getConfig(self::$container)->getAll();
        return self::$container->get(EntityManager::class)->getRepository(
                \EnjoysCMS\Module\Catalog\Entities\Setting::class
            )->findOneBy(['key' => $key])?->getValue() ?? $moduleConfig[$key] ?? $defaults;
    }

}
