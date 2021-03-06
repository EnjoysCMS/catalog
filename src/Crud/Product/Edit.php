<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Crud\Product;

use App\Module\Admin\Core\ModelInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Exception\ExceptionRule;
use Enjoys\Forms\Form;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Forms\Rules;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Helpers\Error;
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

final class Edit implements ModelInterface
{

    private ?Product $product;
    private ObjectRepository|EntityRepository|\EnjoysCMS\Module\Catalog\Repositories\Product $productRepository;
    private ModuleConfig $config;

    public function __construct(
        private EntityManager $entityManager,
        private ServerRequestInterface $serverRequest,
        private RendererInterface $renderer,
        private UrlGeneratorInterface $urlGenerator,
        private ContainerInterface $container
    ) {
        $this->productRepository = $entityManager->getRepository(Product::class);
        $this->product = $this->productRepository->find(
            $this->serverRequest->get('id', 0)
        );
        if ($this->product === null) {
            Error::code(404);
        }
        $this->config = Config::getConfig($this->container);
    }

    /**
     * @throws SyntaxError
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
            'product' => $this->product,
            'subtitle' => '????????????????????????????',
            'wysiwyg' => $wysiwyg->selector('#description'),
        ];
    }

    /**
     * @throws ExceptionRule
     */
    private function getForm(): Form
    {
        $defaults = [
            'name' => $this->product->getName(),
            'url' => $this->product->getUrl()->getPath(),
            'description' => $this->product->getDescription(),
            'qty' => $this->product->getQuantity()->getQty(),
            'active' => [(int)$this->product->isActive()],
            'hide' => [(int)$this->product->isHide()],
        ];

        $category = $this->product->getCategory();
        if ($category instanceof Category) {
            $defaults['category'] = $category->getId();
        }

        $form = new Form(['method' => 'post']);

        $form->setDefaults($defaults);

        $form->checkbox('active', null)
            ->setPrefixId('active')
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => '???????????????'])
        ;

        $form->checkbox('hide', null)
            ->setPrefixId('hide')
            ->addClass(
                'custom-switch custom-switch-off-danger custom-switch-on-success',
                Form::ATTRIBUTES_FILLABLE_BASE
            )
            ->fill([1 => '???????????'])
        ;

        $form->select('category', '??????????????????')
            ->fill(
                $this->entityManager->getRepository(
                    Category::class
                )->getFormFillArray()
            )
            ->addRule(Rules::REQUIRED)
        ;

        $form->text('name', '????????????????????????')
            ->addRule(Rules::REQUIRED)
        ;


        $form->text('url', 'URL')
            ->addRule(Rules::REQUIRED)
            ->addRule(
                Rules::CALLBACK,
                '????????????, ?????????? url ?????? ????????????????????',
                function () {
                    /** @var Product $product */
                    $product = $this->productRepository->getFindByUrlBuilder(
                        $this->serverRequest->post('url'),
                        $this->product->getCategory()
                    )->getQuery()->getOneOrNullResult();

                    if ($product === null) {
                        return true;
                    }

                    /** @var Url $url */
                    foreach ($product->getUrls() as $url) {
                        if ($url->getProduct()->getId() === $this->product->getId()) {
                            return true;
                        }
                    }

                    return false;
                }
            )
        ;
        $form->textarea('description', '????????????????');
        $form->number(
            'qty',
            sprintf('????????????????????, %s', $this->product->getPrices()->first()->getUnit()->getName())
        )->setAttributes([
            'step' => $this->product->getQuantity()->getStep(),
            'min' => $this->product->getQuantity()->getMin()
        ]);


        $form->submit('add');
        return $form;
    }

    private function doAction()
    {
        /** @var Category|null $category */
        $category = $this->entityManager->getRepository(Category::class)->find(
            $this->serverRequest->post('category', 0)
        );

        $this->product->setName($this->serverRequest->post('name'));
        $this->product->setDescription($this->serverRequest->post('description'));
        $this->product->setQuantity($this->product->getQuantity()->setQty($this->serverRequest->post('qty')));
        $this->product->setCategory($category);
        $this->product->setActive((bool)$this->serverRequest->post('active', false));
        $this->product->setHide((bool)$this->serverRequest->post('hide', false));


        $urlString = (empty($this->serverRequest->post('url')))
            ? URLify::slug($this->product->getName())
            : $this->serverRequest->post('url');

        /** @var Url $url */
        $urlSetFlag = false;
        foreach ($this->product->getUrls() as $url) {
            if ($url->getPath() === $urlString) {
                $url->setDefault(true);
                $urlSetFlag = true;
                continue;
            }
            $url->setDefault(false);
        }

        if ($urlSetFlag === false) {
            $url = new Url();
            $url->setPath($urlString);
            $url->setDefault(true);
            $url->setProduct($this->product);
            $this->entityManager->persist($url);
        }

        $this->entityManager->flush();
        Redirect::http($this->urlGenerator->generate('catalog/admin/products'));
    }
}
