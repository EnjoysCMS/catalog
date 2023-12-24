<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Api;

use DI\Container;
use Doctrine\ORM\EntityManagerInterface;
use EnjoysCMS\Core\AbstractController;
use EnjoysCMS\Core\Routing\Annotation\Route;
use JMS\Serializer\SerializerBuilder;
use Psr\Http\Message\ResponseInterface;

#[Route('admin/catalog/api', '@catalog_admin_api~')]
final class Brands extends AbstractController
{

    private EntityManagerInterface $em;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->em = $container->get(EntityManagerInterface::class);
    }

    #[Route(
        path: '/get-brands',
        name: 'get-brands',
        methods: [
            'GET'
        ],
        comment: 'API: получение списка Брендов'
    )]
    public function getBrands(): ResponseInterface
    {
        $serializer = SerializerBuilder::create()->build();
        $brandsRepository = $this->em->getRepository(\EnjoysCMS\Module\Catalog\Entity\Vendor::class);
        return $this->json($serializer->toArray($brandsRepository->findAll()));
    }
}
