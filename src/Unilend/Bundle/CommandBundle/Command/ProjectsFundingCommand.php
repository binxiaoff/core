<?php
namespace Unilend\Bundle\CommandBundle\Command;

use CL\Slack\Payload\ChatPostMessagePayload;
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

        $url                = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $hasProjectFinished = false;
        $projects           = $project->selectProjectsByStatus([\projects_status::EN_FUNDING], '', [], '', '', false);

        foreach ($projects as $projectTable) {
            if ($project->get($projectTable['id_project'])) {
                $output->writeln('Project : ' . $project->title);

                $currentDate = new \DateTime();
                $endDate     = $projectManager->getProjectEndDate($project);
                $isFunded    = $projectManager->isFunded($project);

                if ($isFunded) {
                    $projectManager->markAsFunded($project);
                }

                if ($endDate > $currentDate && false === $projectManager->isRateMinReached($project)) {
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
                        $mailerManager->sendBidAccepted($project);
                    } else {
                        $projectManager->treatFundFailed($project);

                        $mailerManager->sendFundFailedToBorrower($project);
                        $mailerManager->sendFundFailedToLender($project);
                    }

                    $now     = new \DateTime();
                    $payload = new ChatPostMessagePayload();
                    $payload->setChannel('#plateforme');
                    $payload->setText('Le projet *<' . $url . '/projects/detail/' . $project->slug . '|' . $project->title . '>* est cloturé le ' . $now->format('d/m/Y à H:i'));
                    $payload->setUsername('Unilend');
                    $payload->setIconUrl($this->getContainer()->get('assets.packages')->getUrl('/assets/images/slack/unilend.png'));
                    $payload->setAsUser(false);

                    $this->getContainer()->get('cl_slack.api_client')->send($payload);

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
