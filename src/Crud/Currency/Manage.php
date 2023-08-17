<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Currency;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;

final class Manage
{
    private EntityRepository $currencyRepository;

    public function __construct(
        private readonly EntityManager $em,
    ) {
        $this->currencyRepository = $this->em->getRepository(Currency::class);
    }

    public function getContext(): array
    {
        return [
            'currencies' => $this->currencyRepository->findAll(),
        ];
    }
}
