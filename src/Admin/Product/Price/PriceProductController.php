<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Admin\Product\Price;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Admin\AdminController;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\Currency\Currency;
use EnjoysCMS\Module\Catalog\Entity\PriceGroup;
use EnjoysCMS\Module\Catalog\Entity\Product;
use EnjoysCMS\Module\Catalog\Entity\ProductPrice;
use EnjoysCMS\Module\Catalog\Repository\Product as ProductRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Route(
    path: '/admin/catalog/product/prices',
    name: '@catalog_product_prices',
    comment: 'Установка цен товару'
)]
final class PriceProductController extends AdminController
{
    private EntityRepository|ProductRepository $productRepository;
    protected Product $product;
    /**
     * @var array|PriceGroup[]
     */
    private array $priceGroups;
    private array $prices = [];


    /**
     * @throws NoResultException
     * @throws NotSupported
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(
        Container $container,
        Config $config,
        \EnjoysCMS\Module\Admin\Config $adminConfig,
        private readonly EntityManager $em,
    ) {
        parent::__construct($container, $config, $adminConfig);

        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->productRepository->find(
            $this->request->getQueryParams()['id'] ?? 0
        ) ?? throw new NoResultException();

        $this->priceGroups = $this->em->getRepository(PriceGroup::class)->findAll();

        foreach ($this->product->getPrices() as $item) {
            $this->prices[$item->getPriceGroup()->getCode()] = $item;
        }
    }

    /**
     * @throws ORMException
     * @throws RuntimeError
     * @throws DependencyException
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotFoundException
     */
    public function __invoke(): ResponseInterface
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();

            return $this->redirect->toUrl();
        }

        $rendererForm = $this->adminConfig->getRendererForm($form);

        $this->breadcrumbs->add('@catalog_products', 'Список продуктов')->setLastBreadcrumb(
            sprintf('Менеджер цен: %s', $this->product->getName())
        );


        return $this->response(
            $this->twig->render(
                $this->templatePath . '/product/prices/manage.twig',
                [
                    'product' => $this->product,
                    'form' => $rendererForm->output(),
                    'subtitle' => 'Установка цен'
                ]
            )
        );
    }


    private function getForm(): Form
    {
        $priceDefaults = [];
        foreach ($this->prices as $code => $price) {
            $priceDefaults[$code] = $price->getPrice();
        }


        $form = new Form();
        $form->setDefaults([
            'price' => $priceDefaults,
            'currency' => $this->product->getPrices()->get(0)?->getCurrency()->getId(),
        ]);

        $form->select('currency', 'Валюта')->fill(function () {
            $ret = [];
            foreach ($this->em->getRepository(Currency::class)->findAll() as $item) {
                $ret[$item->getId()] = $item->getName();
            }
            return $ret;
        });

        $form->header(sprintf('Единица измерения: %s', $this->product->getUnit()?->getName() ?? '-'));

        foreach ($this->priceGroups as $priceGroup) {
            $form->number(sprintf('price[%s]', $priceGroup->getCode()), $priceGroup->getTitle())
                ->setAttribute(AttributeFactory::create('step', '0.01'))
                ->setDescription($priceGroup->getCode());
        }
        $form->submit('set', 'Установить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $currency = $this->em->getRepository(Currency::class)->find(
            $this->request->getParsedBody()['currency'] ?? null
        );

        if ($currency === null) {
            throw new InvalidArgumentException('Currency not found');
        }


        foreach ($this->priceGroups as $priceGroup) {
            foreach (($this->request->getParsedBody()['price'] ?? []) as $code => $price) {
                if ($priceGroup->getCode() !== $code) {
                    continue;
                }

                if (!is_numeric($price)) {
                    continue;
                }


                if (!array_key_exists($code, $this->prices)) {
                    if ($price == 0) {
                        continue;
                    }

                    $priceEntity = new ProductPrice();
                    $priceEntity->setPrice($price);
                    $priceEntity->setProduct($this->product);
                    $priceEntity->setPriceGroup($priceGroup);
                    $priceEntity->setCurrency($currency);
                    $this->em->persist($priceEntity);
                    continue;
                }


                if ($price == 0) {
                    $this->em->remove($this->prices[$code]);
                }

                $this->prices[$code]->setPrice($price);
                $this->prices[$code]->setCurrency($currency);
            }
        }

        $this->em->flush();
    }
}
