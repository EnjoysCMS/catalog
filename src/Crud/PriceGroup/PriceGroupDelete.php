<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\PriceGroup;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PriceGroupDelete
{


    private PriceGroup $priceGroup;

    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RedirectInterface $redirect,
    ) {
        $this->priceGroup = $this->em->getRepository(PriceGroup::class)->find(
            $this->request->getQueryParams()['id'] ?? null
        ) ?? throw new NoResultException();
    }

    /**
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
                $this->urlGenerator->generate('@catalog_admin') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/pricegroup') => 'Группы цен',
                'Удаление группы цен'
            ],
        ];
    }

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
        $this->redirect->toRoute('catalog/admin/pricegroup', emit: true);
    }
}
