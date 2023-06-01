<?php

namespace EnjoysCMS\Module\Catalog\ORM\Doctrine;

use Doctrine\DBAL\Exception\NoKeyValue;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

class FilterHydrate  extends AbstractHydrator
{

    public const NAME = 'FilterHydrate';

    protected function hydrateAllData(): array
    {
        throw new \InvalidArgumentException('Need release FilterHydrate');
    }
}
