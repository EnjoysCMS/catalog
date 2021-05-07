<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Blocks as Entity;
use EnjoysCMS\Module\Catalog\Entities\Category;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;

final class CategoryTree  extends AbstractBlock
{
    public function view()
    {
     //   var_dump($this->block->get);
        return 'ddd';
    }

    public static function getMeta(): ?array
    {
        return Yaml::parseFile(__DIR__.'/../../blocks.yml')[__CLASS__];
    }
}