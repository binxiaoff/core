<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\Altares;

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
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $settings->get('Altares email alertes', 'type');
        $alertEmail = $settings->value;

        $settingsAltaresStatus = $entityManager->getRepository('settings');
        $settingsAltaresStatus->get('Altares status', 'type');
        $altaresStatus = $settingsAltaresStatus->value;

        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('logger');

        $altares = new Altares();

        $projects = explode(',', $input->getArgument('projects'));

        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');

        $projectManager = $this->getContainer()->get('unilend.service.project_manager');

        foreach ($projects as $projectId) {
            if ($project->get($projectId) && $company->get($project->id_company)) {
                try {
                    $result  = $altares->getEligibility($company->siren);
                } catch (\Exception $exception) {
                    if ($altaresStatus) {
                        $settingsAltaresStatus->value = 0;
                        $settingsAltaresStatus->update();

                        $logger->error(
                            'Calling Altares::getEligibility() using SIREN ' . $company->siren . ' - Exception message: ' . $exception->getMessage(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $company->siren]
                        );

                        mail($alertEmail, '[ALERTE] ERREUR ALTARES 2', 'Date ' . date('Y-m-d H:i:s') . '' . $exception->getMessage());
                    }
                    continue;
                }

                if (false === empty($result->exception)) {
                    if ($altaresStatus) {
                        $settingsAltaresStatus->value = 0;
                        $settingsAltaresStatus->update();

                        $logger->error(
                            'Altares error code: ' . $result->exception->code . ' - Altares error description: ' . $result->exception->description . ' - Altares error: ' . $result->exception->erreur,
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $this->company->siren]
                        );

                        mail($alertEmail, '[ALERTE] ERREUR ALTARES 1', 'Date ' . date('Y-m-d H:i:s') . 'SIREN : ' . $this->company->siren . ' | ' . $result->exception->code . ' | ' . $result->exception->description . ' | ' . $result->exception->erreur);
                    }

                    $project->retour_altares = Altares::RESPONSE_CODE_WS_ERROR;
                    $project->update();

                    continue;
                }

                if (! $altaresStatus) {
                    $settingsAltaresStatus->value = 1;
                    $settingsAltaresStatus->update();

                    mail($alertEmail, '[INFO] ALTARES is up', 'Date ' . date('Y-m-d H:i:s') . '. Altares is up now.');
                }

                $project->retour_altares = $result->myInfo->codeRetour;
                $altares->setCompanyData($company, $result->myInfo);

                switch ($result->myInfo->eligibility) {
                    case 'Oui':
                        $altares->setProjectData($project, $result->myInfo);
                        $altares->setCompanyBalance($company);

                        /** @var \companies_bilans $companyAccount */
                        $companyAccount = $entityManager->getRepository('companies_bilans');
                        $lastBilan = $companyAccount->select('id_company = ' . $company->id_company, 'cloture_exercice_fiscal DESC', 0, 1);

                        if (true === isset($lastBilan[0]['id_bilan'])) {
                            $project->id_dernier_bilan = $lastBilan[0]['id_bilan'];
                        }

                        $companyCreationDate = new \DateTime($company->date_creation);
                        if ($companyCreationDate->diff(new \DateTime())->days < \projects::MINIMUM_CREATION_DAYS_PROSPECT) {
                            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::PAS_3_BILANS, $project);
                        } else if ($project->status <  \projects_status::A_TRAITER){
                            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::A_TRAITER, $project);
                        }
                        break;
                    case 'Non':
                    default:
                        $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $result->myInfo->motif);
                        break;
                }

                $output->writeln('Project id :' . $project->id_project . '. status : ' . $project->status);

                $project->update();
            }
        }
    }
}
