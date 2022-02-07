<?php

declare(strict_types=1);

namespace Build\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductPrice;
use EnjoysCMS\Module\Catalog\Entities\ProductUnit;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use EnjoysCMS\Module\Catalog\Repositories;
use EnjoysCMS\Module\Catalog\Repositories\OptionKeyRepository;
use EnjoysCMS\Module\Catalog\Repositories\OptionValueRepository;
use Exception;
use InvalidArgumentException;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use XMLReader;

final class AssetsInstall extends Command
{
    protected static $defaultName = 'assets-install';
    protected static $defaultDescription = '';

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {

        system('cd /opt/project && yarn install');
        system('cd /opt/project/vendor/enjoyscms/admin && yarn install');
//        system('cd /opt/project/vendor/enjoyscms/elfinder && yarn install');


        return Command::SUCCESS;
    }


}
