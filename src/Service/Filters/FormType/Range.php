<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Filters\FormType;

use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Elements\Number;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;

final class Range
{
    public function __construct(
        private Form $form,
        private FilterInterface $filter,
        private $values
    ) {
    }

    public function create($min = null, $max = null): void
    {
        $min = $min ?? min($this->values);
        $max = $max ?? max($this->values);


        $this->form->group($this->filter->__toString())
            ->addAttribute(
                AttributeFactory::create('data-is-main', ($this->filter->getParams()->main ?? false) ? 'true' : 'false')
            )
            ->addClasses(['filter-item'])
            ->add([
                (new Number(sprintf('%s[min]', $this->filter->getFormName())))
                    ->addClass('minInput')
                    ->addAttribute(AttributeFactory::create('placeholder', sprintf('от %s', $min)))
                ,
                (new Number(sprintf('%s[max]', $this->filter->getFormName())))
                    ->addClass('maxInput')
                    ->addAttribute(AttributeFactory::create('placeholder', sprintf('до %s', $max)))
                ,
            ]);
    }
}
