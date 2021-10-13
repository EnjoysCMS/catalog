<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Product\Options;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FillFromProduct
{
    private ObjectRepository|EntityRepository|ProductRepository $productRepository;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->entityManager->getRepository(Product::class);
    }

    /**
     * @throws NoResultException
     */
    public function __invoke()
    {
        /** @var Product $product */
        $product = $this->productRepository->find($this->serverRequest->post('id', 0));
        if ($product === null) {
            throw new NoResultException();
        }

        /** @var Product $from */
        $from = $this->productRepository->find($this->serverRequest->post('fillFromProduct', 0));
        if ($from === null) {
            $this->redirectToProductOptionsPage($product);
        }

        foreach ($from->getOptionsCollection() as $item) {
            $product->addOption($item);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->redirectToProductOptionsPage($product);
    }

    private function redirectToProductOptionsPage(Product $product)
    {
        Redirect::http($this->urlGenerator->generate('@a/catalog/product/options', ['id' => $product->getId()]));
    }
}