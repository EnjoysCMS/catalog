<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Helpers;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Helpers\HelpersBase;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Repositories\OptionKeyRepository;

final class ProductOptions extends HelpersBase
{

    public static function getOptionKey(string $name, string $unit = null): ?OptionKey
    {
        /** @var OptionKeyRepository $repository */
        $repository = self::$container->get(EntityManager::class)->getRepository(OptionKey::class);
        return $repository->findOneBy(
            [
                'name' => $name,
                'unit' => $unit
            ]
        );
    }
}
