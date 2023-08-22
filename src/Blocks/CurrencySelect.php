<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use Doctrine\ORM\EntityManager;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use EnjoysCMS\Core\Block\AbstractBlock;
use EnjoysCMS\Core\Block\Annotation\Block;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\Currency\Currency;
use NumberFormatter;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Block(
    name: 'Выбор валюты',
    options: [
        'template' => [
            'value' => '',
            'name' => 'Путь до template',
            'description' => 'Обязательно',
        ],
        'name-as' => [
            'value' => 'code',
            'name' => ' При выборе валюты показывать как...',
            'form' => [
                'type' => 'select',
                'data' => [
                    'code' => 'код валюты (USD, RUB и тд)',
                    'name' => 'наименование валюты (Российский рубль ... и тд)',
                    'symbol' => 'символ валюты ($, ₽, € ... и тд)',
                ]
            ]
        ]
    ]
)]
final class CurrencySelect extends AbstractBlock
{

    /**
     * @var Currency[]
     */
    private array $currencies;

    public function __construct(
        private readonly Config $config,
        private readonly Environment $twig,
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {
        /** @var Currency[] $currencies */
        $this->currencies = $this->em->getRepository(Currency::class)->findAll();
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function view(): string
    {
        if (null !== $currentCurrency = ($this->request->getQueryParams()['currency'] ?? null)) {
            $this->config->setCurrencyCode($currentCurrency);
        }


        $form = new Form('get', id: 'currency_select_block');
        $form->setDefaults([
            'currency' => $currentCurrencyCode = $this->config->getCurrentCurrencyCode()
        ]);
        $select = $form->select('currency')
            ->addAttribute(AttributeFactory::create('onchange', 'this.form.submit();'))
            ->addClass('form-select form-select-sm')
            ->fill($this->getFillData());

        if (empty($this->getBlockOptions()->getValue('template'))) {
            $result = sprintf('<form><select%s>', $select->getAttributesString());
            foreach ($select->getElements() as $element) {
                $result .= $element->baseHtml();
            }
            $result .= '</select></form>';
            return $result;
        } else {
            return $this->twig->render(
                $this->getBlockOptions()->getValue('template'),
                [
                    'form' => $form,
                    'data' => $this->getFillData(),
                    'currencies' => $this->currencies,
                    'currentCurrencyCode' => $currentCurrencyCode,
                    'blockOptions' => $this->getBlockOptions()
                ]
            );
        }
    }


    public function getFillData(): array
    {
        return match ($this->getBlockOptions()->getValue('name-as')) {
            'code' => array_merge(...array_map(fn($item) => [$item->getCode() => $item->getCode()], $this->currencies)),
            'name' => array_merge(...array_map(fn($item) => [$item->getCode() => $item->getName()], $this->currencies)),
            'symbol' => array_merge(
                ...array_map(function ($item) {
                    $numberFormatter = new NumberFormatter(
                        'ru_RU' . sprintf(
                            '@currency=%s',
                            $item->getCode()
                        ), NumberFormatter::CURRENCY
                    );
                    return [
                        $item->getCode() => $item->getSymbol() ?? $numberFormatter->getSymbol(
                                NumberFormatter::CURRENCY_SYMBOL
                            )
                    ];
                }, $this->currencies)
            ),
            default => [],
        };
    }
}
