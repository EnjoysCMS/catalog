<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Console;


use ArrayIterator;
use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use PatchRanger\CartesianIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'currency-rate-update',
    description: 'Обновление котировок валют'
)]
final class CurrencyRate extends Command
{

    private array $rates;

    public function __construct(private EntityManager $em)
    {
        $this->rates = json_decode(file_get_contents('https://www.cbr-xml-daily.ru/latest.js'), true);
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Currency[] $currencies */
        $currencies = $this->em->getRepository(Currency::class)->findAll();
        $cartesianIterator = new CartesianIterator();
        $cartesianIterator->attachIterator(new ArrayIterator($currencies));
        $cartesianIterator->attachIterator(new ArrayIterator($currencies));
        $result = iterator_to_array($cartesianIterator);



        /** @var Currency[] $currencyPair */
        foreach ($result as $currencyPair) {
            $_currencyMain = $currencyPair[0];
            $_currencyConvert = $currencyPair[1];
            $currencyRate = $this->em->getRepository(\EnjoysCMS\Module\Catalog\Entities\Currency\CurrencyRate::class)->find(
                ['currencyMain' => $_currencyMain->getId(), 'currencyConvert' => $_currencyConvert->getId()]
            );

            if ($currencyRate === null) {
                $currencyRate = new \EnjoysCMS\Module\Catalog\Entities\Currency\CurrencyRate();
                $currencyRate->setCurrencyMain($_currencyMain);
                $currencyRate->setCurrencyConvert($_currencyConvert);
            }
            $rate = $this->getRate($_currencyMain, $_currencyConvert);
            $currencyRate->setRate($rate);

            $this->em->persist($currencyRate);

        }
        $this->em->flush();
        return self::SUCCESS;
    }

    /**
     * @param Currency $_currencyMain
     * @param Currency $_currencyConvert
     * @param $rates
     * @return float
     */
    protected function getRate(Currency $_currencyMain, Currency $_currencyConvert): float
    {
        if ($_currencyMain->getId() === $_currencyConvert->getId()) {
            return 1;
        }

        if ($_currencyMain->getId() === 'RUB') {
            return (float)$this->rates['rates'][$_currencyConvert->getId()];
        }

        if ($_currencyConvert->getId() === 'RUB') {
            return 1 / (float)$this->rates['rates'][$_currencyMain->getId()];
        }

        return (float)$this->rates['rates'][$_currencyConvert->getId()] / (float)$this->rates['rates'][$_currencyMain->getId()];
    }


}
