<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;

final class ManageImage
{
    /**
     * @var array|object[]
     */
    private array $productImages;

    public function __construct(private Product $product, private EntityManager $entityManager, private Config $config)
    {
        $this->productImages = $entityManager->getRepository(Image::class)->findBy(['product' => $this->product]);
    }

    public function addToDB(string $filename, string $extension): void
    {
        $image = new Image();
        $image->setProduct($this->product);
        $image->setFilename($filename);
        $image->setExtension($extension);
        $image->setStorage($this->config->get('productImageStorage'));
        $image->setGeneral(empty($this->productImages));

        $this->entityManager->persist($image);
        $this->entityManager->flush();
    }

}
