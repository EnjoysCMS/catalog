<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\PriceGroup;


use App\Module\Admin\Core\ModelInterface;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;

final class PriceGroupAdd implements ModelInterface
{

    public function __construct(private ServerRequestInterface $serverRequest, private RendererInterface $renderer)
    {
    }

    public function getContext(): array
    {
        $form = $this->getAddForm();
        if($form->isSubmitted()){
            $this->doAction();
        }
        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer->render()
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getAddForm(): Form
    {
        $form = new Form(['method' => 'post']);
        $form->text('code', 'Идентификатор цены (внутренний), например ROZ, OPT и тд')
            ->addRule(Rules::REQUIRED);

        $form->text('title', 'Наименование');
        $form->submit('add');
        return $form;
    }
}