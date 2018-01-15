<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectsPrePublishCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('projects:pre_publish')
            ->setDescription('Check projects that are going to be published in the next 15 minutes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger                  = $this->getContainer()->get('monolog.logger.console');
        $projectLifecycleManager = $this->getContainer()->get('unilend.service.project_lifecycle_manager');
        $entityManagerSimulator  = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \projects $project */
        $project        = $entityManagerSimulator->getRepository('projects');
        $projectsToFund = $project->selectProjectsByStatus([\projects_status::A_FUNDER], "AND p.date_publication <= (NOW() + INTERVAL 15 MINUTE)", [], '', 1, false);

        foreach ($projectsToFund as $projectTable) {
            if ($project->get($projectTable['id_project'])) {
                $output->writeln('Project : ' . $project->title);

                try {
                    $projectLifecycleManager->setLogger($logger);
                    $projectLifecycleManager->prePublish($project);
                } catch (\Exception $exception) {
                    $logger->critical(
                        'An exception occurred during prepublishing of project ' . $project->id_project . ' with message: ' . $exception->getMessage(),
                        ['method' => __METHOD__, 'file'   => $exception->getFile(), 'line'   => $exception->getLine() ]
                    );
                }
            }
        }
    }
}
