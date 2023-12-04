<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Api;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Core\Routing\Annotation\Route;
use JMS\Serializer\SerializerBuilder;
use Psr\Http\Message\ResponseInterface;

#[Route(
    path: 'admin/catalog/api/vendor',
    name: '@catalog_api_vendor_',
)]
final class Vendor extends AbstractController
{

    #[Route(
        path: '/find',
        name: 'find',
        methods: ['GET'],
        comment: 'API: Поиск бренда или производителя'
    )]
    public function find(): ResponseInterface
    {
        $serializer = SerializerBuilder::create()->build();

        /** @var EntityRepository $vendorRepository */
        $vendorRepository = $this->container->get(EntityManagerInterface::class)->getRepository(
            \EnjoysCMS\Module\Catalog\Entity\Vendor::class
        );
        $query = $this->request->getQueryParams()['q'] ?? '';

        $result = $vendorRepository->matching(
            (new Criteria())
                ->where(Criteria::expr()->contains('name', $query))
        );

        return $this->json($serializer->toArray($result->toArray()));
    }
}
