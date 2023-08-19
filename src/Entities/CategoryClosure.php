<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_categories_closure')]
#[ORM\UniqueConstraint(name: 'closure_unique_idx', columns: ['ancestor', 'descendant'])]
#[ORM\Index(name: 'closure_depth_idx', columns: ['depth'])]
class CategoryClosure extends AbstractClosure
{

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'descendant', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $descendant;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'ancestor', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $ancestor;

}
