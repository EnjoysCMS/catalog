<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Cookie\Cookie;
use Enjoys\Cookie\Exception;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;
use EnjoysCMS\Core\Components\WYSIWYG\WYSIWYG;
use EnjoysCMS\Module\Catalog\Config;
use EnjoysCMS\Module\Catalog\Entities\Category;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Entities\Url;
use EnjoysCMS\Module\Catalog\Helpers\URLify;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Add implements ModelInterface
{

    private ObjectRepository|EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Product $productRepository;
    private ObjectRepository|EntityRepository $categoryRepository;
    private ModuleConfig $config;

    public function __construct(
        private EntityManager $em,
        private ServerRequestInterface $serverRequest,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private ContainerInterface $container,
        private Cookie $cookie
    ) {
        $this->productRepository = $em->getRepository(Product::class);
        $this->categoryRepository = $em->getRepository(Category::class);

        $this->config = Config::getConfig($this->container);
    }

    /**
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws ExceptionRule
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function getContext(): array
    {
        $form = $this->getForm();

        $this->renderer->setForm($form);

        if ($form->isSubmitted()) {
            $this->doAction();
        }

        $wysiwyg = WYSIWYG::getInstance($this->config->get('WYSIWYG'), $this->container);

        return [
            'form' => $this->renderer,
            'wysiwyg' => $wysiwyg->selector('#description'),
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $form = new Form(['method' => 'post']);

        $form->setDefaults(
            [
                'category' => $this->serverRequest->get(
                    'category_id',
                    Cookie::get('__catalog__last_category_when_add_product')
                )
            ]
        );


        $form->select('category', '??????????????????')
            ->fill(
                ['0' => '_?????? ??????????????????_'] + $this->em->getRepository(
                    Category::class
                )->getFormFillArray()
            )
            ->addRule(Rules::REQUIRED);
        $form->text('name', '????????????????????????')
            ->addRule(Rules::REQUIRED);


        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                '????????????, ?????????? url ?????? ????????????????????',
                function () {
                    $check = $this->productRepository->getFindByUrlBuilder(
                        $this->serverRequest->post('url'),
                        $this->categoryRepository->find($this->serverRequest->post('category', 0))
                    )->getQuery()->getOneOrNullResult();
                    return is_null($check);
                }
            );
        $form->textarea('description', '????????????????');

        $form->submit('add');
        return $form;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    private function doAction()
    {
        $categoryId = $this->serverRequest->post('category', 0);
        $this->cookie->set('__catalog__last_category_when_add_product', $categoryId);

        /** @var Category|null $category */
        $category = $this->em->getRepository(Category::class)->find($categoryId);

        $product = new Product();
        $product->setName($this->serverRequest->post('name'));
        $product->setDescription($this->serverRequest->post('description'));

        $product->setCategory($category);

        $product->setHide(false);
        $product->setActive(true);

        $this->em->persist($product);
        $this->em->flush();

        $url = new Url();
        $url->setProduct($product);
        $url->setDefault(true);
        $url->setPath(
            (empty($this->serverRequest->post('url')))
                ? URLify::slug($product->getName())
                : $this->serverRequest->post('url')
        );

        $this->em->persist($url);
        $this->em->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }
}
