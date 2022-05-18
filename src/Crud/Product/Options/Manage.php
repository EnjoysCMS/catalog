<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Options;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Form;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Helpers\Setting;
use EnjoysCMS\Module\Catalog\Repositories\OptionKeyRepository;
use EnjoysCMS\Module\Catalog\Repositories\OptionValueRepository;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Manage implements ModelInterface
{
    private ObjectRepository|EntityRepository|ProductRepository $productRepository;
    protected Product $product;
    private ObjectRepository|EntityRepository|OptionKeyRepository $keyRepository;
    private ObjectRepository|EntityRepository|OptionValueRepository $valueRepository;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->keyRepository = $this->em->getRepository(OptionKey::class);
        $this->valueRepository = $this->em->getRepository(OptionValue::class);
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
            $this->doSave();
        }

        return [
            'product' => $this->product,
            'form' => $form,
            'delimiterOptions' =>Setting::get('delimiterOptions', '|'),
            'subtitle' => 'Параметры',
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Характеристики: %s', $this->product->getName()),
            ],
        ];
    }

    private function getForm(): Form
    {
        $options = $this->product->getOptions();

        $form = new Form();
        $form->setDefaults($this->getDefaultsOptions($options));


        foreach ($options as $key => $option) {
            $form->group()->setAttribute(AttributeFactory::create('id', 'group'))->add([
                (new Text(
                    'options[' . $key . '][option]'
                ))->setAttributes(
                   AttributeFactory::createFromArray( [
                       'class' => 'filter-option form-control',
                       'placeholder' => 'Опция',
                       'grid' => 'col-md-3'
                   ])
                ),
                (new Text(
                    'options[' . $key . '][unit]'
                ))->setAttributes(
                    AttributeFactory::createFromArray([
                        'class' => 'filter-unit form-control',
                        'placeholder' => 'ед.изм.',
                        'grid' => 'col-md-1'
                    ])
                ),
                (new Text(
                    'options[' . $key . '][value]'
                ))->setAttributes(
                   AttributeFactory::createFromArray( [
                       'class' => 'filter-value form-control',
                       'placeholder' => 'Значение',
                       'grid' => 'col-md-7'
                   ])
                ),
            ]);
        }
        $form->submit('submit', 'Сохранить')->addClass('btn btn-outline-primary');
        return $form;
    }

    private function getDefaultsOptions($options): array
    {
        $defaults = [];

        foreach ($options as $key => $option) {
            $defaults['options'][$key]['option'] = $option['key']->getName();
            $defaults['options'][$key]['unit'] = $option['key']->getUnit();
            $defaults['options'][$key]['value'] = implode(
                Setting::get('delimiterOptions', '|'),
                array_map(function ($item) {
                    return $item->getValue();
                }, $option['values'])
            );
        }
        return $defaults;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doSave(): void
    {
//        dd($this->serverRequest->post('options', []));
        $this->product->clearOptions();
        foreach ($this->requestWrapper->getPostData('options', []) as $option) {
            if (empty($option['option']) || empty($option['value'])) {
                continue;
            }
            $optionKey = $this->keyRepository->getOptionKey($option['option'], $option['unit']);
            foreach (explode(Setting::get('delimiterOptions', '|'), $option['value']) as $value) {
                $optionValue = $this->valueRepository->getOptionValue($value, $optionKey);
                $this->em->persist($optionValue);
                $this->product->addOption($optionValue);
            }
        }
        $this->em->flush();
        Redirect::http();
    }

}
