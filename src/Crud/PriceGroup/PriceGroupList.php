<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\PriceGroup;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;

final class PriceGroupList implements ModelInterface
{

    public function __construct(private EntityManager $em)
    {
    }

    public function getContext(): array
    {

        return [
            'priceGroups' => $this->em->getRepository(PriceGroup::class)->findAll()
        ];
    }

}
