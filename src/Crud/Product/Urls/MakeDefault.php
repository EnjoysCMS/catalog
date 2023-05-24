<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Urls;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use EnjoysCMS\Core\Interfaces\RedirectInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

final class MakeDefault
{

    private Product $product;

    /**
     * @throws NoResultException
     * @throws NotSupported
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $request,
        private RedirectInterface $redirect,
    ) {
        $this->product = $this->em->getRepository(Product::class)->find(
            $this->request->getQueryParams()['product_id'] ?? null
        ) ?? throw new NoResultException();
    }


    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function __invoke(): void
    {
        $setFlag = false;
        /** @var Url $url */
        foreach ($this->product->getUrls() as $url) {
            if ($url->getId() === (int)($this->request->getQueryParams()['url_id'] ?? null)) {
                $url->setDefault(true);
                $setFlag = true;
                continue;
            }
            $url->setDefault(false);
        }

        if ($setFlag === false) {
            throw new InvalidArgumentException('Url id is invalid');
        }

        $this->em->flush();
        $this->redirect->toRoute(
            '@a/catalog/product/urls',
            ['id' => $this->product->getId()],
            emit: true
        );
    }
}
