<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Command;


use ArrayIterator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\CartesianIterator\CartesianIterator;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\Currency\Currency;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'catalog:currency:rate-update',
    description: 'Обновление котировок валют'
)]
final class CurrencyRate extends Command
{

    private array $rates = [];

    public function __construct(private EntityManager $em, private Config $config)
    {
        parent::__construct();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = new Client(
            [
                'verify' => false,
                RequestOptions::IDN_CONVERSION => true
            ]
        );
        $response = $client->get('https://www.cbr-xml-daily.ru/latest.js');
        $this->rates = json_decode($response->getBody()->getContents(), true);

        /** @var Currency[] $currencies */
        $currencies = $this->em->getRepository(Currency::class)->findAll();
        $cartesianIterator = new CartesianIterator();
        $cartesianIterator->attachIterator(new ArrayIterator($currencies));
        $cartesianIterator->attachIterator(new ArrayIterator($currencies));

        /** @var Currency[] $currencyPair */
        foreach ($cartesianIterator as $currencyPair) {
            $_currencyMain = $currencyPair[0];
            $_currencyConvert = $currencyPair[1];
            $currencyRate = $this->em->getRepository(
                \EnjoysCMS\Module\Catalog\Entity\Currency\CurrencyRate::class
            )->find(
                ['currencyMain' => $_currencyMain->getId(), 'currencyConvert' => $_currencyConvert->getId()]
            );

            if ($currencyRate === null) {
                $currencyRate = new \EnjoysCMS\Module\Catalog\Entity\Currency\CurrencyRate();
                $currencyRate->setCurrencyMain($_currencyMain);
                $currencyRate->setCurrencyConvert($_currencyConvert);
            }
            $ratio = $this->getRatio($currencyRate);
            $rate = $this->getRate($_currencyMain, $_currencyConvert);
            $currencyRate->setRate($rate * $ratio);

            $output->writeln(
                sprintf(
                    '%s/%s -> %f',
                    $_currencyMain->getCode(),
                    $_currencyConvert->getCode(),
                    $currencyRate->getRate()
                ),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $this->em->persist($currencyRate);
        }
        $this->em->flush();
        return self::SUCCESS;
    }

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

        return (float)$this->rates['rates'][$_currencyConvert->getId(
            )] / (float)$this->rates['rates'][$_currencyMain->getId()];
    }

    private function getRatio(\EnjoysCMS\Module\Catalog\Entity\Currency\CurrencyRate $currencyRate)
    {
        $ratio = $this->config->get('currency->ratio', []);
        return $ratio[$currencyRate->__toString()] ?? 1;
    }


}
