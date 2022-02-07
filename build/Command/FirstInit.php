<?php

declare(strict_types=1);

namespace Build\Command;

use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FirstInit extends Command
{
    protected static $defaultName = 'first-init';
    protected static $defaultDescription = '';

    public function __construct(private EntityManager $em)
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            unlink('/opt/project/build/db.sqlite');
            exec('rm /opt/project/build/migrations -r');
            exec('mkdir /opt/project/build/migrations -p');

            chdir('/opt/project/build');
            passthru('php console assets-install');
            passthru('php ../vendor/bin/doctrine-migrations diff');

            passthru('sleep 1');
            $output->writeln('sleep 1');

            passthru('php ../vendor/bin/doctrine-migrations migrate -n');


            $currency = new Currency();
            $currency->setName('Рубль');
            $currency->setDCode(643);
            $currency->setId('RUB');
            $currency->setRight(' руб.');


            $this->em->persist($currency);
            $this->em->flush();

            $_cur = $this->em->getRepository(Currency::class)->find('RUB');
            $output->writeln(sprintf('Insert currency: %s', $_cur->getName()));
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        $output->writeln('Migrate fixtures');
        passthru('php console import');


        return Command::SUCCESS;
    }


}
