<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Price;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductPrice;
use EnjoysCMS\Module\Catalog\Entities\ProductUnit;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Manage implements ModelInterface
{
    private ObjectRepository|EntityRepository|ProductRepository $productRepository;
    protected Product $product;
    /**
     * @var array|PriceGroup[]
     */
    private array $priceGroups;
    private array $prices = [];

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->getProduct();
        $this->priceGroups = $this->em->getRepository(PriceGroup::class)->findAll();
        if ($this->priceGroups === null) {
            $this->priceGroups = [];
        }

        /** @var ProductPrice $item */
        foreach ($this->product->getPrices() as $item) {
            $this->prices[$item->getPriceGroup()->getCode()] = $item;
        }
    }


    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->find($this->requestWrapper->getQueryData('id'));
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $this->renderer->setForm($form);


        return [
            'product' => $this->product,
            'form' => $this->renderer->output(),
            'subtitle' => 'Установка цен'
        ];
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
            'unit' => $this->product->getPrices()->get(0)?->getUnit()->getName(),
        ]);

        $form->select('currency', 'Валюта')->fill(function () {
            $ret = [];
            foreach ($this->em->getRepository(Currency::class)->findAll() as $item) {
                $ret[$item->getId()] = $item->getName();
            }
            return $ret;
        });

        $form->text('unit', 'Единица измерения');

        foreach ($this->priceGroups as $priceGroup) {
            $form->number(sprintf('price[%s]', $priceGroup->getCode()), $priceGroup->getTitle())
                ->setAttr(AttributeFactory::create('step', '0.01'))
                ->setDescription($priceGroup->getCode());
        }
        $form->submit('set', 'Установить');
        return $form;
    }

    private function doAction(): void
    {
        $currency = $this->em->getRepository(Currency::class)->find($this->requestWrapper->getPostData('currency'));

        if ($currency === null) {
            throw new \InvalidArgumentException('Currency not found');
        }

        $unit = $this->em->getRepository(ProductUnit::class)->findOneBy(['name' => $this->requestWrapper->getPostData('unit')]);
        if ($unit === null) {
            $unit = new ProductUnit();
            $unit->setName($this->requestWrapper->getPostData('unit'));
            $this->em->persist($unit);
            $this->em->flush();
        }


        foreach ($this->priceGroups as $priceGroup) {
            foreach ($this->requestWrapper->getPostData('price', []) as $code => $price) {
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
                    $priceEntity->setUnit($unit);
                    $this->em->persist($priceEntity);
                    continue;
                }


                if ($price == 0) {
                    $this->em->remove($this->prices[$code]);
                }

                $this->prices[$code]->setPrice($price);
                $this->prices[$code]->setCurrency($currency);
                $this->prices[$code]->setUnit($unit);
            }
        }


        $this->em->flush();
//        exit;
        Redirect::http($this->urlGenerator->generate('@a/catalog/product/prices', ['id' => $this->product->getId()]));
    }
}
