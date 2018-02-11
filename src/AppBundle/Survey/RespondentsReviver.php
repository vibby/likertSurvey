<?php

namespace AppBundle\Survey;

use AppBundle\Entity\Respondent;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class RespondentsReviver
{
    private $entityManager;

    private $twig;

    private $kernel;

    public function __construct(EntityManager $entityManager, Environment $twig, KernelInterface $kernel)
    {
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->kernel = $kernel;
    }

    public function revive()
    {
        $days = 10;
        $from = new \DateTime();
        $filename = sprintf(
            '%s/exports/revive_%s.csv',
            $this->kernel->getRootDir(),
            $from->format('Y-m-d_h-i-s')
        );
        $from->sub(new \DateInterval(sprintf('P%sD', $days)));
        $repo = $this->entityManager->getRepository(Respondent::class);
        $repo->updateIncrementExport($from);
        $respondents = $repo->getQueryUnconnectedSinceXDays($from)->getResult();
        file_put_contents(
            $filename,
            $this->twig->render(
                'admin/list.csv.twig',
                ['respondents' => $respondents]
            )
        );

        return $filename;
    }
}
