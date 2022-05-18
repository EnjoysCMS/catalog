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
use InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit implements ModelInterface
{

    private Currency $currency;

    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $currencyId = $this->requestWrapper->getQueryData('id');
        if ($currencyId === null) {
            throw new InvalidArgumentException('Currency id was not transmitted');
        }

        $currency = $this->entityManager->getRepository(Currency::class)->find($currencyId);
        if ($currency === null) {
            throw new InvalidArgumentException(sprintf('Currency not found: %s', $currencyId));
        }
        $this->currency = $currency;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doProcess();
        }

        $this->renderer->setForm($form);

        return [
            'title' => 'Редактирование Валюты',
            'subtitle' => $this->currency->getName(),
            'form' => $this->renderer,
            'breadcrumbs' => [
                $this->urlGenerator->generate('admin/index') => 'Главная',
                $this->urlGenerator->generate('@a/catalog/dashboard') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/currency') => 'Список валют',
                sprintf('Редактирование валюты: %s', $this->currency->getName())
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults([
            'id' => $this->currency->getId(),
            'name' => $this->currency->getName(),
            'digital_code' => $this->currency->getDCode(),
            'right' => $this->currency->getRight(),
            'left' => $this->currency->getLeft(),
            'precision' => $this->currency->getPrecision(),
        ]);
        $form->text('id', 'ID');
        $form->text('name', 'Name');
        $form->number('digital_code', 'DCode');
        $form->text('right', 'right');
        $form->text('left', 'left');
        $form->number('precision', 'precision');
        $form->submit('edit', 'Редактировать');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doProcess()
    {
        $this->currency->setId($this->requestWrapper->getPostData('id'));
        $this->currency->setName($this->requestWrapper->getPostData('name'));
        $this->currency->setDCode((int)$this->requestWrapper->getPostData('digital_code'));
        $this->currency->setRight($this->requestWrapper->getPostData('right'));
        $this->currency->setLeft($this->requestWrapper->getPostData('left'));
        $this->currency->setPrecision((int)$this->requestWrapper->getPostData('precision'));

        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('catalog/admin/currency'));
    }

}
