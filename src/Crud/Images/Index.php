<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Images;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use InvalidArgumentException;

final class Index implements ModelInterface
{

    private EntityManager $entityManager;
    private ?Product $product;

    public function __construct(EntityManager $entityManager, ServerRequestInterface $serverRequest)
    {
        if(!isset($_ENV['UPLOAD_URL'])){
            throw new InvalidArgumentException('Not set UPLOAD_URL in .env');
        }
        
        $this->entityManager = $entityManager;

        $this->product = $entityManager->getRepository(Product::class)->find($serverRequest->get('product_id'));
        if($this->product === null){
            Error::code(404);
        }

    }

    public function getContext(): array
    {

        return [
            'product' => $this->product,
            'images' => $this->entityManager->getRepository(Image::class)->findBy(['product' => $this->product])
        ];
    }
}
