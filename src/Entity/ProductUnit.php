<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Repository\Unit;


#[ORM\Entity(repositoryClass: Unit::class)]
#[ORM\Table(name: 'catalog_units')]
class ProductUnit
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;


    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $name;



    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
