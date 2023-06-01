<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Filter;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final class FilterCrud
{
    private \stdClass $input;

    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response
    ) {
        $this->input = json_decode($this->request->getBody()->getContents());
    }


    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    #[Route(
        path: 'admin/catalog/filter',
        name: 'catalog/admin/filter/update',
        methods: [
            'PATCH'
        ]
    )]
    public function updateFilter(EntityManager $em): ResponseInterface
    {
        $response = $this->response->withHeader('content-type', 'application/json');
        /** @var Category $category */
        $filter = $em->getRepository(Filter::class)->find(
            $this->input->filterId ?? throw new \InvalidArgumentException('Filter id not found')
        ) ?? throw new \RuntimeException('Filter not found');
        $filter->setType($this->input->type ?? 'checkbox');
        $filter->setOrder((int)($this->input->order ?? 0));
        $em->flush();
        return $response;
    }
}
