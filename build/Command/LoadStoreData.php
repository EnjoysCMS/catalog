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

final class LoadStoreData extends Command
{
    protected static $defaultName = 'import';
    protected static $defaultDescription = 'Automatically Import Products from TDT';

    private ?string $xmlFileTDT = null;

    private OptionKeyRepository|ObjectRepository|EntityRepository $keyRepository;
    private OptionValueRepository|ObjectRepository|EntityRepository $valueRepository;
    private Repositories\Category|ObjectRepository|EntityRepository $categoryRepository;
    private Repositories\Product|ObjectRepository|EntityRepository $productRepository;

    public function __construct(private EntityManager $em)
    {
        $this->xmlFileTDT = __DIR__.'/../fixtures/store.xml';

        $this->em->getConfiguration()->setSQLLogger(null);
        $this->keyRepository = $this->em->getRepository(OptionKey::class);
        $this->valueRepository = $this->em->getRepository(OptionValue::class);
        $this->categoryRepository = $this->em->getRepository(Category::class);
        $this->productRepository = $this->em->getRepository(Product::class);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // ...
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Path', $this->xmlFileTDT)
        ;
    }

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists((string)$input->getOption('path'))) {
            throw new InvalidArgumentException("Input file no detected.\nUpdate failed.");
        }
        $reader = new XMLReader;
        $reader->open((string)$input->getOption('path'));

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('debug');
        $progressBar->setOverwrite(true);
        $progressBar->setRedrawFrequency(42);
        $progressBar->start();

        $this->em->createQuery('UPDATE \EnjoysCMS\Module\Catalog\Entities\Quantity q SET q.updated = false')->execute();

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'item') {
                $node = new SimpleXMLElement($reader->readOuterXML());
                $this->import($node);
                $progressBar->advance();

                if ($progressBar->getProgress() % 200 === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }
            }
        }
        $this->em->flush();
        $this->em->clear();

        $this->em->createQuery(
            'UPDATE \EnjoysCMS\Module\Catalog\Entities\Quantity q SET q.qty = 0 WHERE q.updated = false'
        )->execute();

        $progressBar->finish();

        $output->writeln([
            '',
            sprintf(
                'Memory usage: %s',
                bytes2iniSize(
                    memory_get_peak_usage(true)
                )
            )
        ]);


        return Command::SUCCESS;
    }

    /**
     * @param SimpleXMLElement $node
     * @return void
     * @throws ORMException
     */
    private function import(SimpleXMLElement $node)
    {
        $countValue = $this->getNormalizeCountValue((string)$node->count);
        if (empty($node->code) || $countValue <= 0) {
            return;
        }

        $category = $this->getCategory((string)$node->groupg);
        $section = $this->getSection((string)$node->typeg, $category);

        $product = $this->productRepository->findOneBy(['productCode' => (string)$node->code]);

        if ($product === null) {
            //Создаём новый продукт
            $product = new Product();
            $product->setProductCode((string)$node->code);
            $product->setDescription('');
            //Создаём ссылку на новый продукт
            $url = new Url();
            $url->setDefault(true);
            $url->setPath(URLify::slug((string)$node->modelg));
            $url->setProduct($product);
            $product->addUrl($url);
        }

        $product->setName((string)$node->modelg);
        $product->setCategory($section);
        $product->setMaxDiscount($this->getNormalizeMaxDiscountValue((string) $node->undiscounted));

        $quantity = $product->getQuantity();

        if ($quantity->getQty() <= 0 && $this->checkToSetArrivalDate($quantity->getUpdatedAt())) {
            $quantity->setArrivalDate(new \DateTimeImmutable());
        }
        $quantity->setQty($countValue);
        $quantity->setUpdated(true);

        $product->setQuantity($quantity);


        foreach ($this->getOptions($node) as $option) {
            $optionKey = $this->keyRepository->getOptionKey($option['key'], $option['unit']);

            $product->removeOptionByKey($optionKey);

            foreach ($option['value'] as $value) {
                if (in_array($value, $option['excludeValues'] ?? [])) {
                    continue;
                }
                $optionValue = $this->valueRepository->getOptionValue($value, $optionKey);
                $product->addOption($optionValue);
            }
        }

        try {
            $product->addPrice(
                $this->getPrice(
                    'ROZ',
                    $node->price->price1,
                    $this->getUnit((string)$node->volume),
                    $product->getPrice('ROZ')
                )
            );


            $product->addPrice(
                $this->getPrice(
                    'OPT',
                    $node->price->price2,
                    $this->getUnit((string)$node->volume),
                    $product->getPrice('OPT')
                )
            );

            $product->addPrice(
                $this->getPrice(
                    'OLD',
                    $node->price->oldprice,
                    $this->getUnit((string)$node->volume),
                    $product->getPrice('OLD')
                )
            );

            $product->addPrice(
                $this->getPrice(
                    'INC',
                    $node->price->incoming_price,
                    $this->getUnit((string)$node->volume),
                    $product->getPrice('INC')
                )
            );
        } catch (\Exception $e) {
            throw $e;
        }

        $this->em->persist($product);
    }

    /**
     * @throws ORMException
     */
    private function getPrice(
        string $code,
        $price,
        ProductUnit $productUnit,
        ProductPrice $productPrice = null
    ): ?ProductPrice {
        if ((float)$price->value == 0) {
            return null;
        }

        $currency = $this->getCurrency((string)$price->cur);
        if ($productPrice === null) {
            $productPrice = new ProductPrice();
            $productPrice->setPriceGroup($this->getPriceGroup($code));
        }
        $productPrice->setCurrency($currency);
        $productPrice->setUnit($productUnit);
        $productPrice->setPrice((float)$price->value);

        return $productPrice;
    }


    /**
     * @throws ORMException
     */
    private function getPriceGroup(string $code): PriceGroup
    {
        $priceGroup = $this->em->getRepository(PriceGroup::class)->findOneBy(['code' => $code]);
        if ($priceGroup === null) {
            $priceGroup = new PriceGroup();
            $priceGroup->setCode($code);
            $priceGroup->setTitle($code);
            $this->em->persist($priceGroup);
            $this->em->getUnitOfWork()->commit($priceGroup);
        }
        return $priceGroup;
    }

    private function getCurrency(string $currencyCode): Currency
    {
        $currency = match ($currencyCode) {
//            'дол.' => $this->em->getRepository(Currency::class)->find('USD'),
            default => $this->em->getRepository(Currency::class)->find('RUB'),
        };

        if ($currency === null) {
            throw new \InvalidArgumentException(sprintf('Currency not configure or not set: %s', $currencyCode));
        }

        return $currency;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    private function getCategory(string $title): Category
    {
        $category = $this->categoryRepository->findOneBy(['title' => $title, 'parent' => null]);
        if ($category === null) {
            //Создаём новую категорию
            $category = new Category();
            $category->setTitle($title);
            $category->setUrl(URLify::slug($category->getTitle()));
            $category->setSort(0);

            $this->em->persist($category);
            $this->em->getUnitOfWork()->commit($category);
        }

        return $category;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    private function getSection(string $title, Category $parent): Category
    {
        $section = $this->categoryRepository->findOneBy(['title' => $title, 'parent' => $parent]);
        if ($section === null) {
            //Создаём новую подкатегорию
            $section = new Category();
            $section->setTitle($title);
            $section->setParent($parent);
            $section->setUrl(URLify::slug($section->getTitle()));
            $section->setSort(0);

            $this->em->persist($section);
            $this->em->getUnitOfWork()->commit($section);
        }
        return $section;
    }

    private function getOptions(SimpleXMLElement $node): array
    {
        $manufactures = explode("|", (string)$node->firmag);

        return [
            [
                'key' => 'Бренд',
                'unit' => '',
                'value' => [$manufactures[0]],
                'excludeValues' => ['']
            ],
            [
                'key' => 'Нетто',
                'unit' => 'кг.',
                'value' => [(string)$node->weight],
                'excludeValues' => ['', '0']
            ],
            [
                'key' => 'Брутто',
                'unit' => 'кг.',
                'value' => [(string)$node->gross],
                'excludeValues' => ['', '0']
            ],
        ];
    }

    /**
     * @throws ORMException
     */
    private function getUnit(string $volume): ProductUnit
    {
        $unit = $this->em->getRepository(ProductUnit::class)->findOneBy(['name' => $volume]);
        if ($unit === null) {
            $unit = new ProductUnit();
            $unit->setName($volume);
            $this->em->persist($unit);
            $this->em->getUnitOfWork()->commit($unit);
        }
        return $unit;
    }

    private function getNormalizeCountValue(string $count): float|int
    {
        return \floatval($this->replaceCommaOnDot($count));
    }

    private function replaceCommaOnDot(string $input): string
    {
        return \str_replace([','], ['.'], $input);
    }

    private function checkToSetArrivalDate(?\DateTimeInterface $updatedAt): bool
    {
        if ($updatedAt === null) {
            return true;
        }
        return $updatedAt->format('d-m-Y') !== (new \DateTime())->format('d-m-Y');
//        return  $updatedAt->diff(new \DateTime())->days > 0;
    }

    private function getNormalizeMaxDiscountValue(string $maxDiscount): ?int
    {
        return is_numeric($maxDiscount) ? (int)$maxDiscount : null;
    }

}
