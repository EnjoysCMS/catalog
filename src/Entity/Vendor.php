<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Module\Catalog\Repository\VendorRepository;

#[ORM\Entity(repositoryClass: VendorRepository::class)]
#[ORM\Table(name: 'catalog_product_vendors')]
class Vendor implements \Stringable
{

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $sortWeight = 0;

    public function __construct(
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'NONE')]
        #[ORM\Column(type: 'uuid')]
        private string $id,

        #[ORM\Column(type: 'string')]
        private string $name,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getSortWeight(): int
    {
        return $this->sortWeight;
    }

    public function setSortWeight(int $sortWeight): void
    {
        $this->sortWeight = $sortWeight;
    }
}
