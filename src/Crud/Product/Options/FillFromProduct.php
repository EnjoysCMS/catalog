<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Options;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FillFromProduct
{
    private EntityRepository|ProductRepository $productRepository;

    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private UrlGeneratorInterface $urlGenerator,
        private RedirectInterface $redirect,
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
    }

    /**
     * @throws NoResultException
     * @throws ORMException
     */
    public function __invoke(): void
    {
        /** @var Product $product */
        $product = $this->productRepository->find(
            $this->request->getParsedBody()['id'] ?? 0
        ) ?? throw new NoResultException();


        /** @var Product $from */
        $from = $this->productRepository->find($this->request->getParsedBody()['fillFromProduct'] ?? 0);
        if ($from === null) {
            $this->redirectToProductOptionsPage($product);
        }

        foreach ($from->getOptionsCollection() as $item) {
            $product->addOption($item);
        }

        $this->em->persist($product);
        $this->em->flush();

        $this->redirectToProductOptionsPage($product);
    }

    private function redirectToProductOptionsPage(Product $product): void
    {
        $this->redirect->http(
            $this->urlGenerator->generate('@a/catalog/product/options', ['id' => $product->getId()]),
            emit: true
        );
    }
}
