<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="\EnjoysCMS\Module\Catalog\Repositories\OptionKeyRepository")
 * @ORM\Table(name="catalog_product_option_keys")
 */
class OptionKey
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=50)
     */
    private string $name;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $unit = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $note = null;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $weight = 0;




    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
