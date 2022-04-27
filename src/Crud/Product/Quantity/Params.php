<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Quantity;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Interfaces\RendererInterface;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Admin\Core\ModelInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Quantity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Params implements ModelInterface
{
    private Quantity $quantity;
    private int $productId;
    private Product $product;

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
        $this->productId =  $this->product->getId();
    }

    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->em->getRepository(Product::class)->find($this->requestWrapper->getQueryData('id'));
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
    }

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
            'subtitle' => 'Установка параметров для количества'
        ];
    }

    private function getForm(): Form
    {

        $form = new Form();
        $form->setDefaults([
            'min' => $this->quantity->getMin(),
            'step' => $this->quantity->getStep()
        ]);

        $form->text('min', 'Минимальное кол-во для заказа');
        $form->text('step', 'Шаг');

        $form->submit('set', 'Установить');
        return $form;
    }

    private function doAction(): void
    {
        $this->quantity->setStep($this->requestWrapper->getPostData('step'));
        $this->quantity->setUnit($this->requestWrapper->getPostData('min'));
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('@a/catalog/product/quantity', ['id' => $this->product->getId()]));
    }
}
