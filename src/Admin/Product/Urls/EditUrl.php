<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Urls;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Psr\Http\Message\ServerRequestInterface;

final class EditUrl
{


    private ProductRepository|EntityRepository $repository;

    /**
     * @throws NotSupported
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {
        $this->repository = $this->em->getRepository(Product::class);
    }

    /**
     * @throws ExceptionRule
     */
    public function getForm(Product $product): Form
    {
        $url = $product->getUrlById((int)($this->request->getQueryParams()['url_id'] ?? 0));

        $form = new Form();
        $form->setDefaults([
            'path' => $url->getPath()
        ]);
        $form->text('path', 'Путь')->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () use ($url, $product) {
                    if ($this->repository->getFindByUrlBuilder(
                            $this->request->getParsedBody()['path'] ?? null,
                            $product->getCategory()
                        )->getQuery()->getOneOrNullResult() === null) {
                        return true;
                    }

                    if ($url->getPath() === ($this->request->getParsedBody()['path'] ?? null)) {
                        return true;
                    }

                    return false;
                }
            );
        $form->submit('save', 'Сохранить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(Product $product): void
    {
        $url = $product->getUrlById((int)($this->request->getQueryParams()['url_id'] ?? 0));
        $url->setPath($this->request->getParsedBody()['path'] ?? null);
        $this->em->flush();
    }

}
