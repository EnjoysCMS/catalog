<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \EnjoysCMS\Module\Catalog\Repository\Setting::class)]
#[ORM\Table(name: 'catalog_setting')]
final class Setting
{


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(name: '`key`', type: 'string', length: 255, unique: true)]
    private string $key;

    #[ORM\Column(type: 'string', length: 50)]
    private string $value;


    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }


}
