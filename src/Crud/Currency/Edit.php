<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Currency;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit implements ModelInterface
{

    private Currency $currency;

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

        $currency = $this->entityManager->getRepository(Currency::class)->find($currencyId);
        if ($currency === null) {
            throw new InvalidArgumentException(sprintf('Currency not found: %s', $currencyId));
        }
        $this->currency = $currency;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doProcess();
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
        $form->text('id', 'ID');
        $form->text('name', 'Name');
        $form->number('digital_code', 'DCode');
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doProcess()
    {
        $this->currency->setId($this->requestWrapper->getPostData('id'));
        $this->currency->setName($this->requestWrapper->getPostData('name'));
        $this->currency->setDCode((int)$this->requestWrapper->getPostData('digital_code'));
        $this->currency->setPattern(
            $this->requestWrapper->getPostData('pattern') === '' ? null : $this->requestWrapper->getPostData(
                'pattern'
            )
        );
        $this->currency->setSymbol(
            $this->requestWrapper->getPostData('symbol') === '' ? null : $this->requestWrapper->getPostData(
                'symbol'
            )
        );
        $this->currency->setFractionDigits(
            $this->requestWrapper->getPostData(
                'fraction_digits'
            ) === '' ? null : (int)$this->requestWrapper->getPostData(
                'fraction_digits'
            )
        );
        $this->currency->setMonetaryGroupSeparator(
            $this->requestWrapper->getPostData(
                'monetary_group_separator'
            ) === '' ? null : $this->requestWrapper->getPostData(
                'monetary_group_separator'
            )
        );
        $this->currency->setMonetarySeparator(
            $this->requestWrapper->getPostData('monetary_separator') === '' ? null : $this->requestWrapper->getPostData(
                'monetary_separator'
            )
        );

        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('catalog/admin/currency'));
    }

}
