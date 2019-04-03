<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};
use Unilend\Entity\Projects;

class ProjectsPrePublishCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setName('projects:pre_publish')
            ->setDescription('Check projects that are going to be published in the next 15 minutes');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $logger                  = $this->getContainer()->get('monolog.logger.console');
        $projectLifecycleManager = $this->getContainer()->get('unilend.service.project_lifecycle_manager');
        $projectRepository       = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository(Projects::class);
        $projectsToFund          = $projectRepository->findPrePublish(1);

        $projectLifecycleManager->setLogger($logger);

        /** @var Projects $project */
        foreach ($projectsToFund as $project) {
            $output->writeln('Project : ' . $project->getTitle());

            try {
                $projectLifecycleManager->prePublish($project);
            } catch (\Exception $exception) {
                $logger->critical('An error occurred during pre-publish of project ' . $project->getIdProject() . ': ' . $exception->getMessage(), [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine()
                ]);
            }
        }
    }
}
