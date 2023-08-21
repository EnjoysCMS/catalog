<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Blocks;


use Doctrine\ORM\EntityManager;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use EnjoysCMS\Core\Block\Entity\Block;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\Currency\Currency;
use NumberFormatter;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class CurrencySelect
{

    /**
     * @var Currency[]
     */
    private array $currencies;

    public function __construct(
        private Config $config,
        private Environment $twig,
        private EntityManager $em,
        private ServerRequestInterface $request,
        Block $block
    ) {
        /** @var Currency[] $currencies */
        $this->currencies = $this->em->getRepository(Currency::class)->findAll();
    }

    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
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
            ->fill($this->getFillData())
        ;

        if (empty($this->getOption('template'))) {
            $result = sprintf('<form><select%s>', $select->getAttributesString());
            foreach ($select->getElements() as $element) {
                $result .= $element->baseHtml();
            }
            $result .= '</select></form>';
            return $result;
        } else {
            return $this->twig->render(
                $this->getOption('template'),
                [
                    'form' => $form,
                    'data' => $this->getFillData(),
                    'currencies' => $this->currencies,
                    'currentCurrencyCode' => $currentCurrencyCode,
                    'blockOptions' => $this->getOptions()
                ]
            );
        }
    }


    public function getFillData(): array
    {

        return match ($this->getOption('name-as')) {
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
                    return [$item->getCode() => $item->getSymbol() ?? $numberFormatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL)];
                }, $this->currencies)
            ),
            default => [],
        };
    }
}
