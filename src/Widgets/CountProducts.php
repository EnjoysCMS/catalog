<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Widgets;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Components\Widgets\AbstractWidgets;
use EnjoysCMS\Core\Entities\Widget;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;

final class CountProducts extends AbstractWidgets
{

    public function view()
    {
        /** @var \EnjoysCMS\Module\Catalog\Repositories\Product $repository */
        $repository = $this->getContainer()->get(EntityManager::class)->getRepository(Product::class);

        $url = $this->getContainer()->get(UrlGeneratorInterface::class)->generate('catalog/admin/products');
        return <<<HTML
<div class="small-box bg-warning">
    <div class="inner">
        <h3>{$repository->count(['active' => true])}</h3>
        <p>Количество товаров</p>
    </div>
    <div class="icon">
        <i class="ion ion-stats-bars"></i>
    </div>
    <a href="{$url}" class="small-box-footer">Перейти <i class="fas fa-arrow-circle-right"></i></a>
</div>
HTML;
    }

    public static function getWidgetDefinitionFile(): string
    {
        return __DIR__ . '/../../widgets.yml';
    }

    public static function getMeta(): array
    {
        return Yaml::parseFile(static::getWidgetDefinitionFile())[static::class];
    }
}