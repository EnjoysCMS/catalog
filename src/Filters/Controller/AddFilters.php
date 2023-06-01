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
    private \stdClass $input;

    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response,
        private EntityManager $em,
    ) {
        $this->input = json_decode($this->request->getBody()->getContents());
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws NotSupported
     */
    public function __invoke(): ResponseInterface
    {

        $response = $this->response->withHeader('content-type', 'application/json');

        /** @var Category $category */
        $category = $this->em->getRepository(Category::class)->find(
            $this->input->category ?? throw new \InvalidArgumentException('category id not found')
        ) ?? throw new \RuntimeException('Category not found');

        switch ($this->input->filterType) {
            case 'option':
                $this->addOptionFilter($category);
                break;
            case 'price':
                $this->addPriceFilter($category);
                break;
            case 'stock':
                $this->addStockFilter($category);
                break;
        }
        $this->em->flush();

        return $response;
    }

    /**
     * @throws NotSupported
     * @throws ORMException
     */
    private function addOptionFilter(Category $category): void
    {
        foreach ($this->input->options ?? [] as $optionKeyId) {
            $hash = md5($this->input->filterType . $optionKeyId);

            if ($this->isFilterExist($category, $this->input->filterType, $hash)) {
                continue;
            }

            $filter = new FilterEntity();
            $filter->setCategory($category);
            $filter->setFilterType($this->input->filterType);
            $filter->setParams([
                'optionKey' => $optionKeyId
            ]);
            $filter->setOrder($this->input->order ?? 0);
            $filter->setHash($hash);

            $this->em->persist($filter);
        }
    }

    /**
     * @throws NotSupported
     * @throws ORMException
     */
    private function addPriceFilter(Category $category): void
    {
        $hash = md5($this->input->filterType);
        if ($this->isFilterExist($category, $this->input->filterType, $hash)) {
            return;
        }
        $filter = new FilterEntity();
        $filter->setCategory($category);
        $filter->setFilterType($this->input->filterType);
        $filter->setParams([]);
        $filter->setOrder($this->input->order ?? 0);
        $filter->setHash($hash);

        $this->em->persist($filter);
    }

    /**
     * @throws NotSupported
     * @throws ORMException
     */
    private function addStockFilter(Category $category): void
    {
        $hash = md5($this->input->filterType);
        if ($this->isFilterExist($category, $this->input->filterType, $hash)) {
            return;
        }
        $filter = new FilterEntity();
        $filter->setCategory($category);
        $filter->setFilterType($this->input->filterType);
        $filter->setParams([]);
        $filter->setOrder($this->input->order ?? 0);
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
