<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;


use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="categories_closure")
 */
class CategoryClosure extends AbstractClosure
{

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="`descendant`", referencedColumnName="`id`", nullable=false, onDelete="CASCADE")
     */
    protected $descendant;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="`ancestor`", referencedColumnName="`id`", nullable=false, onDelete="CASCADE")
     */
    protected $ancestor;

}