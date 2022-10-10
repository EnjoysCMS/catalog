<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Currency;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Elements\Hidden;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Entities\Currency\CurrencyRate;
use InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Delete implements ModelInterface
{
    private Currency $currency;
    private ObjectRepository|EntityRepository $repo;

    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $currencyId = $this->requestWrapper->getQueryData('id');
        if ($currencyId === null) {
            throw new InvalidArgumentException('Currency id was not transmitted');
        }

        $this->repo = $this->entityManager->getRepository(Currency::class);

        $currency = $this->repo->find($currencyId);
        if ($currency === null) {
            throw new InvalidArgumentException(sprintf('Currency not found: %s', $currencyId));
        }
        $this->currency = $currency;
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
            } catch (\Throwable $e) {
                if ($e->getCode() !== 1451){
                    throw $e;
                }
                $rule->setRuleError('Эта валюта используется в ценах на товары, поэтому удалить нельзя. Сначала нужно удалить все товары с этой валютой');
            }
        }

        if ($rule->isRuleError()){
            $form->header($rule->getRuleErrorMessage())->addClass('text-danger');
        }

        $this->renderer->setForm($form);

        return [
            'title' => 'Удаление Валюты',
            'subtitle' => $this->currency->getName(),
            'form' => $this->renderer,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
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

        $form->hidden('rule')->addRule(Rules::CALLBACK, 'Нельзя удалять все валюты из системы', function () {
            return $this->repo->count([]) > 1;
        });

        $form->submit('delete', 'Удалить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doProcess()
    {
        $this->entityManager->getRepository(CurrencyRate::class)->removeAllRatesByCurrency($this->currency);
        $this->entityManager->remove($this->currency);
        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/currency'));
    }

}
