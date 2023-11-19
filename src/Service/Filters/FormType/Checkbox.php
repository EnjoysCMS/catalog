<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Filters\FormType;

use Enjoys\Forms\AttributeFactory;
use Enjoys\Forms\Elements\Text;
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
        $group = [];

        if (count($this->values) > 8) {
            $group[] = (new Text(uniqid('searchparams')))
                ->addClass('search_param')
                ->addAttribute(AttributeFactory::create('placeholder', 'Поиск'));
        }
        $group[] = (new \Enjoys\Forms\Elements\Checkbox(
            sprintf('%s[]', $this->filter->getFormName()),
        ))->addClass(uniqid())
            ->fill($this->values);

        $this->form->group($this->filter->__toString())->add($group)
            ->addClasses(['flex-column', 'checkbox-option-filter']);
    }
}
