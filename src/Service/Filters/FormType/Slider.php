<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Filters\FormType;

use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Elements\Number;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;

final class Slider
{
    public function __construct(
        private Form $form,
        private FilterInterface $filter,
        private $values
    ) {
    }

    public function create(): void
    {
        $min = min($this->values);
        $max = max($this->values);


        $this->form->group($this->filter->__toString())
            ->addClasses(['slider-group', 'filter-item'])
            ->addAttribute(
                AttributeFactory::create('data-is-main', ($this->filter->getParams()->main ?? false) ? 'true' : 'false')
            )
            ->add([
                (new Number(sprintf('%s[min]', $this->filter->getFormName())))
                    ->addClass('minInput')
                    ->setMin($min)
                    ->setMax($max)
                    ->addAttribute(AttributeFactory::create('value', $min))
                ,
                (new Number(sprintf('%s[max]', $this->filter->getFormName())))
                    ->addClass('maxInput')
                    ->setMin($min)
                    ->setMax($max)
                    ->addAttribute(AttributeFactory::create('value', $max))
                ,
            ]);
    }
}
