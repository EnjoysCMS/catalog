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
     * @throws NotSupported
     * @throws ORMException
     */
    #[Route(
        path: 'admin/catalog/filter',
        name: 'catalog/admin/filter/add',
        methods: [
            'PUT'
        ]
    )]
    public function addFilter(EntityManager $em): ResponseInterface
    {
        $response = $this->response->withHeader('content-type', 'application/json');

        /** @var Category $category */
        $category = $em->getRepository(Category::class)->find(
            $this->input->category ?? throw new \InvalidArgumentException('category id not found')
        ) ?? throw new \RuntimeException('Category not found');

        foreach (
            $this->input->optionKeys ?? throw new \InvalidArgumentException(
            'OptionKeys not set'
        ) as $optionKeyId
        ) {
            /** @var OptionKey $optionKey */
            $optionKey = $em->getRepository(OptionKey::class)->find($optionKeyId)
                ?? throw new \RuntimeException('OptionKey not found');

            if (null !== $filter = $em->getRepository(Filter::class)->findOneBy(
                    ['category' => $category, 'optionKey' => $optionKey]
                )) {
                continue;
            }
            $filter = new Filter();
            $filter->setCategory($category);
            $filter->setType($this->input->type ?? 'checkbox');
            $filter->setOptionKey($optionKey);
            $filter->setOrder($this->input->order ?? 0);

            $em->persist($filter);
        }
        $em->flush();
        $response->getBody()->write('');
        return $response;
    }

    /**
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    #[Route(
        path: 'admin/catalog/filter',
        name: 'catalog/admin/filter/delete',
        methods: [
            'DELETE'
        ]
    )]
    public function deleteFilter(EntityManager $em): ResponseInterface
    {
        $response = $this->response->withHeader('content-type', 'application/json');
        /** @var Category $category */
        $filter = $em->getRepository(Filter::class)->find(
            $this->input->filterId ?? throw new \InvalidArgumentException('Filter id not found')
        ) ?? throw new \RuntimeException('Filter not found');

        $em->remove($filter);
        $em->flush();
        return $response;
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

        if ($this->input->type ?? null) {
            $filter->setType($this->input->type);
        }
        if ($this->input->order ?? null) {
            $filter->setOrder((int)$this->input->order);
        }
        $em->flush();
        return $response;
    }
}
