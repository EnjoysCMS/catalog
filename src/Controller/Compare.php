<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Controller;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Enjoys\Cookie\Cookie;
use Enjoys\Cookie\Exception;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entity\CompareList;
use EnjoysCMS\Module\Catalog\Service\Compare\GoodsComparator;
use EnjoysCMS\Module\Catalog\Service\Compare\Result\LineMatrix;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


final class Compare extends AbstractController
{

    /**
     * @throws Exception
     */
    #[Route('catalog/compare/add', 'catalog_compare_add', priority: 3)]
    public function add(Cookie $cookie, EntityManagerInterface $em): ResponseInterface
    {
        $parsedBody = json_decode($this->request->getBody()->getContents());

        $product = $this->getProduct($em, $parsedBody->productId ?? null);

        if ($product === null) {
            return $this->json(['error' => true, 'message' => 'Invalid product id'], 400);
        }

        $compareList = $this->getCompareList();

        if ($compareList === null) {
            $compareList = new CompareList();
            $compareList->setId(Uuid::uuid4()->toString());
            $em->persist($compareList);
        }

        $compareList->setCreatedAt(new \DateTimeImmutable());
        $compareList->setGoodsIds(\array_unique(\array_merge($compareList->getGoodsIds(), [$product->getId()])));

        $cookie->set(
            'compare-list-id',
            $compareList->getId(),
            new \DateTimeImmutable('+7 days')
        );

        $em->flush();

        return $this->json(sprintf('%s добавлен в сравнение', $product->getName()));
    }


    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DependencyException
     * @throws NotFoundException
     * @throws NotSupported
     */
    #[Route('catalog/compare', 'catalog_compare', priority: 3)]
    public function compare(GoodsComparator $goodsComparator, Config $config): ResponseInterface
    {
        $this->breadcrumbs->add('catalog/index', 'Каталог')->add(title: 'Сравнение товаров');

        /** @var \EnjoysCMS\Module\Catalog\Repository\Product $repo */
        $repo = $this->container->get(EntityManager::class)->getRepository(
            \EnjoysCMS\Module\Catalog\Entity\Product::class
        );


        $compareList = $this->getCompareList();

        /** @var \EnjoysCMS\Module\Catalog\Entity\Product[] $products */
        $products = $repo->findByIds($compareList?->getGoodsIds() ?? []);

        $goodsComparator->addProducts($products);


        return $this->response($this->twig->render('@m/catalog/compare.twig', [
            'breadcrumbs' => $this->breadcrumbs,
            'comparator' => $goodsComparator,
            'comparisonGoods' => (new LineMatrix($goodsComparator))->setRemoveRepeat(
                (bool)$this->request->getQueryParams()['remove_repeat_values'] ?? false
            ),
            'config' => $config
        ]));
    }


    private function getCompareList(): ?CompareList
    {
        $compareListId = $this->request->getCookieParams()['compare-list-id'] ?? null;
        $em = $this->container->get(EntityManagerInterface::class);
        $repository = $em->getRepository(CompareList::class);
        return $repository->findOneBy([
            'id' => $compareListId
        ]);
    }

    private function getProduct(EntityManagerInterface $em, ?string $id): ?\EnjoysCMS\Module\Catalog\Entity\Product
    {
        if ($id === null) {
            return null;
        }
        return $em->getRepository(\EnjoysCMS\Module\Catalog\Entity\Product::class)->find($id);
    }

}
