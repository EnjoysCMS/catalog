<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\PriceGroup;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PriceGroupList
{

    public function __construct(
        private readonly EntityManager $em,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getContext(): array
    {
        return [
            'priceGroups' => $this->em->getRepository(PriceGroup::class)->findAll(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                'Группы цен',
            ],
        ];
    }

}
