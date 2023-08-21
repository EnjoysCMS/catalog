<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Entity\Currency;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use NumberFormatter;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_currency')]
class Currency
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'string', length: 3)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $dCode = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $symbol = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $pattern = null;

    #[ORM\Column(name: 'monetary_separator', type: 'string', nullable: true)]
    private ?string $monetarySeparator = null;

    #[ORM\Column(name: 'monetary_group_separator', type: 'string', nullable: true)]
    private ?string $monetaryGroupSeparator = null;

    /**
     * @deprecated
     */
    #[ORM\Column(name: 'left_char', type: 'string', length: 50, nullable: true)]
    private ?string $left = null;

    /**
     * @deprecated
     */
    #[ORM\Column(name: 'right_char', type: 'string', length: 50, nullable: true)]
    private ?string $right = null;

    /**
     * Количество знаков для округления в большую сторону
     * @deprecated
     */
    #[ORM\Column(name: 'precisions', type: 'integer', options: ['default' => 0])]
    private int $precision = 0;


    /**
     * Количество знаков для округления в большую сторону
     */
    #[ORM\Column(name: 'fraction_digits', type: 'integer', nullable: true)]
    private ?int $fractionDigits = null;

    /**
     * @deprecated
     */
    public function getPrecision(): int
    {
        return $this->precision;
    }

    /**
     * @deprecated
     */
    public function setPrecision(int $precision): void
    {
        $this->precision = $precision;
    }

    /**
     * @deprecated
     */
    public function getRight(): ?string
    {
        return $this->right;
    }

    /**
     * @deprecated
     */
    public function setRight(?string $right): void
    {
        $this->right = $right;
    }

    /**
     * @deprecated
     */
    public function getLeft(): ?string
    {
        return $this->left;
    }

    /**
     * @deprecated
     */
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

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Alias for getId
     * @see getId
     */
    public function getCode(): string
    {
        return $this->getId();
    }

    public function setId(string $id): void
    {
        $this->id = strtoupper($id);
    }

    public function __toString(): string
    {
        return $this->getName() . ', ' . $this->getCode() . ' (' . $this->getDCode() . ')';
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }


    public function setPattern(?string $pattern): void
    {
        $this->pattern = $pattern;
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }


    public function setSymbol(?string $symbol): void
    {
        $this->symbol = $symbol;
    }

    public function getMonetarySeparator(): ?string
    {
        return $this->monetarySeparator;
    }

    public function setMonetarySeparator(?string $monetarySeparator): void
    {
        $this->monetarySeparator = $monetarySeparator;
    }


    public function getMonetaryGroupSeparator(): ?string
    {
        return $this->monetaryGroupSeparator;
    }

    public function setMonetaryGroupSeparator(?string $monetaryGroupSeparator): void
    {
        $this->monetaryGroupSeparator = $monetaryGroupSeparator;
    }

    public function getFractionDigits(): ?int
    {
        return $this->fractionDigits;
    }

    public function setFractionDigits(?int $fractionDigits): void
    {
        $this->fractionDigits = $fractionDigits;
    }

    /**
     * @throws Exception
     */
    public function format(float|int $amount, $locale = 'ru_RU'): string
    {
        $numberFormatter = new NumberFormatter(
            $locale . sprintf(
                '@currency=%s',
                strtoupper($this->getId())
            ), NumberFormatter::CURRENCY
        );
        $numberFormatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);

        if (!is_null($this->getFractionDigits())) {
            $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $this->getFractionDigits());
        }

        if (!is_null($this->getSymbol())) {
            $numberFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, $this->getSymbol());
        }
        if (!is_null($this->getPattern())) {
            $numberFormatter->setPattern($this->getPattern());
        }
        if (!is_null($this->getMonetaryGroupSeparator())) {
            $numberFormatter->setSymbol(
                NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL,
                $this->getMonetaryGroupSeparator()
            );
        }
        if (!is_null($this->getMonetarySeparator())) {
            $numberFormatter->setSymbol(NumberFormatter::MONETARY_SEPARATOR_SYMBOL, $this->getMonetarySeparator());
        }
        if (false === $result = $numberFormatter->formatCurrency($amount, $this->getId())) {
            throw new Exception('An error occurred. Format currency is failed');
        }
        return $result;
    }




}
