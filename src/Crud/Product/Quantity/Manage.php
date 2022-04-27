<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product\Quantity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Quantity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Manage implements ModelInterface
{
    private Product $product;
    private Quantity $quantity;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->product = $this->getProduct();
        $this->quantity =  $this->product->getQuantity();
    }


    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product =  $this->em->getRepository(Product::class)->find($this->requestWrapper->getQueryData('id'));
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
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
        }

        $this->renderer->setForm($form);


        return [
            'product' => $this->product,
            'form' => $this->renderer->output(),
            'subtitle' => 'Установка количества'
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        $this->quantity->setQty($this->requestWrapper->getPostData('qty'));
        $this->quantity->setStep($this->requestWrapper->getPostData('step'));
        $this->quantity->setMin($this->requestWrapper->getPostData('min'));
        $this->em->flush();

        Redirect::http($this->urlGenerator->generate('@a/catalog/product/quantity', ['id' => $this->product->getId()]));
    }
}
