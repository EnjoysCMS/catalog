<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\PriceGroup;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PriceGroupDelete implements ModelInterface
{


    private PriceGroup $priceGroup;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator
    ) {

        $priceGroup = $this->em->getRepository(PriceGroup::class)->find($this->request->getQueryParams()['id'] ?? null);
        if ($priceGroup === null){
            throw new NoResultException();
        }
        $this->priceGroup = $priceGroup;
    }

    /**
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getContext(): array
    {
        $form = $this->getAddForm();
        if ($form->isSubmitted()) {
            $this->doAction();
        }
        $this->renderer->setForm($form);
        return [
            'form' => $this->renderer->output(),
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/pricegroup') =>'Группы цен',
                'Удаление группы цен'
            ],
        ];
    }

    /**
     * @return Form
     */
    private function getAddForm(): Form
    {
        $form = new Form();
        $form->header(sprintf('Удалить категорию цен: <b>%s</b>', $this->priceGroup->getTitle()));
        $form->submit('delete', 'Удалить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $this->em->remove($this->priceGroup);
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/pricegroup'));
    }
}
