<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Catalog\Models;

use Enjoys\Core\Session\Session;
use Enjoys\Forms\Captcha\reCaptcha\reCaptcha;
use Enjoys\Forms\Form;
use Enjoys\Forms\Rules;
use Enjoys\Mail;
use Enjoys\Traits\Options;
use EnjoysCMS\Module\Catalog\Config;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SendMail
{

    use Options;

    private string $replyTo;
    private string $subject;
    private string $body;

    public function __construct(private ContainerInterface $container)
    {
        $this->setOptions(Config::getConfig($container)->getAll());
    }

    public function getForm(): Form
    {
        $config = $this->getOption('formSendRequest');

        $form = new Form(
            [
                'action' => $this->container->get(UrlGeneratorInterface::class)->generate('catalog/sendmail'),
                'method' => 'post',
                'name' => 'formSendRequest'
            ]
        );
        $form->setAttribute('id', 'formSendRequest');

        $form->text('name')
            ->addRule(Rules::REQUIRED, 'Имя - обязательно для заполнения')
        ;
        $form->email('email')
            ->addRule(Rules::REQUIRED, 'Email - обязательно для заполнения')
            ->addRule(Rules::EMAIL)
        ;
        $form->text('phone')
            ->addRule(Rules::REQUIRED, 'Телефон - обязательно для заполнения')
        ;
        $form->textarea('message')
            ->addRule(Rules::REQUIRED, 'Сообщение - обязательно для заполнения')
        ;

        if ($config['captcha']) {
            $captcha = new reCaptcha();
            $captcha->setOptions(
                [
                    'privatekey' => $_ENV['RECAPTCHA_SECRET_KEY'] ?? $config['RECAPTCHA_SECRET_KEY'] ?? '6LdUGNEZAAAAAPPz685RwftPySFeCLbV1xYJJjsk',
                    'publickey' => $_ENV['RECAPTCHA_PUBLIC_KEY'] ?? $config['RECAPTCHA_PUBLIC_KEY'] ?? '6LdUGNEZAAAAANA5cPI_pCmOqbq-6_srRkcGOwRy',
                ]
            );
            $form->captcha($captcha);
        }

        $form->submit('sendMessage');
        return $form;
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function doSend(): void
    {
        $phpMailer = $this->container->get(PHPMailer::class);
        //Recipients
        $phpMailer->setFrom(
            $this->getOption('mailer')['from'][0] ?? '',
            $this->getOption('mailer')['from'][1] ?? ''
        );
        foreach ($this->getOption('mailer')['address'] as $to) {
            $phpMailer->addAddress($to);
        }
        $phpMailer->addReplyTo($this->getReplyTo());

        //Content
        $phpMailer->isHTML(true);                                  // Set email format to HTML
        $phpMailer->CharSet = 'UTF-8';
        $phpMailer->Subject = $this->getSubject();
        $phpMailer->Body = $this->getBody();
        $phpMailer->AltBody = strip_tags($phpMailer->Body);


        $result = $phpMailer->send();
        if ($result === false) {
            throw new \Exception($phpMailer->ErrorInfo);
        }
    }


    /**
     * @return string
     */
    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    /**
     * @param string $replyTo
     */
    public function setReplyTo(string $replyTo): void
    {
        $this->replyTo = $replyTo;
    }


    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }


}