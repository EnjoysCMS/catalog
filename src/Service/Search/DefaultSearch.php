<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Search;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EnjoysCMS\Module\Catalog\Entities\Product;

final class DefaultSearch implements SearchInterface
{

    private \EnjoysCMS\Module\Catalog\Repositories\Product|EntityRepository $productRepository;
    private ?string $searchQuery = null;
    private array $optionKeys = [];


    public function __construct(
        private EntityManager $em
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
    }

    public function setSearchQuery(string $searchQuery): void
    {
        $this->searchQuery = $searchQuery;
    }

    public function setOptionKeys(array $optionKeys): void
    {
        $this->optionKeys = $optionKeys;
    }

    public function getResult(int $offset, int $limit): SearchResult
    {
        if ($this->searchQuery === null) {
            throw new \InvalidArgumentException('Not set searchQuery (SearchInterface::setSearchQuery())');
        }

        $result = new Paginator(
            $this
                ->getFoundProducts($this->searchQuery, $this->optionKeys)
                ->setFirstResult($offset)
                ->setMaxResults($limit)
        );

        $searchResult = new SearchResult();
        $searchResult->setCountResult($result->count());
        $searchResult->setOptionKeys($this->optionKeys);
        $searchResult->setResult($result);
        $searchResult->setSearchQuery($this->searchQuery);
        return $searchResult;
    }


    private function getFoundProducts(string $searchQuery, array $optionKeys = []): QueryBuilder
    {
        $this->optionKeys = $optionKeys;
        $qb = $this->productRepository->createQueryBuilder('p');

        $qb->select('p', 'm', 'u', 'ov', 'c')
            ->leftJoin('p.meta', 'm')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.options', 'ov', Join::WITH, 'ov.optionKey IN (:key) ')
            ->where('p.name LIKE :option')
            ->orWhere('p.description LIKE :option')
            ->orWhere('c.title LIKE :option')
            ->orWhere('ov.value LIKE :option')
            ->andWhere('p.active = true')
            ->andWhere('c.status = true OR c IS null')
            ->setParameters([
                'key' => $optionKeys,
                'option' => '%' . $searchQuery . '%'
            ])//
        ;


        return $qb;
    }


}
