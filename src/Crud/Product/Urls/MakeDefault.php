<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Crud\Product\Urls;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enjoys\ServerRequestWrapper;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MakeDefault
{

    private Product $product;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestWrapper $requestWrapper,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $product = $this->em->getRepository(Product::class)->find($this->requestWrapper->getQueryData('product_id'));
        if ($product === null) {
            throw new NoResultException();
        }
        $this->product = $product;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function __invoke()
    {
        $setFlag = false;
        /** @var Url $url */
        foreach ($this->product->getUrls() as $url) {
            if ($url->getId() === (int)$this->requestWrapper->getQueryData('url_id')) {
                $url->setDefault(true);
                $setFlag = true;
                continue;
            }
            $url->setDefault(false);
        }

        if ($setFlag === false) {
            throw new \InvalidArgumentException('Url id is invalid');
        }

        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('@a/catalog/product/urls', ['id' => $this->product->getId()]));
    }
}
