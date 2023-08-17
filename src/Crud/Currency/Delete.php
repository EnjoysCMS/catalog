<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Currency;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Elements\Hidden;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Entities\Currency\CurrencyRate;
use EnjoysCMS\Module\Catalog\Repositories\CurrencyRateRepository;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

final class Delete
{
    private Currency $currency;
    private EntityRepository $currencyRepository;
    private CurrencyRateRepository|EntityRepository $currencyRateRepository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly EntityManager $entityManager,
        private readonly ServerRequestInterface $request,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RedirectInterface $redirect,
    ) {
        $currencyId = $this->request->getQueryParams()['id']
            ?? throw new InvalidArgumentException('Currency id was not transmitted');


        $this->currencyRepository = $this->entityManager->getRepository(Currency::class);
        $this->currencyRateRepository = $this->entityManager->getRepository(CurrencyRate::class);

        $this->currency = $this->currencyRepository
            ->find($currencyId)
            ?? throw new InvalidArgumentException(
                sprintf('Currency not found: %s', $currencyId)
            );
    }

    /**
     * @throws OptimisticLockException
     * @throws ExceptionRule
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        /** @var Hidden $rule */
        $rule = $form->getElement('rule');

        if ($form->isSubmitted()) {
            try {
                $this->doProcess();
            } catch (Throwable $e) {
                if ($e->getCode() !== 1451) {
                    throw $e;
                }
                $rule->setRuleError(
                    'Эта валюта используется в ценах на товары, поэтому удалить нельзя.
                    Сначала нужно удалить все товары с этой валютой'
                );
            }
        }

        if ($rule->isRuleError()) {
            $form->header($rule->getRuleErrorMessage())->addClass('text-danger');
        }

        $this->renderer->setForm($form);

        return [
            'title' => 'Удаление Валюты',
            'subtitle' => $this->currency->getName(),
            'form' => $this->renderer,
            'breadcrumbs' => [
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/currency') => 'Список валют',
                sprintf('Удаление валюты: %s', $this->currency->getName())
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();

        $form->hidden('rule')->addRule(
            Rules::CALLBACK,
            'Нельзя удалять все валюты из системы',
            function () {
                return $this->currencyRepository->count([]) > 1;
            }
        );

        $form->submit('delete', 'Удалить');
        return $form;
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doProcess(): void
    {
        $this->currencyRateRepository->removeAllRatesByCurrency($this->currency);
        $this->entityManager->remove($this->currency);
        $this->entityManager->flush();
        $this->redirect->toRoute('catalog/admin/currency', emit: true);
    }

}
