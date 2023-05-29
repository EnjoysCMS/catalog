<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller\Admin\Api;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Filter;
use EnjoysCMS\Module\Catalog\Entities\OptionKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final class FilterCrud
{
    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response
    ) {
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
        $input = json_decode($this->request->getBody()->getContents());

        /** @var Category $category */
        $category = $em->getRepository(Category::class)->find(
            $input->category ?? throw new \InvalidArgumentException('category id not found')
        ) ?? throw new \RuntimeException('Category not found');

        foreach ($input->optionKeys ?? throw new \InvalidArgumentException('OptionKeys not set') as $optionKeyId) {
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
            $filter->setType($input->type ?? 'checkbox');
            $filter->setOptionKey($optionKey);
            $filter->setOrder($input->order ?? 0);

            $em->persist($filter);
        }
        $em->flush();
        $response->getBody()->write('');
        return $response;
    }

    #[Route(
        path: 'admin/catalog/filter',
        name: 'catalog/admin/filter/delete',
        methods: [
            'DELETE'
        ]
    )]
    public function deleteFilter()
    {
    }

    public function get()
    {

    }
}
