<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Service\Search;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EnjoysCMS\Core\Components\Pagination\Pagination;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Psr\Http\Message\ServerRequestInterface;

final class DefaultSearch implements SearchInterface
{


    private \EnjoysCMS\Module\Catalog\Repositories\Product|EntityRepository $productRepository;
    private string $searchQuery;
    private array $optionKeys = [];


    public function __construct(
        private ServerRequestInterface $request,
        private EntityManager $em,
        private Config $config
    ) {
        $this->searchQuery = \trim($this->request->getQueryParams()['q'] ?? $this->request->getParsedBody()['q'] ?? '');
        $this->productRepository = $this->em->getRepository(Product::class);
    }

    /**
     * @throws NotFoundException
     */
    public function getResult(): SearchResult
    {
        $this->validateSearchQuery();

        $pagination = new Pagination(
            $this->request->getQueryParams()['page'] ?? 1, $this->config->get('limitItems', 30)
        );

        $qb = $this->getFoundProducts($this->optionKeys);
        $qb->setFirstResult($pagination->getOffset())->setMaxResults($pagination->getLimitItems());
        $result = new Paginator($qb);
        $pagination->setTotalItems($result->count());

        $searchResult = new SearchResult();
        $searchResult->setCountResult($result->count());
        $searchResult->setOptionKeys($this->optionKeys);
        $searchResult->setResult($result);
        $searchResult->setSearchQuery($this->searchQuery);
        return $searchResult;
    }


    private function getFoundProducts(array $optionKeys = []): QueryBuilder
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
                'option' => '%' . $this->searchQuery . '%'
            ])//
        ;


        return $qb;
    }

    private function validateSearchQuery(): void
    {
        if (mb_strlen($this->searchQuery) < $this->config->get('minSearchChars', 3)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Слишком короткое слово для поиска (нужно минимум %s символа)',
                    $this->config->get('minSearchChars', 3)
                )
            );
        }
    }


    public function setOptionKeys(array $optionKeys): void
    {
        $this->optionKeys = $optionKeys;
    }

}
