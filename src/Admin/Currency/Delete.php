<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Currency;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Entities\Currency\CurrencyRate;
use EnjoysCMS\Module\Catalog\Repository\CurrencyRateRepository;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

final class Delete
{
    private Currency $currency;
    private EntityRepository $repository;
    private CurrencyRateRepository|EntityRepository $rateRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly ServerRequestInterface $request,
    ) {
        $currencyId = $this->request->getQueryParams()['id']
            ?? throw new InvalidArgumentException('Currency id was not transmitted');


        $this->repository = $this->entityManager->getRepository(Currency::class);
        $this->rateRepository = $this->entityManager->getRepository(CurrencyRate::class);

        $this->currency = $this->repository
            ->find($currencyId)
            ?? throw new InvalidArgumentException(
                sprintf('Currency not found: %s', $currencyId)
            );
    }

    /**
     * @throws ExceptionRule
     */
    public function getForm(): Form
    {
        $form = new Form();

        $form->hidden('rule')->addRule(
            Rules::CALLBACK,
            'Нельзя удалять все валюты из системы',
            function () {
                return $this->repository->count([]) > 1;
            }
        );

        $form->submit('delete', 'Удалить');
        return $form;
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(): void
    {
        $this->rateRepository->removeAllRatesByCurrency($this->currency);
        $this->entityManager->remove($this->currency);
        $this->entityManager->flush();
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

}
