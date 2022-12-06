<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Currency;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add implements ModelInterface
{
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doProcess();
        }


        $this->renderer->setForm($form);
        return [
            'title' => 'Добавление Валюты',
            'subtitle' => '',
            'form' => $this->renderer,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/currency') => 'Список валют',
                'Добавление валюты'
            ],
        ];
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doProcess()
    {
        $currency = new Currency();
        $currency->setId(strtoupper($this->request->getParsedBody()['id'] ?? null));
        $currency->setName($this->request->getParsedBody()['name'] ?? null);
        $currency->setDCode(
            ($this->request->getParsedBody()['digital_code'] ?? null) === ''
                ? null : (int)($this->request->getParsedBody()['digital_code'] ?? null)
        );
        $currency->setPattern(
            ($this->request->getParsedBody()['pattern'] ?? null) === ''
                ? null : $this->request->getParsedBody()['pattern'] ?? null
        );
        $currency->setSymbol(
            ($this->request->getParsedBody()['symbol'] ?? null) === ''
                ? null : $this->request->getParsedBody()['symbol'] ?? null
        );
        $currency->setFractionDigits(
            ($this->request->getParsedBody()['fraction_digits'] ?? null) === ''
                ? null : (int)($this->request->getParsedBody()['fraction_digits'] ?? null)
        );
        $currency->setMonetaryGroupSeparator(
            ($this->request->getParsedBody()['monetary_group_separator'] ?? null) === ''
                ? null : $this->request->getParsedBody()['monetary_group_separator'] ?? null
        );
        $currency->setMonetarySeparator(
            ($this->request->getParsedBody()['monetary_separator'] ?? null) === ''
                ? null : $this->request->getParsedBody()['monetary_separator'] ?? null
        );

        $this->entityManager->persist($currency);
        $this->entityManager->flush();

        exec('php ' . __DIR__ . '/../../../bin/catalog currency-rate-update');

        Redirect::http($this->urlGenerator->generate('catalog/admin/currency'));
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form();
        $form->text('id', 'ID')
            ->setDescription(
                'Буквенный код ISO 4217'
            )
            ->addRule(Rules::REQUIRED)
            ->addRule(Rules::CALLBACK, 'Такая валюта уже зарегистрирована', function () {
                return is_null(
                    $this->entityManager->getRepository(Currency::class)->find(
                        strtoupper($this->request->getParsedBody()['id'] ?? null)
                    )
                );
            })
            ->addRule(Rules::CALLBACK, 'Валюту с таким кодом невозможно добавить', function () {
                return in_array(
                    strtoupper($this->request->getParsedBody()['id'] ?? null),
                    array_keys(json_decode(file_get_contents('https://www.cbr-xml-daily.ru/latest.js'), true)['rates']),
                    true
                );
            });
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
        $form->submit('add', 'Добавить');
        return $form;
    }
}
