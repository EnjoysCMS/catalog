<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Quantity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Quantity;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Manage
{
    private Product $product;
    private Quantity $quantity;

    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
        private readonly RendererInterface $renderer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RedirectInterface $redirect
    ) {
        $this->product = $this->em->getRepository(Product::class)->find(
            $this->request->getQueryParams()['id'] ?? null
        ) ?? throw new NoResultException();
        $this->quantity = $this->product->getQuantity();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
            $this->em->flush();
            $this->redirect->toUrl(emit: true);
        }

        $this->renderer->setForm($form);


        return [
            'product' => $this->product,
            'form' => $this->renderer->output(),
            'subtitle' => 'Установка количества',
            'breadcrumbs' => [
                $this->urlGenerator->generate('@catalog_admin') => 'Каталог',
                $this->urlGenerator->generate('catalog/admin/products') => 'Список продуктов',
                sprintf('Настройка количества: `%s`', $this->product->getName()),
            ],
        ];
    }

    private function getForm(): Form
    {
        $form = new Form();
        $form->setDefaults([
            'qty' => $this->quantity->getQty(),
            'min' => $this->quantity->getMin(),
            'step' => $this->quantity->getStep()
        ]);

        $form->header(sprintf('Единица измерения: %s', $this->product->getUnit()?->getName() ?? '-'));

        $form->text('qty', 'Количество');
        $form->header('Дополнительные параметры');
        $form->text('min', 'Минимальное кол-во для заказа');
        $form->text('step', 'Шаг');


        $form->submit('set', 'Установить');
        return $form;
    }

    private function doAction(): void
    {
        $this->quantity->setQty($this->request->getParsedBody()['qty'] ?? null);
        $this->quantity->setStep($this->request->getParsedBody()['step'] ?? null);
        $this->quantity->setMin($this->request->getParsedBody()['min'] ?? null);
    }
}
