<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Admin\Product\Options;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Elements\Text;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Api\ProductOptions;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\OptionKey;
use EnjoysCMS\Module\Catalog\Entity\OptionValue;
use EnjoysCMS\Module\Catalog\Entity\Product;
use EnjoysCMS\Module\Catalog\Repository\OptionKeyRepository;
use EnjoysCMS\Module\Catalog\Repository\OptionValueRepository;
use EnjoysCMS\Module\Catalog\Repository\Product as ProductRepository;
use JMS\Serializer\SerializerBuilder;
use Psr\Http\Message\ServerRequestInterface;

final class ManageOptions
{
    private EntityRepository|ProductRepository $productRepository;
    private Product $product;
    private EntityRepository|OptionKeyRepository $keyRepository;
    private EntityRepository|OptionValueRepository $valueRepository;

    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly Config $config,
        private readonly ProductOptions $productOptionsController,
    ) {
        $this->keyRepository = $this->em->getRepository(OptionKey::class);
        $this->valueRepository = $this->em->getRepository(OptionValue::class);
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->productRepository->find(
            $this->request->getQueryParams()['product_id'] ?? null
        ) ?? throw new NoResultException();
    }


    public function getForm(): Form
    {
        $serializer = SerializerBuilder::create()->build();

//        $options = json_decode((string)$this->productOptionsController->getProductOptionsKeysByCategory($this->product->getCategory())->getBody());
        //  dd($this->product->getOptions());
//        $options = $this->product->getOptions();
        $options = $serializer->deserialize(
            (string)$this->productOptionsController->getProductOptionsKeysByCategory(
                $this->product->getCategory()
            )->getBody(),
            'array<EnjoysCMS\Module\Catalog\Entity\OptionKey>',
            'json'
        );
//        dd($options);
        $form = new Form();
        $form->setDefaults($this->getDefaultsOptions($this->product->getOptions()));


        foreach ($options as $option) {
            $key = $option;
            $form->group()->setAttribute(AttributeFactory::create('id', 'group'))->add([
                (new Text(
                    'options[' . $key->getId() . '][option]'
                ))->setAttributes(
                    AttributeFactory::createFromArray([
                        'class' => 'filter-option form-control',
                        'placeholder' => 'Опция',
                        'grid' => 'col-md-3',
                        'value' => $key->getName()
                    ])
                ),
                (new Text(
                    'options[' . $key->getId() . '][unit]'
                ))->setAttributes(
                    AttributeFactory::createFromArray([
                        'class' => 'filter-unit form-control',
                        'placeholder' => 'ед.изм.',
                        'grid' => 'col-md-1',
                        'value' => $key->getUnit()
                    ])
                ),
                (new Text(
                    'options[' . $key->getId() . '][value]'
                ))->setAttributes(
                    AttributeFactory::createFromArray([
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

        foreach ($options as $option) {
            $defaults['options'][$option['key']->getId()]['value'] = implode(
                $this->config->getDelimiterOptions(),
                array_map(function ($item) {
                    return $item->getValue();
                }, $option['values'])
            );
        }
        return $defaults;
    }


    /**
     * @throws ORMException
     */
    public function doSave(): void
    {
//        dd($this->serverRequest->post('options', []));
        $this->product->clearOptions();
        foreach (($this->request->getParsedBody()['options'] ?? []) as $option) {
            if (empty($option['option']) || empty($option['value'])) {
                continue;
            }
            $optionKey = $this->keyRepository->getOptionKey($option['option'], $option['unit']);
            foreach (explode($this->config->getDelimiterOptions(), $option['value']) as $value) {
                $optionValue = $this->valueRepository->getOptionValue($value, $optionKey);
                $this->em->persist($optionValue);
                $this->product->addOption($optionValue);
            }
        }
        $this->em->flush();
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

}
