<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Actions;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Http\ServerRequestInterface;
use Enjoys\Traits\Options;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities;
use EnjoysCMS\Module\Catalog\Repositories;
use Psr\Container\ContainerInterface;

final class Search
{

    use Options;

    private ObjectRepository|EntityRepository|Repositories\Product $productRepository;
    private string $searchQuery;
    private array $optionKeys = [];


    public function __construct(
        private ContainerInterface $container,
        private ServerRequestInterface $serverRequest,
        private EntityManager $em
    ) {
        $this->setOptions(Config::getConfig($this->container)->getAll());
        $this->searchQuery = \trim($this->serverRequest->get('q'));

        $this->validateSearchQuery();

        $this->productRepository = $this->em->getRepository(Entities\Product::class);
    }

    public function getSearchResult(array $optionKeys = []): array
    {
        $pagination = new Pagination($this->serverRequest->get('page', 1), $this->getOption('limitItems'));

        $qb = $this->getFoundProducts($optionKeys);
        $qb->setFirstResult($pagination->getOffset())->setMaxResults($pagination->getLimitItems());
        $result = new Paginator($qb);
        $pagination->setTotalItems($result->count());

        return [
            'searchQuery' => $this->searchQuery,
            'countResult' => $result->count(),
            'optionKeys' => $this->optionKeys,
            'pagination' => $pagination,
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
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.options', 'ov', Expr\Join::WITH, 'ov.optionKey IN (:key) ')
            ->where('p.name LIKE :option')
            ->orWhere('p.description LIKE :option')
            ->orWhere('c.title LIKE :option')
            ->orWhere('ov.value LIKE :option')
            ->andWhere('p.active = true')
            ->andWhere('c.status = true')
            ->setParameters([
                'key' => $optionKeys,
                'option' => '%' . $this->searchQuery . '%'
            ])//
        ;


        return $qb;
    }

    private function validateSearchQuery()
    {
        if (mb_strlen($this->searchQuery) < $this->getOption('minSearchChars', 3)) {
            throw new \InvalidArgumentException(sprintf('Слишком короткое слово для поиска (нужно минимум %s символа)', $this->getOption('minSearchChars', 3)));
        }
    }


}
