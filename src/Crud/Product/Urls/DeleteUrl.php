<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Urls;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DeleteUrl implements ModelInterface
{
    private ObjectRepository|EntityRepository|ProductRepository $productRepository;
    protected Product $product;
    private Url $url;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->getProduct();
        $this->url = $this->product->getUrlById((int)$this->serverRequest->get('url_id'));


    }

    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->find($this->serverRequest->get('product_id'));
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

        $renderer = new Bootstrap4([], $form);

        return [
            'product' => $this->product,
            'form' => $renderer->render(),
            'subtitle' => 'Редактирование URL'
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
        $form->header(sprintf('Удалить ссылку: %s?', $this->url->getPath()));
        $form->submit('save', 'Удалить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction(): void
    {
        if($this->url->isDefault()){
            throw new \InvalidArgumentException('You cannot delete the main link');
        }
        $this->em->remove($this->url);
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('@a/catalog/product/urls', ['id' => $this->product->getId()]));
    }
}
