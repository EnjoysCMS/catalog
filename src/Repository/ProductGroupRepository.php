<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Repository;

use Doctrine\ORM\EntityRepository;
use EnjoysCMS\Module\Catalog\Entity\ProductGroup;

/**
 * @method ProductGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductGroup[] findAll()
 * @method ProductGroup[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductGroupRepository extends EntityRepository
{

}
