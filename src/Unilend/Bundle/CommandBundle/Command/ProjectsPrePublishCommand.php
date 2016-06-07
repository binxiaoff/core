<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Unilend\Service\ProjectManager;
use Unilend\Service\Simulator\EntityManager;
use Psr\Log\LoggerInterface;
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
        ini_set('memory_limit', '1G');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        /** @var ProjectManager $projectManager */
        $projectManager = $this->getContainer()->get('unilend.service.project_manager');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');

        $projectsToFund = $project->selectProjectsByStatus(\projects_status::A_FUNDER,  "AND p.date_publication_full <= (NOW() + INTERVAL 15 MINUTE)", '', array(), '', '', false);

        foreach ($projectsToFund as $projectTable) {
            if ($project->get($projectTable['id_project'])) {
                $output->writeln('Project : ' . $project->title);

                $logger->info('Do process pre-publish on project ID: ' . $project->id_project, array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
                $projectManager->prePublish($project);
            }
        }
    }
}
