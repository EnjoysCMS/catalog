<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Currency;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit implements ModelInterface
{

    private Currency $currency;
    private EntityRepository $currencyRepository;

    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RedirectInterface $redirect,
    ) {
        $currencyId = $this->request->getQueryParams()['id']
            ?? throw new InvalidArgumentException('Currency id was not transmitted');

        $this->currencyRepository = $this->em->getRepository(Currency::class);

        $this->currency = $this->currencyRepository->find($currencyId)
            ?? throw new InvalidArgumentException(sprintf('Currency not found: %s', $currencyId));
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws ExceptionRule
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doProcess();
            $this->em->flush();
            exec('php ' . __DIR__ . '/../../../bin/catalog currency-rate-update');

            $this->redirect->http($this->urlGenerator->generate('catalog/admin/currency'), emit: true);
        }

        $this->renderer->setForm($form);

        return [
            'title' => 'Редактирование Валюты',
            'subtitle' => $this->currency->getName(),
            'form' => $this->renderer,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/currency') => 'Список валют',
                sprintf('Редактирование валюты: %s', $this->currency->getName())
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults([
            'id' => $this->currency->getId(),
            'name' => $this->currency->getName(),
            'digital_code' => $this->currency->getDCode(),
            'fraction_digits' => $this->currency->getFractionDigits(),
            'monetary_separator' => $this->currency->getMonetarySeparator(),
            'monetary_group_separator' => $this->currency->getMonetaryGroupSeparator(),
            'symbol' => $this->currency->getSymbol(),
            'pattern' => $this->currency->getPattern(),
        ]);
        $form->text('id', 'ID')
            ->setDescription(
                'Буквенный код ISO 4217. Изменять нельзя, чтобы изменить, нужно удалить и добавить новую'
            )
            ->setAttribute(AttributeFactory::create('disabled'));
        $form->text('name', 'Name')->setDescription(
            'Наименование валюты'
        )->addRule(Rules::REQUIRED);
        $form->number('digital_code', 'DCode')->setDescription(
            'Числовой код ISO 4217. Не обязательно, но желательно'
        );
        $form->number('fraction_digits', 'Fraction Digits')->setDescription(
            'Число цифр после запятой. Если пусто - будет использовано значение по-умолчанию для валют - это 2'
        );
        $form->text('monetary_separator', 'Monetary Separator')->setDescription(
            'Денежный разделитель. Если пусто - будет использовано значение по-умолчанию для этой валюты в зависимости от локали.'
        );
        $form->text('monetary_group_separator', 'Monetary Group Separator')->setDescription(
            'Разделитель групп для денежного формата. Если пусто - будет использовано значение по-умолчанию для этой валюты в зависимости от локали.'
        );
        $form->text('symbol', 'Currency Symbol')->setDescription(
            'Символ обозначения денежной единицы. Если пусто - будет использовано значение по-умолчанию для этой валюты в зависимости от локали.'
        );
        $form->text('pattern', 'Pattern')->setDescription(
            'Устанавливает шаблон средства форматирования. Шаблон в синтаксисе, описанном в » документации ICU DecimalFormat. Если пусто - будет использован шаблон по-умолчанию для этой валюты в зависимости от локали.'
        );
        $form->submit('edit', 'Редактировать');
        return $form;
    }


    private function doProcess(): void
    {
        $this->currency->setName($this->request->getParsedBody()['name'] ?? null);
        $this->currency->setDCode(
            ($this->request->getParsedBody()['digital_code'] ?? null) === ''
                ? null : (int)($this->request->getParsedBody()['digital_code'] ?? null)
        );
        $this->currency->setPattern(
            ($this->request->getParsedBody()['pattern'] ?? null) === ''
                ? null : $this->request->getParsedBody()['pattern'] ?? null
        );
        $this->currency->setSymbol(
            ($this->request->getParsedBody()['symbol'] ?? null) === ''
                ? null : $this->request->getParsedBody()['symbol'] ?? null
        );
        $this->currency->setFractionDigits(
            ($this->request->getParsedBody()['fraction_digits'] ?? null) === ''
                ? null : (int)($this->request->getParsedBody()['fraction_digits'] ?? null)
        );
        $this->currency->setMonetaryGroupSeparator(
            ($this->request->getParsedBody()['monetary_group_separator'] ?? null) === ''
                ? null : $this->request->getParsedBody()['monetary_group_separator'] ?? null
        );
        $this->currency->setMonetarySeparator(
            ($this->request->getParsedBody()['monetary_separator'] ?? null) === ''
                ? null : $this->request->getParsedBody()['monetary_separator'] ?? null
        );



    }

}
