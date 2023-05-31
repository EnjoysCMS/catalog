<?php

namespace EnjoysCMS\Module\Catalog\Filters\Controller;

use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Filters\Entity\FilterEntity;
use HttpSoft\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: 'admin/catalog/filter',
    name: 'catalog/filter/add',
    methods: [
        'POST'
    ]
)]
class AddFilters
{
    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response
    ) {
    }

    public function __invoke(EntityManager $em): ResponseInterface
    {
        $em->getConfiguration()->addCustomStringFunction(
            'JSON_CONTAINS',
            \DoctrineExtensions\Query\Mysql\JsonContains::class
        );
        $input = json_decode($this->request->getBody()->getContents());

        $response = $this->response->withHeader('content-type', 'application/json');

        /** @var Category $category */
        $category = $em->getRepository(Category::class)->find(
            $input->category ?? throw new \InvalidArgumentException('category id not found')
        ) ?? throw new \RuntimeException('Category not found');

        foreach ($input->options ?? [] as $optionKeyId) {
            $filterParams = [
                'optionKey' => $optionKeyId
            ];

            $test =
                $em->getRepository(FilterEntity::class)->createQueryBuilder('f')
                    ->select('f')
                    ->where('f.category = :category')
                    ->andWhere('f.filterType = :filterType')
                    ->andWhere('JSON_CONTAINS(f.params, :params, :jsonPath) = 1')
                    ->setParameters([
                        'jsonPath' => '$.parent',
                        'category' => $category,
                        'filterType' => 'option',
                        'params' => $filterParams
                    ])
                    ->getQuery()->getOneOrNullResult();

            $response->getBody()->write(json_encode($test));
            $emitter = new SapiEmitter();
            $emitter->emit($response);
            exit;
            return $response;

            if (null !== $filter = $em->getRepository(FilterEntity::class)->findOneBy(
                    ['category' => $category, 'filterType' => 'option', 'params' => $filterParams]
                )) {
                continue;
            }
            $filter = new FilterEntity();
            $filter->setCategory($category);
            $filter->setFilterType($input->filterType);
            $filter->setParams($filterParams);
            $filter->setOrder($input->order ?? 0);

            $em->persist($filter);
        }
        $em->flush();
        //  $response->getBody()->write('');
        return $response;
    }

}
