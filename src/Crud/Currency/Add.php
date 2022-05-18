<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Currency;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Add implements ModelInterface
{
    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doProcess();
        }


        $this->renderer->setForm($form);
        return [
            'title' => 'Добавление Валюты',
            'subtitle' => '',
            'form' => $this->renderer,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                '#' => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/currency') => 'Список валют',
                'Добавление валюты'
            ],
        ];
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doProcess()
    {
        $currency = new Currency();
        $currency->setId($this->requestWrapper->getPostData('id'));
        $currency->setName($this->requestWrapper->getPostData('name'));
        $currency->setDCode((int) $this->requestWrapper->getPostData('digital_code'));
        $currency->setRight($this->requestWrapper->getPostData('right'));
        $currency->setLeft($this->requestWrapper->getPostData('left'));
        $currency->setPrecision((int) $this->requestWrapper->getPostData('precision'));

        $this->entityManager->persist($currency);
        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('catalog/admin/currency'));
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->text('id', 'ID');
        $form->text('name', 'Name');
        $form->number('digital_code', 'DCode');
        $form->text('right', 'right');
        $form->text('left', 'left');
        $form->number('precision', 'precision');
        $form->submit('add', 'Добавить');
        return $form;
    }
}
