<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Entities\Currency;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="catalog_currency")
 */
final class Currency
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="string", length=3)
     */
    private string $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=true)
     */
    private ?int $dCode = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $left = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $right = null;

    /**
     * Количество знаков для округления в большую сторону
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $precision = 0;


    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function setPrecision(int $precision): void
    {
        $this->precision = $precision;
    }

    public function getRight(): ?string
    {
        return $this->right;
    }

    public function setRight(?string $right): void
    {
        $this->right = $right;
    }

    public function getLeft(): ?string
    {
        return $this->left;
    }

    public function setLeft(?string $left): void
    {
        $this->left = $left;
    }

    public function getDCode(): ?int
    {
        return $this->dCode;
    }

    public function setDCode(?int $dCode): void
    {
        $this->dCode = $dCode;
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
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = strtoupper($id);
    }
}