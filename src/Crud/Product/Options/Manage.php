<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Options;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Form;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use EnjoysCMS\Module\Catalog\Entities\OptionValue;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Helpers\Setting;
use EnjoysCMS\Module\Catalog\Repositories\OptionKeyRepository;
use EnjoysCMS\Module\Catalog\Repositories\OptionValueRepository;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;

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
        private ServerRequestInterface $serverRequest
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
        $product = $this->productRepository->find($this->serverRequest->get('id'));
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
            'subtitle' => '??????????????????'
        ];
    }

    private function getForm()
    {
        $options = $this->product->getOptions();

        $form = new Form(['method' => 'post']);
        $form->setDefaults($this->getDefaultsOptions($options));


        foreach ($options as $key => $option) {
            $form->group()->setAttribute('id', 'group')->add([
                (new Text(
                    'options[' . $key . '][option]'
                ))->setAttributes(
                    [
                        'class' => 'filter-option form-control',
                        'placeholder' => '??????????',
                        'grid' => 'col-md-3'
                    ]
                ),
                (new Text(
                    'options[' . $key . '][unit]'
                ))->setAttributes(
                    [
                        'class' => 'filter-unit form-control',
                        'placeholder' => '????.??????.',
                        'grid' => 'col-md-1'
                    ]
                ),
                (new Text(
                    'options[' . $key . '][value]'
                ))->setAttributes(
                    [
                        'class' => 'filter-value form-control',
                        'placeholder' => '????????????????',
                        'grid' => 'col-md-7'
                    ]
                ),
            ]);
        }
        $form->submit('submit', '??????????????????')->addClass('btn btn-outline-primary');
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
    private function doSave()
    {
//        dd($this->serverRequest->post('options', []));
        $this->product->clearOptions();
        foreach ($this->serverRequest->post('options', []) as $option) {
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
