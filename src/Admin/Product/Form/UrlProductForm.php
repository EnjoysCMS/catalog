<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Admin\Product\Form;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Psr\Http\Message\ServerRequestInterface;

final class UrlProductForm
{

    private EntityRepository|ProductRepository $productRepository;


    /**
     * @throws NotSupported
     * @throws NoResultException
     */
    public function __construct(
        private readonly EntityManager $em,
        private readonly ServerRequestInterface $request,
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
    }


    public function getForm(Product $product = null): Form
    {
        $url = $product?->getUrlById((int)($this->request->getQueryParams()['url_id'] ?? 0));

        $form = new Form();

        $form->setDefaults([
            'path' => $url?->getPath()
        ]);

        $form->checkbox('default')
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => 'Сделать основным?']);

        $form->text('path', 'Путь')->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () use ($product, $url) {
                    if ($url?->getPath() === ($this->request->getParsedBody()['path'] ?? null)) {
                        return true;
                    }

                    /** @var Product $product */
                    $product = $this->productRepository->getFindByUrlBuilder(
                        $this->request->getParsedBody()['path'] ?? null,
                        $product->getCategory()
                    )->getQuery()->getOneOrNullResult();

                    return is_null($product);
                }
            );
        $form->submit('save', 'Добавить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function doAction(Product $product): void
    {
        $url = $product?->getUrlById((int)($this->request->getQueryParams()['url_id'] ?? 0));


        $url = $url ?? new Url();
        $url->setPath($this->request->getParsedBody()['path'] ?? null);

        $newDefault = (bool)($this->request->getParsedBody()['default'] ?? false);
        if ($newDefault) {
            foreach ($product->getUrls() as $item) {
                $item->setDefault(false);
            }
            $url->setDefault($newDefault);
        }
        $url->setProduct($product);

        $this->em->persist($url);
        $this->em->flush();


    }

}
