<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\PriceGroup;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PriceGroupAdd implements ModelInterface
{

    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getContext(): array
    {
        $form = $this->getAddForm();
        if ($form->isSubmitted()) {
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
            ->addRule(Rules::REQUIRED)
            ->addRule(Rules::CALLBACK, 'Такой код уже существует', function () {
                return is_null(
                    $this->em->getRepository(PriceGroup::class)->findOneBy(
                        ['code' => $this->serverRequest->post('code')]
                    )
                );
            });

        $form->text('title', 'Наименование');
        $form->submit('add');
        return $form;
    }

    private function doAction()
    {
        $priceGroup = new PriceGroup();
        $priceGroup->setTitle($this->serverRequest->post('title'));
        $priceGroup->setCode($this->serverRequest->post('code'));
        $this->em->persist($priceGroup);
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/pricegroup'));
    }
}