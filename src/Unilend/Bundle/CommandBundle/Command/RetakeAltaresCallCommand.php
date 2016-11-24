<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Altares;

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
            ->addArgument('projects', InputArgument::REQUIRED, 'A project list separated by virgule.')
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
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        $logger        = $this->getContainer()->get('logger');
        $altares       = $this->getContainer()->get('unilend.service.altares');

        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('Altares email alertes', 'type');
        $alertEmail = $settings->value;

        /** @var \settings $settingsAltaresStatus */
        $settingsAltaresStatus = $entityManager->getRepository('settings');
        $settingsAltaresStatus->get('Altares status', 'type');

        /** @var \companies_bilans $companyAccount */
        $companyAccount = $entityManager->getRepository('companies_bilans');

        $projects = explode(',', $input->getArgument('projects'));

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
                    if (false === $result['eligible']) {
                        continue;
                    }
                    $altares->setProjectData($project);
                    $altares->setCompanyBalance($company);

                    $lastBilan = $companyAccount->select('id_company = ' . $company->id_company, 'cloture_exercice_fiscal DESC', 0, 1);

                    if (true === isset($lastBilan[0]['id_bilan'])) {
                        $project->id_dernier_bilan = $lastBilan[0]['id_bilan'];
                        $project->update();
                    }
                } catch (\Exception $exception) {
                    if ($settingsAltaresStatus->value) {
                        $settingsAltaresStatus->value = '0';
                        $settingsAltaresStatus->update();

                        $logger->error(
                            $exception->getMessage(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $this->company->siren]
                        );

                        mail($alertEmail, '[ALERTE] Altares is down', 'Date ' . date('Y-m-d H:i:s') . '. ' . $exception->getMessage());
                    }

                    $project->retour_altares = Altares::RESPONSE_CODE_WS_ERROR;
                    $project->update();
                }

                if (! $settingsAltaresStatus->value) {
                    $settingsAltaresStatus->value = 1;
                    $settingsAltaresStatus->update();

                    mail($alertEmail, '[INFO] ALTARES is up', 'Date ' . date('Y-m-d H:i:s') . '. Altares is up now.');
                }

                $output->writeln('Project id :' . $project->id_project . '. status : ' . $project->status);
            }
        }
    }
}
