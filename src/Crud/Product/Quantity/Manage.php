<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Quantity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use EnjoysCMS\Module\Catalog\Entities\Product;
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
            'subtitle' => 'Установка количества'
        ];
    }

    private function getForm(): Form
    {



        $form = new Form();
        $form->setDefaults([
            'qty' => $this->product->getQuantity()->getQty(),
            'unit' => $this->product->getQuantity()->getUnit()?->getName(),
        ]);

        $form->text('unit', 'Единица измерения');
        $form->text('qty', 'Количество');


        $form->submit('set', 'Установить');
        return $form;
    }

    private function doAction(): void
    {
        $unitValue = $this->requestWrapper->getPostData('unit');
        $unit = $this->em->getRepository(ProductUnit::class)->findOneBy(['name' => $unitValue]);
        if ($unit === null) {
            $unit = new ProductUnit();
            $unit->setName($unitValue);
            $this->em->persist($unit);
            $this->em->flush();
        }

        $quantity = $this->product->getQuantity();
        $quantity->setQty($this->requestWrapper->getPostData('qty'));
        $quantity->setUnit($unit);
        $this->product->setQuantity($quantity);

        $this->em->flush();

        Redirect::http($this->urlGenerator->generate('@a/catalog/product/quantity', ['id' => $this->product->getId()]));
    }
}
