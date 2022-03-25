<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Currency;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Currency\Currency;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Edit implements ModelInterface
{

    private Currency $currency;

    public function __construct(
        private RendererInterface $renderer,
        private EntityManager $entityManager,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $currencyId = $this->request->getQueryParams()['id'] ?? null;
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
            'form' => $this->renderer
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
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
        $this->currency->setId($this->request->getParsedBody()['id'] ?? null);
        $this->currency->setName($this->request->getParsedBody()['name'] ?? null);
        $this->currency->setDCode((int)$this->request->getParsedBody()['digital_code'] ?? null);
        $this->currency->setRight($this->request->getParsedBody()['right'] ?? null);
        $this->currency->setLeft($this->request->getParsedBody()['left'] ?? null);
        $this->currency->setPrecision((int)$this->request->getParsedBody()['precision'] ?? null);

        $this->entityManager->flush();

        Redirect::http($this->urlGenerator->generate('catalog/admin/currency'));
    }

}
