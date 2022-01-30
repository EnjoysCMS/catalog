<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Images;


use Doctrine\ORM\EntityManager;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use Intervention\Image\ImageManagerStatic;

final class ManageImage
{
    /**
     * @var array|object[]
     */
    private array $productImages;

    public function __construct(private Product $product, private EntityManager $entityManager)
    {
        $this->productImages = $entityManager->getRepository(Image::class)->findBy(['product' => $this->product]);
    }

    public function addToDB(string $filename, string $extension, string $path): void
    {
        $image = new Image();
        $image->setProduct($this->product);
        $image->setFilename($filename);
        $image->setExtension($extension);
        $image->setGeneral(empty($this->productImages));

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        $imgSmall = ImageManagerStatic::make($path);
        $imgSmall->resize(
            300,
            300,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );
        $imgSmall->save(
            str_replace(
                $filename,
                $filename . '_small',
                $path
            )
        );

        $imgLarge = ImageManagerStatic::make($path);
        $imgLarge->resize(
            900,
            900,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );
        $imgLarge->save(
            str_replace(
                $filename,
                $filename . '_large',
                $path
            )
        );
    }

}
