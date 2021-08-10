<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\Api;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Enjoys\Forms\Element;
use Enjoys\Http\ServerRequestInterface;
use EnjoysCMS\Module\Catalog\Entities\Product;
use EnjoysCMS\Module\Catalog\Repositories;
use HttpSoft\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;

final class SendMail
{
    private Repositories\Product|ObjectRepository|EntityRepository $productRepository;

    public function __construct(
        private EntityManager $em,
        private \EnjoysCMS\Module\Catalog\Models\SendMail $sendMail,
        private EmitterInterface $emitter,
        private ResponseInterface $response,
        private ServerRequestInterface $serverRequest
    ) {
        $this->productRepository = $this->em->getRepository(Product::class);
    }

    /**
     * @Route(
     *     name="catalog/sendmail",
     *     path="api/sendmail.form",
     *     options={
     *      "aclComment": "[public] Запрос коммерческого предложения"
     *     }
     * )
     */

    public function sendmail()
    {
        $form = $this->sendMail->getForm();
        try {
            if ($form->isSubmitted()) {
                /** @var Product $product */
                $product = $this->productRepository->find($this->serverRequest->post('product_id', 0));
                if ($product === null) {
                    throw new NoResultException();
                }

                $_name = $this->sanitizeData($this->serverRequest->post('name'));
                $_phone = $this->sanitizeData($this->serverRequest->post('phone'));
                $_message = $this->sanitizeData($this->serverRequest->post('message'));
                $this->sendMail->setReplyTo($this->serverRequest->post('email'));

                $body = <<<HTML
<h1>{$product->getName()}</h1>
<b>Имя:</b> {$_name}<br/>
<b>Email:</b> {$this->serverRequest->post('email')}<br/>
<b>Телефон:</b> {$_phone}<br/><br/>
<b>Сообщение:</b><br/>
{$_message}<br/>                      
HTML;
                $this->sendMail->setBody($body);
                $this->sendMail->setSubject($product->getName());

                $this->sendMail->doSend();

                $this->response
                    ->getBody()
                    ->write('Сообщение успешно отправлено');
            } else {
                /** @var Element $element */
                $errors = [];
                foreach ($form->getElements() as $element) {
                    if (method_exists($element, 'isRuleError') && $element->isRuleError()) {
//                        throw new \Exception($element->getRuleErrorMessage());
                        $errors[] = $element->getRuleErrorMessage();
                    }
                }
                if (!empty($errors)) {
                    throw new \Exception(implode("<br>", $errors));
                }

                throw new \Exception('Произошла ошибка: Форма не была отправлена');
            }
        } catch (\Exception $e) {
            $this->response = $this->response->withStatus(400);
            $this->response
                ->getBody()
                ->write($e->getMessage());
        }

//        $logger = $this->container->get(LoggerInterface::class);
//        $logger->close();

        $this->emitter->emit($this->response);
    }

    private function sanitizeData(string $data): string
    {
        return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE);
    }
}