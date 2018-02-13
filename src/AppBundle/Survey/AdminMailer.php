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

    public function sendWithFile($filename)
    {
        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        $attachment = new \Swift_Attachment();
        $attachment->setFilename($filename);
        $message->attach($attachment);
        $message->addFrom('no-reply@vibby.fr');
        $message->addTo('vincent@beauvivre.fr');
        $message->setSubject('LikertSurvey Daily report');
        $message->setBody('See attached CSV file');

        $this->mailer->send($message);
    }
}
