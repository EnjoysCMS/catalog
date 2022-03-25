<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Images;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Module\Catalog\Entities\Image;
use EnjoysCMS\Module\Catalog\Entities\Product;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

final class Index implements ModelInterface
{

    private ?Product $product;

    /**
     * @throws NotFoundException
     */
    public function __construct(private EntityManager $entityManager, ServerRequestInterface $request)
    {
        if(!isset($_ENV['UPLOAD_URL'])){
            throw new InvalidArgumentException('Not set UPLOAD_URL in .env');
        }

        $this->product = $entityManager->getRepository(Product::class)->find($request->getQueryParams()['product_id'] ?? null);
        if($this->product === null){
            throw new NotFoundException(
                sprintf('Not found by product_id: %s', $request->getQueryParams()['product_id'] ?? null)
            );
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
