<?php

namespace AppBundle\Command;

use AppBundle\Survey\RespondentsReviver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReviveRespondentsCommand extends Command
{
    private $respondentsReviver;

    public function __construct(RespondentsReviver $respondentsReviver)
    {
        $this->respondentsReviver = $respondentsReviver;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('likert-survey:revive-respondents')
            ->setDescription('Send email alerts to revive respondants')
            ->setHelp('This command send an email that contains a csv with respondents inactive since a long time')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->respondentsReviver->revive();
        $output->writeln('Finished');
    }
}
