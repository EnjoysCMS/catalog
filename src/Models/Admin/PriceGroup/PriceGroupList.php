<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\PriceGroup;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;

final class PriceGroupList implements ModelInterface
{

    public function __construct(private EntityManager $em)
    {
    }

    public function getContext(): array
    {

        return [];
    }

}