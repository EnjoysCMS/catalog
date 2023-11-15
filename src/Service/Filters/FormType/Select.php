<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Filters\FormType;

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
        $select = $this->form->select(
            sprintf('%s[]', $this->filter->getFormName()),
            $this->filter->__toString()
        );
        if ($this->multiple){
            $select->setMultiple();
        }
        $select->fill($this->values);
    }

    public function multiple(): Select
    {
        $this->multiple = true;
        return $this;
    }
}
