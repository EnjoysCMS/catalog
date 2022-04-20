<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Currency;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;

final class Manage implements ModelInterface
{
    private ObjectRepository|EntityRepository $currencyRepository;

    public function __construct(
        private EntityManager $em
    ) {
        $this->currencyRepository = $this->em->getRepository(Currency::class);
    }

    public function getContext(): array
    {
        return [
            'currencies' => $this->currencyRepository->findAll()
        ];
    }
}
