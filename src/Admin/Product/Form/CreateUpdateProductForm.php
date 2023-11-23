<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Form;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\QueryException;
use Enjoys\Cookie\Cookie;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\Category;
use EnjoysCMS\Module\Catalog\Entity\Product;
use EnjoysCMS\Module\Catalog\Entity\ProductUnit;
use EnjoysCMS\Module\Catalog\Entity\Url;
use EnjoysCMS\Module\Catalog\Entity\Vendor;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

final class CreateUpdateProductForm
{

    private EntityRepository|\EnjoysCMS\Module\Catalog\Repository\Product $productRepository;
    private EntityRepository|\EnjoysCMS\Module\Catalog\Repository\Category $categoryRepository;


    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly Config $config,
        private readonly Cookie $cookie,
    ) {
        $this->productRepository = $em->getRepository(Product::class);
        $this->categoryRepository = $em->getRepository(Category::class);
    }

    /**
     * @throws ExceptionRule
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    public function getForm(Product $product = null): Form
    {
        $defaults = [
            'name' => $product?->getName(),
            'sku' => $product?->getSku(),
            'barcodes' => implode(' ', $product?->getBarCodes() ?? []),
            'vendor' => $product?->getVendor()?->getName(),
            'vendorCode' => $product?->getVendorCode(),
            'productCode' => $product?->getProductCode(),
            'url' => $product?->getUrl()->getPath(),
            'description' => $product?->getDescription(),
            'unit' => $product?->getUnit()?->getName(),
            'active' => [(int)($product?->isActive() ?? 1)],
            'hide' => [(int)($product?->isHide() ?? 0)],
            'category' => $product?->getCategory()?->getId()
                ?? $this->request->getQueryParams()['category_id']
                    ?? $this->cookie->get('__catalog__last_category_when_add_product'),
        ];

        $form = new Form();

        $form->setDefaults($defaults);

        $form->checkbox('active')
            ->setPrefixId('active')
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => 'Включен?']);

        $form->checkbox('hide')
            ->setPrefixId('hide')
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => 'Скрыт?']);

        $elCategory = $form->select('category', 'Категория')
            ->addRule(Rules::REQUIRED)
            ->fill(
                $this->categoryRepository->getFormFillArray()
            );

        if ($product !== null && $this->config->get('admin->product->disableChangeCategory', false)) {
            $elCategory->setAttribute(AttributeFactory::create('disabled'));
        }

        $elName = $form->text('name', 'Наименование')
            ->addRule(Rules::REQUIRED);

        if ($product !== null && $this->config->get('admin->product->disableChangeName', false)) {
            $elName->setAttribute(AttributeFactory::create('disabled'));
        }

        $elSku = $form->text('sku', 'SKU')
            ->setDescription(
                'Не обязательно. Уникальный идентификатор продукта, уникальный артикул, внутренний код
            в системе учета или что-то подобное, используется для внутренних команд и запросов,
            но также можно и показывать это поле наружу'
            )
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, SKU уже используется',
                function () use ($product) {
                    /** @var Product $check */

                    $check = $this->productRepository->findOneBy(
                        ['sku' => $this->request->getParsedBody()['sku'] ?? '']
                    );


                    if ($product?->getSku() === $check?->getSku()) {
                        return true;
                    }
                    if ($check === null) {
                        return true;
                    }
                    return false;
                }
            );

        if ($product !== null && $this->config->get('admin->product->disableChangeSku', false)) {
            $elSku->setAttribute(AttributeFactory::create('disabled'));
        }

        $form->text('barcodes', 'Штрих-коды')
            ->setDescription(
                'Не обязательно. Штрих-коды, если их несколько можно указать через пробел.'
            );

        $elVendor = $form->text('vendor', 'Бренд или производитель')
            ->setDescription(
                'Не обязательно.'
            );

        if ($product !== null && $this->config->get('admin->product->disableChangeVendor', false)) {
            $elVendor->setAttribute(AttributeFactory::create('disabled'));
        }

        $form->text('vendorCode', 'Артикул')
            ->setDescription(
                'Не обязательно. Артикул товара, так как он значится у поставщика.'
            );

        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(Rules::CALLBACK, 'Не допустимые символы', function () {
                preg_match('/[.\/]/', $this->request->getParsedBody()['url'] ?? '', $matches);
                return !$matches;
            })
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () use ($product) {
                    $category = $this->categoryRepository->find($this->request->getParsedBody()['category'] ?? null);

                    try {
                        if ($this->productRepository->getFindByUrlBuilder(
                                $this->request->getParsedBody()['url'] ?? null,
                                $category
                            )->getQuery()->getOneOrNullResult() === null) {
                            return true;
                        }
                    } catch (NonUniqueResultException) {
                        return false;
                    }

                    /** @var Url $url */
                    foreach ($product?->getUrls() ?? [] as $url) {
                        if ($url->getProduct()->getId() === $product->getId()) {
                            return true;
                        }
                    }

                    return false;
                }
            );
        $form->textarea('description', 'Описание');

        $elUnit = $form->text('unit', 'Единица измерения');
        if ($product !== null && $this->config->get('admin->product->disableChangeUnit', false)) {
            $elUnit->setAttribute(AttributeFactory::create('disabled'));
        }

        $form->submit('add');
        return $form;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotSupported
     */
    public function doAction(Product $product = null): Product
    {
        $productEntity = $product ?? new Product();


        /** @var Category|null $category */
        $category = $this->em->getRepository(Category::class)->find(
            $this->request->getParsedBody()['category'] ?? 0
        );

        $productEntity->setName($this->request->getParsedBody()['name'] ?? null);

        if (!$this->config->get('admin->product->disableChangeSku', false)) {
            $sku = $this->request->getParsedBody()['sku'] ?? null;
            $productEntity->setSku(empty($sku) ? null : $sku);
        }

        $vendorName = $this->request->getParsedBody()['vendor'] ?? '';
        if (!empty($vendorName)) {
            $vendor = $this->em->getRepository(Vendor::class)->findOneBy(
                ['name' => $vendorName]
            );
            if ($vendor === null) {
                $vendor = new Vendor();
                $vendor->setId(Uuid::uuid7()->toString());
                $vendor->setName($vendorName);
                $this->em->persist($vendor);
            }
            $productEntity->setVendor($vendor);
        }


        $productEntity->setVendorCode($this->request->getParsedBody()['vendorCode'] ?? null);
        $productEntity->setBarCodes(
            $this->request->getParsedBody()['barcodes'] ? array_values(
                array_filter(
                    explode(
                        ' ',
                        $this->request->getParsedBody()['barcodes']
                    ), static fn($i) => !empty($i)
                )
            ) : null
        );
        $productEntity->setDescription($this->request->getParsedBody()['description'] ?? null);


        $unitValue = $this->request->getParsedBody()['unit'] ?? '';
        $unit = $this->em->getRepository(ProductUnit::class)->findOneBy(['name' => $unitValue]);
        if ($unit === null) {
            $unit = new ProductUnit();
            $unit->setName($unitValue);
            $this->em->persist($unit);
            $this->em->flush();
        }
        $productEntity->setUnit($unit);

        $productEntity->setCategory($category);
        $productEntity->setActive((bool)($this->request->getParsedBody()['active'] ?? false));
        $productEntity->setHide((bool)($this->request->getParsedBody()['hide'] ?? false));

        $this->em->persist($productEntity);

        $urlString = $this->request->getParsedBody()['url'] ?? '';

        /** @var Url $url */
        $urlSetFlag = false;
        foreach ($productEntity->getUrls() as $url) {
            if ($url->getPath() === $urlString) {
                $url->setDefault(true);
                $urlSetFlag = true;
                continue;
            }
            $url->setDefault(false);
        }

        if ($urlSetFlag === false) {
            $url = new Url();
            $url->setPath($urlString);
            $url->setDefault(true);
            $url->setProduct($productEntity);
            $this->em->persist($url);
            $productEntity->addUrl($url);
        }
        $this->em->flush();
        return $productEntity;
    }
}
