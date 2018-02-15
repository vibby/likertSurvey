<?php

namespace AppBundle\Survey;

use AppBundle\Entity\Respondent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class AdminMailer
{
    private $mailer;

    public function __construct(
        \Swift_Mailer $mailer
    ) {
        $this->mailer = $mailer;
    }

    public function sendWithFile($fileBody, $fileName, $subject, $body = 'Here is a new file from Survey !')
    {
        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        $attachment = new \Swift_Attachment();
        $attachment->setFilename($fileName);
        $attachment->setBody($fileBody);
        $message->attach($attachment);
        $message->addFrom('no-reply@teep.fr');
        $message->addTo('vincent@beauvivre.fr');
        $message->setSubject($subject);
        $message->setBody($body);

        $this->mailer->send($message);
    }
}
