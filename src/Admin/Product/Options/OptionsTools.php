<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Options;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use EnjoysCMS\Module\Catalog\Entity\OptionValue;
use EnjoysCMS\Module\Catalog\Repository\OptionValueRepository;

final class OptionsTools
{

    private EntityRepository|OptionValueRepository $valueRepository;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        $this->valueRepository = $this->em->getRepository(OptionValue::class);
    }

    public function removeOrphanedValues(): void
    {
        $orphans = $this->valueRepository->getOrphans();
        foreach ($orphans as $orphan) {
            $this->em->remove($orphan);
        }
        $this->em->flush();
    }
}
