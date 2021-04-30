<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Repositories;

use Doctrine\ORM\EntityRepository;

/**
 * Class Category
 * @package EnjoysCMS\Module\Catalog\Repositories
 */
class Category extends EntityRepository
{

    public function getRootNodes()
    {
        return $this->findBy(['parent' => null]);
    }
}