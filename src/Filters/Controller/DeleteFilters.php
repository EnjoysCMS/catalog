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
    name: 'catalog/filter/delete',
    methods: [
        'DELETE'
    ]
)]
class DeleteFilters
{
    private \stdClass $input;

    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response,
        private EntityManager $em,
    ) {
        $this->input = json_decode($this->request->getBody()->getContents());
        $this->response = $this->response->withHeader('content-type', 'application/json');
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function __invoke(): ResponseInterface
    {
        /** @var Category $category */
        $filter = $this->em->getRepository(FilterEntity::class)->find(
            $this->input->filterId ?? throw new \InvalidArgumentException('Filter id not found')
        ) ?? throw new \RuntimeException('Filter not found');

        $this->em->remove($filter);
        $this->em->flush();
        return $this->response;
    }
}
