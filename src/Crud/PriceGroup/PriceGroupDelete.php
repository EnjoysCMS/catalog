<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\PriceGroup;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PriceGroupDelete implements ModelInterface
{


    private PriceGroup $priceGroup;

    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator
    ) {

        $priceGroup = $this->em->getRepository(PriceGroup::class)->find($this->requestWrapper->getQueryData('id'));
        if ($priceGroup === null){
            throw new NoResultException();
        }
        $this->priceGroup = $priceGroup;
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
        $form->header(sprintf('Удалить категорию цен: <b>%s</b>', $this->priceGroup->getTitle()));
        $form->submit('delete', 'Удалить');
        return $form;
    }

    private function doAction(): void
    {
        $this->em->remove($this->priceGroup);
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/pricegroup'));
    }
}
