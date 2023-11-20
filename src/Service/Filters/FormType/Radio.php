<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Filters\FormType;

use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;

final class Radio
{
    private bool $multiple = false;

    public function __construct(
        private Form $form,
        private FilterInterface $filter,
        private $values,
    ) {
    }

    public function create(): void
    {
        $this->form->group($this->filter->__toString())->add([
            (new \Enjoys\Forms\Elements\Radio(
                sprintf('%s[]', $this->filter->getFormName())
            ))->addAttribute(
                AttributeFactory::create('data-is-main', ($this->filter->getParams()->main ?? false) ? 'true' : 'false')
            )
                ->fill($this->values)
        ])
            ->addAttribute(
                AttributeFactory::create('data-is-main', ($this->filter->getParams()->main ?? false) ? 'true' : 'false')
            )
            ->addClasses(['flex-column', 'filter-item']);
    }
}
