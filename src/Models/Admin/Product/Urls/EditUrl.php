<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Models\Admin\Product\Urls;


use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Repositories\Product as ProductRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EditUrl implements ModelInterface
{
    private ObjectRepository|EntityRepository|ProductRepository $productRepository;
    protected Product $product;
    private Url $url;

    /**
     * @throws NoResultException
     */
    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
        $this->product = $this->getProduct();
        $this->url = $this->product->getUrlById((int)$this->serverRequest->get('url_id'));
    }

    /**
     * @throws NoResultException
     */
    private function getProduct(): Product
    {
        $product = $this->productRepository->find($this->serverRequest->get('product_id'));
        if ($product === null) {
            throw new NoResultException();
        }
        return $product;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $renderer = new Bootstrap4([], $form);

        return [
            'product' => $this->product,
            'form' => $renderer->render(),
            'subtitle' => 'Редактирование URL'
        ];
    }

    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);
        $form->setDefaults([
            'path' => $this->url->getPath()
        ]);
        $form->text('path', 'Путь')->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                'Ошибка, такой url уже существует',
                function () {
                    /** @var Product $product */
                    $product = $this->productRepository->getFindByUrlBuilder(
                        $this->serverRequest->post('path'),
                        $this->product->getCategory()
                    )->getQuery()->getOneOrNullResult();

                    if ($product === null) {
                        return true;
                    }

                    if ($this->url->getPath() === $this->serverRequest->post('path')){
                        return true;
                    }

                    return false;
                }
            )
        ;
        $form->submit('save', 'Сохранить');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function doAction()
    {
        $this->url->setPath($this->serverRequest->post('path'));
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('@a/catalog/product/urls', ['id' => $this->product->getId()]));
    }
}