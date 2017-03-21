<?php
namespace Unilend\Bundle\CommandBundle\Command;

use CL\Slack\Payload\ChatPostMessagePayload;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

class RetakeAltaresCallCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:altares:retake')
            ->setDescription('Retake the Altares result for the given projects')
            ->addOption('projects', null, InputOption::VALUE_REQUIRED, 'A project list separated by virgule.')
            ->setHelp(<<<EOF
The <info>unilend:dev_tools:altares:retake</info> command call Altares to get the information for all projects in argument.
<info>php bin/console unilend:dev_tools:altares:retake project_id1,project_id2,project_id3</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager  = $this->getContainer()->get('unilend.service.entity_manager');
        $logger         = $this->getContainer()->get('logger');
        $altares        = $this->getContainer()->get('unilend.service.altares');
        $projectManager = $this->getContainer()->get('unilend.service.project_manager');

        /** @var \settings $settingsAltaresStatus */
        $settingsAltaresStatus = $entityManager->getRepository('settings');
        $settingsAltaresStatus->get('Altares status', 'type');

        /** @var \companies_bilans $companyAccount */
        $companyAccount = $entityManager->getRepository('companies_bilans');

        $projects = explode(',', $input->getOption('projects'));

        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');

        foreach ($projects as $projectId) {
            if ($project->get($projectId) && $company->get($project->id_company)) {
                if (empty($company->siren)) {
                    continue;
                }
                try {
                    $result  = $altares->isEligible($project);
                    $altares->setCompanyData($company);
                    $altares->setProjectData($project);
                    if (false === $result['eligible']) {
                        if ($project->status != \projects_status::NOTE_EXTERNE_FAIBLE) {
                            $motif = implode(',', $result['reason']);
                            $projectManager->addProjectStatus(Users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $motif);
                        }
                        continue;
                    }
                    $altares->setCompanyBalance($company);

                    $lastBilan = $companyAccount->select('id_company = ' . $company->id_company, 'cloture_exercice_fiscal DESC', 0, 1);

                    if (true === isset($lastBilan[0]['id_bilan'])) {
                        $project->id_dernier_bilan = $lastBilan[0]['id_bilan'];
                        $project->update();
                    }

                    if ($project->status < \projects_status::A_TRAITER){
                        $projectManager->addProjectStatus(Users::USER_ID_CRON, \projects_status::A_TRAITER, $project);
                    }
                } catch (\Exception $exception) {
                    if ($settingsAltaresStatus->value) {
                        $settingsAltaresStatus->value = '0';
                        $settingsAltaresStatus->update();

                        $logger->error(
                            $exception->getMessage(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $company->siren]
                        );

                        $payload = new ChatPostMessagePayload();
                        $payload->setChannel('#it-monitoring');
                        $payload->setText("Altares is down  :skull_and_crossbones:\n> " . $exception->getMessage());
                        $payload->setUsername('Altares');
                        $payload->setIconUrl($this->get('assets.packages')->getUrl('') . '/assets/images/slack/altares.png');
                        $payload->setAsUser(false);

                        $this->getContainer()->get('cl_slack.api_client')->send($payload);
                    }
                }

                if (! $settingsAltaresStatus->value) {
                    $settingsAltaresStatus->value = 1;
                    $settingsAltaresStatus->update();

                    $payload = new ChatPostMessagePayload();
                    $payload->setChannel('#it-monitoring');
                    $payload->setText('Altares is up  :white_check_mark:');
                    $payload->setUsername('Altares');
                    $payload->setIconUrl($this->getContainer()->get('assets.packages')->getUrl('') . '/assets/images/slack/altares.png');
                    $payload->setAsUser(false);

                    $this->getContainer()->get('cl_slack.api_client')->send($payload);
                }

                $output->writeln('Project id :' . $project->id_project . '. status : ' . $project->status);
            }
        }
    }
}
