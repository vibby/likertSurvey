<?php

namespace AppBundle\Survey;

use AppBundle\Entity\Respondent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class RespondentsReviver
{
    private $entityManager;

    private $twig;

    private $kernel;

    private $adminEmailer;

    public function __construct(
        EntityManagerInterface $entityManager,
        Environment $twig,
        KernelInterface $kernel,
        AdminMailer $adminEmailer
    ) {
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->kernel = $kernel;
        $this->adminEmailer = $adminEmailer;
    }

    public function revive()
    {
        $days = 10;
        $from = new \DateTime();
        $filename = sprintf(
            'revive_%s.csv',
            $from->format('Y-m-d_h-i-s')
        );
        $fullFilename = sprintf(
            '%s/data/exports/%s',
            $this->kernel->getRootDir(),
            $filename
        );
        $from->sub(new \DateInterval(sprintf('P%sD', $days)));
        $repo = $this->entityManager->getRepository(Respondent::class);
        $repo->updateIncrementExport($from);
        $respondents = $repo->getQueryUnconnectedSinceXDays($from)->getResult();
        $content = $this->twig->render(
            'admin/list.csv.twig',
            ['respondents' => $respondents]
        );
        file_put_contents($fullFilename, $content);
        $this->adminEmailer->sendWithFile($content, $filename, 'LikertSurvey Daily report');
    }
}
