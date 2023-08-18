<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Widgets;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Core\Block\AbstractWidget;
use EnjoysCMS\Core\Block\Annotation\Widget;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Widget('Общее количество товаров в каталоге')]
final class CountProducts extends AbstractWidget
{

    public function __construct(
        private readonly EntityManager $em,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }


    /**
     * @throws NotSupported
     */
    public function view(): string
    {
        $repository = $this->em->getRepository(Product::class);

        $url = $this->urlGenerator->generate('@catalog_product_list');
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

}
