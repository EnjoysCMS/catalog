<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Entities;
use EnjoysCMS\Module\Catalog\Repositories;

final class Search
{

    private ObjectRepository|EntityRepository|Repositories\Product $productRepository;
    private string $searchQuery;
    private array $optionKeys = [];


    public function __construct(private ServerRequestInterface $serverRequest, private EntityManager $em)
    {
        $this->productRepository = $this->em->getRepository(Entities\Product::class);
        $this->searchQuery = \trim($this->serverRequest->get('q'));

    }

    public function getSearchResult(array $optionKeys = []): array
    {
        $result = $this->getFoundProducts($optionKeys);
        return [
            'searchQuery' => $this->searchQuery,
            'countResult' => count($result),
            'optionKeys' => $this->optionKeys,
            'result' => $result
        ];
    }


    private function getFoundProducts(array $optionKeys = [])
    {
        $this->optionKeys = $optionKeys;
        $qb = $this->productRepository->createQueryBuilder('p');

        $qb->select('p', 'm', 'u', 'ov')
            ->leftJoin('p.meta', 'm')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('p.options', 'ov', Expr\Join::WITH, 'ov.optionKey IN (:key) ')
            ->where('p.name LIKE :option')
            ->orWhere('ov.value LIKE :option')
            ->setParameters([
                'key' => $optionKeys,
                'option' => '%' . $this->searchQuery . '%'
            ])//
        ;


        return $qb->getQuery()->getResult();
    }

}