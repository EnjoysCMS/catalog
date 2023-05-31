<?php

namespace EnjoysCMS\Module\Catalog\Filters\Controller;

use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use EnjoysCMS\Module\Catalog\Filters\Entity\FilterEntity;
use EnjoysCMS\Module\Catalog\Filters\FilterFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/get-filters',
    name: 'catalog/get-filters',
    options: [
        'comment' => 'API: получение списка фильтров категорий '
    ],
    methods: [
        'GET'
    ]
)]
class GetFilters
{
    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response,
        private FilterFactory $filterFactory,
    ) {
        $this->response = $this->response->withHeader('content-type', 'application/json');
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws NotSupported
     */
    public function __invoke(EntityManager $em): ResponseInterface
    {
        /** @var FilterEntity[] $filters */
        $filters = $em->getRepository(FilterEntity::class)->findBy([
            'category' => $this->request->getQueryParams()['category'] ?? throw new \InvalidArgumentException(
                    sprintf('Category id not sent')
                )
        ], ['order' => 'asc']);

        $this->response->getBody()->write(
            json_encode($this->normalizeData($filters))
        );
        return $this->response;
    }

    /**
     * @param FilterEntity[] $filters
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function normalizeData(array $filters): array
    {
        $result = [];
        foreach ($filters as $filterMetaData) {
            $filter = $this->filterFactory->create($filterMetaData->getFilterType(), $filterMetaData->getParams());
            if ($filter === null){
                continue;
            }
            $result[] = [
                'id' => $filterMetaData->getId(),
                'order' => $filterMetaData->getOrder(),
                'filterType' => $filterMetaData->getFilterType(),
                'filterParams' => $filterMetaData->getParams()->getParams(),
                'filterTitle' => $filter->__toString(),
            ];
        }
        return $result;
    }
}
