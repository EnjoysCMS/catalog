<?php

namespace EnjoysCMS\Module\Catalog\Filters\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Filters\Entity\FilterEntity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/filter',
    name: 'catalog/filter/add',
    methods: [
        'PUT'
    ]
)]
class AddFilters
{
    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response,
        private EntityManager $em,
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws NotSupported
     */
    public function __invoke(): ResponseInterface
    {
        $input = json_decode($this->request->getBody()->getContents());

        $response = $this->response->withHeader('content-type', 'application/json');

        /** @var Category $category */
        $category = $this->em->getRepository(Category::class)->find(
            $input->category ?? throw new \InvalidArgumentException('category id not found')
        ) ?? throw new \RuntimeException('Category not found');

        switch ($input->filterType) {
            case 'option':
                $this->addOptionFilter($input, $category);
                break;
            case 'price':
                $this->addPriceFilter($input, $category);
                break;
            case 'stock':
                $this->addStockFilter($input, $category);
                break;
        }
        $this->em->flush();

        return $response;
    }

    /**
     * @throws NotSupported
     * @throws ORMException
     */
    private function addOptionFilter($input, Category $category): void
    {
        foreach ($input->options ?? [] as $optionKeyId) {
            $hash = md5($input->filterType . $optionKeyId);

            if ($this->isFilterExist($category, $input->filterType, $hash)) {
                continue;
            }

            $filter = new FilterEntity();
            $filter->setCategory($category);
            $filter->setFilterType($input->filterType);
            $filter->setParams([
                'optionKey' => $optionKeyId
            ]);
            $filter->setOrder($input->order ?? 0);
            $filter->setHash($hash);

            $this->em->persist($filter);
        }
    }

    /**
     * @throws NotSupported
     * @throws ORMException
     */
    private function addPriceFilter($input, Category $category): void
    {
        $hash = md5($input->filterType);
        if ($this->isFilterExist($category, $input->filterType, $hash)) {
            return;
        }
        $filter = new FilterEntity();
        $filter->setCategory($category);
        $filter->setFilterType($input->filterType);
        $filter->setParams([]);
        $filter->setOrder($input->order ?? 0);
        $filter->setHash($hash);

        $this->em->persist($filter);
    }

    /**
     * @throws NotSupported
     * @throws ORMException
     */
    private function addStockFilter($input, Category $category): void
    {
        $hash = md5($input->filterType);
        if ($this->isFilterExist($category, $input->filterType, $hash)) {
            return;
        }
        $filter = new FilterEntity();
        $filter->setCategory($category);
        $filter->setFilterType($input->filterType);
        $filter->setParams([]);
        $filter->setOrder($input->order ?? 0);
        $filter->setHash($hash);

        $this->em->persist($filter);
    }

    /**
     * @throws NotSupported
     */
    public function isFilterExist(Category $category, string $filterType, string $hash): bool
    {
        return null !== $this->em->getRepository(FilterEntity::class)->findOneBy(
                ['category' => $category, 'filterType' => $filterType, 'hash' => $hash]
            );
    }

}
