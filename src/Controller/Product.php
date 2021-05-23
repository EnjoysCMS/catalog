<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Controller;


use DI\FactoryInterface;
use Doctrine\ORM\EntityManager;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Components\Helpers\Assets;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Module\Catalog\Models\SendMail;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class Product
{

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\EnjoysCMS\Module\Catalog\Repositories\Product
     */
    private $repository;
    private UrlGeneratorInterface $urlGenerator;
    private Environment $twig;
    private ServerRequestInterface $serverRequest;
    private EntityManager $entityManager;

    public function __construct(private ContainerInterface $container)
    {
        $this->entityManager = $container->get(EntityManager::class);
        $this->repository = $this->entityManager->getRepository(\EnjoysCMS\Module\Catalog\Entities\Product::class);
        $this->serverRequest = $this->container->get(ServerRequestInterface::class);
        $this->twig = $this->container->get(Environment::class);
        $this->urlGenerator = $this->container->get(UrlGeneratorInterface::class);
    }

    /**
     * @Route(
     *     name="catalog/product",
     *     path="catalog/{slug}.html",
     *     requirements={"slug": "[^.]+"},
     *     options={
     *      "aclComment": "[public] Просмотр продуктов (товаров)"
     *     }
     * )
     */
    public function view(ContainerInterface $container)
    {
        /** @var \EnjoysCMS\Module\Catalog\Entities\Product $product */
        $product = $this->repository->findBySlug($this->serverRequest->get('slug'));
        if ($product === null) {
            Error::code(404);
        }

        $breadcrumbs = $container->get(BreadcrumbsInterface::class);
        $breadcrumbs->add($this->urlGenerator->generate('catalog/index'), 'Каталог');
        foreach ($product->getCategory()->getBreadcrumbs() as $breadcrumb) {
            $breadcrumbs->add(
                $this->urlGenerator->generate('catalog/category', ['slug' => $breadcrumb['slug']]),
                $breadcrumb['title']
            );
        }
        $breadcrumbs->add(null, $product->getName());


        $template_path = '@m/catalog/product.twig';

        if (!$this->twig->getLoader()->exists($template_path)) {
            $template_path = __DIR__ . '/../../template/product.twig.sample';
        }

        Assets::css(
            [
                'template/modules/catalog/assets/style.css',
                'template/modules/catalog/assets/magnific-popup.css',
            ]
        );


        Assets::js(
            [
//                'http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js',
                __DIR__ . '/../../template/assets/js/jquery.magnific-popup.min.js',
                __DIR__ . '/../../template/assets/js/jquery.form.min.js',
            ]
        );

        $sendMail = $container->get(FactoryInterface::class)->make(SendMail::class);

        return $this->twig->render(
            $template_path,
            [
                'product' => $product,
                'breadcrumbs' => $breadcrumbs->get(),
                'sendMailForm' => $sendMail->getForm()
            ]
        );
    }

    /**
     * @Route(
     *     name="catalog/sendmail",
     *     path="catalog/sendmail.form",
     *     options={
     *      "aclComment": "[public] Запрос коммерческого предложения"
     *     }
     * )
     */

    public function sendmail(SendMail $sendMail)
    {
        $emitter = new SapiEmitter();
        $response = new Response();



        $form = $sendMail->getForm();
        try {

            if ($form->isSubmitted()) {

                /** @var \EnjoysCMS\Module\Catalog\Entities\Product $product */
                $product = $this->repository->find($this->serverRequest->post('product_id', 0));
                if ($product === null) {
                    throw new \InvalidArgumentException("Не правильный product_id");
                }

                $_name = htmlspecialchars($this->serverRequest->post('name'), ENT_QUOTES | ENT_SUBSTITUTE);
                $_phone = htmlspecialchars($this->serverRequest->post('phone'), ENT_QUOTES | ENT_SUBSTITUTE);
                $_message = htmlspecialchars($this->serverRequest->post('message'), ENT_QUOTES | ENT_SUBSTITUTE);
                $sendMail->setReplyTo($this->serverRequest->post('email'));

                $body = <<<BODY
<h1>{$product->getName()}</h1>
<b>Имя:</b> {$_name}<br/>
<b>Email:</b> {$this->serverRequest->post('email')}<br/>
<b>Телефон:</b> {$_phone}<br/><br/>
<b>Сообщение:</b><br/>
{$_message}<br/>                      
BODY;
                $sendMail->setBody($body);
                $sendMail->setSubject($product->getName());

                $sendMail->doSend();

                $response
                    ->getBody()
                    ->write('Сообщение успешно отправлено')
                ;
            } else {
                /** @var \Enjoys\Forms\Element $element */
                $errors = [];
                foreach ($form->getElements() as $element) {
                    if (method_exists($element, 'isRuleError') && $element->isRuleError()) {
//                        throw new \Exception($element->getRuleErrorMessage());
                        $errors[] = $element->getRuleErrorMessage();
                    }
                }
                if(!empty($errors)){
                    throw new \Exception(implode("<br>", $errors));
                }

                throw new \Exception('Произошла ошибка: Форма не была отправлена');
            }
        } catch (\Exception $e) {
            $response = $response->withStatus(400);
            $response
                ->getBody()
                ->write($e->getMessage())
            ;
        }

        $logger = $this->container->get(LoggerInterface::class);
        $logger->close();

        $emitter->emit($response);
    }

}