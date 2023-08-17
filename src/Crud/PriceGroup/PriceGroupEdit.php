<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\PriceGroup;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\Forms\Rules;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\PriceGroup;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PriceGroupEdit
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
     * @throws ExceptionRule
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
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/pricegroup') => 'Группы цен',
                'Редактирование группы цен'
            ],
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getAddForm(): Form
    {
        $form = new Form();
        $form->setDefaults([
            'code' => $this->priceGroup->getCode(),
            'title' => $this->priceGroup->getTitle(),
        ]);
        $form->text('code', 'Идентификатор цены (внутренний), например ROZ, OPT и тд')
            ->addRule(Rules::REQUIRED)
            ->addRule(Rules::CALLBACK, 'Такой код уже существует', function () {
                $pg = $this->em->getRepository(PriceGroup::class)->findOneBy(
                    ['code' => $this->request->getParsedBody()['code'] ?? null]
                );

                if ($pg === null) {
                    return true;
                }

                if ($pg->getId() === $this->priceGroup->getId()) {
                    return true;
                }

                return false;
            });

        $form->text('title', 'Наименование');
        $form->submit('add');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $this->priceGroup->setTitle($this->request->getParsedBody()['title'] ?? null);
        $this->priceGroup->setCode($this->request->getParsedBody()['code'] ?? null);
        $this->em->flush();
        $this->redirect->toRoute('catalog/admin/pricegroup', emit: true);
    }
}
