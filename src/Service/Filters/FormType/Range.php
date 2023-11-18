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

    public function create(): void
    {
        $min = min($this->values);
        $max = max($this->values);


        $this->form->group($this->filter->__toString())
            ->add([
                (new Number(sprintf('%s[min]', $this->filter->getFormName())))
                    ->addClass('minInput')
                    ->addAttribute(AttributeFactory::create('placeholder', sprintf('от %s', $min)))
                    ->setMin($min)
                    ->setMax($max)
                ,
                (new Number(sprintf('%s[max]', $this->filter->getFormName())))
                    ->addClass('maxInput')
                    ->addAttribute(AttributeFactory::create('placeholder', sprintf('до %s', $max)))
                    ->setMin($min)
                    ->setMax($max)
                ,
            ]);
    }
}
