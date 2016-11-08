<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;


class CheckProjectProcessFastCompletenessCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check:project_process_fast_completeness')
            ->setDescription('checks all projects in fast process that still at step 3 after one hour');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \projects $project */
        $project = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('projects');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
        $projectManager = $this->getContainer()->get('unilend.service.project_manager');

        foreach ($project->getFastProcessStep3() as $iProjectId) {
            $project->get($iProjectId, 'id_project');
            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::A_TRAITER, $project);
        }
    }
}
