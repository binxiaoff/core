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
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        $entityManager->getRepository('projects_status'); // Loaded for class constants
        $entityManager->getRepository('users'); // Loaded for class constants

        /** @var \projects $oProject */
        $oProject = $entityManager->getRepository('projects');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->getContainer()->get('unilend.service.project_manager');

        foreach ($oProject->getFastProcessStep3() as $iProjectId) {
            $oProject->get($iProjectId, 'id_project');
            $oProjectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::A_TRAITER, $oProject);
        }
    }
}
