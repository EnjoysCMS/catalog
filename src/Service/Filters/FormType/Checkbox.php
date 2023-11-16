<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Filters\FormType;

use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Form;
use EnjoysCMS\Module\Catalog\Service\Filters\FilterInterface;

final class Checkbox
{
    public function __construct(
        private Form $form,
        private FilterInterface $filter,
        private $values
    ) {
    }

    public function create(): void
    {
        $this->form->checkbox(
            sprintf('%s[]', $this->filter->getFormName()),
            $this->filter->__toString()
        )
            ->addAttribute(AttributeFactory::create('class', 'checkbox-option-filter'), Form::ATTRIBUTES_LABEL)
            ->fill($this->values);
    }
}
