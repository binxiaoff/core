<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Unilend\librairies\CacheKeys;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Cache\Adapter\Memcache\MemcacheCachePool;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectsFundingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('projects:funding')
            ->setDescription('Check projects that are in FUNDING status');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '1G');

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var MailerManager $mailerManager */
        $mailerManager = $this->getContainer()->get('unilend.service.email_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        /** @var ProjectManager $projectManager */
        $projectManager = $this->getContainer()->get('unilend.service.project_manager');
        $projectManager->setLogger($logger);

        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');

        // Loaded for class constants
        $entityManager->getRepository('projects_status');

        $hasProjectFinished = false;
        $projects           = $project->selectProjectsByStatus(\projects_status::EN_FUNDING, '', '', array(), '', '', false);

        foreach ($projects as $projectTable) {
            if ($project->get($projectTable['id_project'])) {
                $output->writeln('Project : ' . $project->title);

                $currentDate = new \DateTime();
                $endDate     = $projectManager->getProjectEndDate($project);
                $isFunded    = $projectManager->isFunded($project);

                if ($isFunded) {
                    $projectManager->markAsFunded($project);
                }

                if ($endDate > $currentDate) {
                    $projectManager->checkBids($project, true);
                    $projectManager->autoBid($project);
                } else {
                    $project->date_fin = $currentDate->format('Y-m-d H:i:s');
                    $project->update();

                    $hasProjectFinished = true;

                    if ($isFunded) {
                        $projectManager->buildLoans($project);
                        $projectManager->createRepaymentSchedule($project);
                        $projectManager->createPaymentSchedule($project);

                        $mailerManager->sendFundedAndFinishedToBorrower($project);
                        $mailerManager->sendFundedToStaff($project);
                        $mailerManager->sendBidAccepted($project);
                    } else {
                        $projectManager->treatFundFailed($project);

                        $mailerManager->sendFundFailedToBorrower($project);
                        $mailerManager->sendFundFailedToLender($project);
                    }
                    $mailerManager->sendProjectFinishedToStaff($project);
                }
            }
        }

        if ($hasProjectFinished) {
            /** @var MemcacheCachePool $cachePool */
            $cachePool = $this->getContainer()->get('memcache.default');
            $cachePool->deleteItem(CacheKeys::LIST_PROJECTS);
        }
    }
}
