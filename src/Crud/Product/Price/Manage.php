<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Price;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\ProductPrice;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Manage
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
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RedirectInterface $redirect,
    ) {
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
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
            $this->em->flush();
            $this->redirect->toUrl( emit: true);
        }

        $this->renderer->setForm($form);


        return [
            'product' => $this->product,
            'form' => $this->renderer->output(),
            'subtitle' => 'Установка цен',
            'breadcrumbs' => [
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Менеджер цен: %s', $this->product->getName()),
            ],
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


    }
}
