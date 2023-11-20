<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Filters\FormType;

use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;

final class Select
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
        $select = new \Enjoys\Forms\Elements\Select(
            sprintf('%s[]', $this->filter->getFormName())
        );

        $select->addAttribute(
            AttributeFactory::create('data-is-main', ($this->filter->getParams()->main ?? false) ? 'true' : 'false')
        );
        if ($this->multiple) {
            $select->setMultiple();
        }
        $select->fill($this->values);

        $this->form->group($this->filter->__toString())->add([$select])
            ->addAttribute(
                AttributeFactory::create('data-is-main', ($this->filter->getParams()->main ?? false) ? 'true' : 'false')
            )
            ->addClasses(['flex-column', 'filter-item']);
    }

    public function multiple(): Select
    {
        $this->multiple = true;
        return $this;
    }
}
