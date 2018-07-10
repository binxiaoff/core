<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Box\Spout\{
    Common\Type, Writer\WriterFactory
};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputInterface, Output\OutputInterface
};
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Companies, CompanyRating, Projects
};

class QueriesProjectEligibilityCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('queries:project_eligibility')
            ->setDescription('Extract eligibility of projects');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filePath = $this->getContainer()->getParameter('path.protected') . '/queries/' . 'projects_eligibility.xlsx';
        $header   = $header = [
            'id_project',
            'date dépôt',
            'raison sociale',
            'siren',
            'date_creation',
            'source',
            'partenaire',
            'prescripteur',
            'motif exprimé',
            'montant',
            'durée',
            'prescore',
            'score Altares',
            'trafficLight Euler',
            'grade Euler',
            'score Infolegale',
            'CA',
            'FP',
            'REX',
            'RCS',
            'NAF',
            'statut projet',
            'tronc commun'
        ];

        try {
            $data = $this->getProjectEligibilityData();
            $this->export($data, $header, $filePath);
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->error('An exception occurred while generating projects eligibility file. Message: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);
        }
    }

    /**
     * @return array
     */
    private function getProjectEligibilityData(): array
    {
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $assessmentRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityAssessment');
        $companyRatingHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRatingHistory');
        $companyRatingRepository        = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRating');
        $extraction                     = [];

        $evaluatedProjects = $assessmentRepository->getEvaluatedProjects();

        /** @var Projects $project */
        foreach ($evaluatedProjects as $project) {
            /** @var Companies $company */
            $company              = $project->getIdCompany();
            $motivation           = $project->getIdBorrowingMotive() ? $entityManager->getRepository('UnilendCoreBusinessBundle:BorrowingMotive')->find($project->getIdBorrowingMotive()) : null;
            $status               = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => $project->getStatus()]);
            $projectNote          = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsNotes')->findOneBy(['idProject' => $project]);
            $companyRatingHistory = $companyRatingHistoryRepository->findOneBy(['idCompany' => $company->getIdCompany()]);
            $scoreAltares         = $companyRatingRepository->findOneBy([
                'idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory(),
                'type'                   => CompanyRating::TYPE_ALTARES_SCORE_20
            ]);
            $trafficLightEuler    = $companyRatingRepository->findOneBy([
                'idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory(),
                'type'                   => CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT
            ]);
            $gradeEuler           = $companyRatingRepository->findOneBy([
                'idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory(),
                'type'                   => CompanyRating::TYPE_EULER_HERMES_GRADE
            ]);
            $scoreInfolegale      = $companyRatingRepository->findOneBy([
                'idCompanyRatingHistory' => $companyRatingHistory->getIdCompanyRatingHistory(),
                'type'                   => CompanyRating::TYPE_INFOLEGALE_SCORE
            ]);

            $source = '';
            if ($company->getIdClientOwner() && false == $project->getCreateBo() && false === empty($company->getIdClientOwner()->getSource())) {
                $source = $company->getIdClientOwner()->getSource();
            }

            $partner = '';
            if ($project->getIdPartner()) {
                $partner = $project->getIdPartner()->getIdCompany()->getName();
            }

            $adviserName = 'Non';
            if ($project->getIdPrescripteur()) {
                $adviser = $entityManager->getRepository('UnilendCoreBusinessBundle:Prescripteurs')->find($project->getIdPrescripteur());
                if ($adviser) {
                    $adviserClient = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($adviser->getIdClient());
                    if ($adviserClient) {
                        $adviserName = $adviserClient->getPrenom() . ' ' . $adviserClient->getNom();
                    }
                }
            }

            $projectEligibilityAssessment = $assessmentRepository->findOneBy(
                ['idProject' => $project],
                ['added' => 'DESC', 'id' => 'DESC']
            );

            $row = [
                'id projet'        => $project->getIdProject(),
                'added'            => $project->getAdded()->format('d/m/Y'),
                'company_name'     => $company->getName(),
                'siren'            => $company->getSiren(),
                'date_creation'    => $company->getDateCreation() ? $company->getDateCreation()->format('d/m/Y') : '',
                'source'           => $source,
                'partner'          => $partner,
                'adviser'          => $adviserName,
                'motivation'       => $motivation ? $motivation->getMotive() : '',
                'amount'           => $project->getAmount(),
                'duration'         => $project->getPeriod(),
                'prescore'         => $projectNote ? ($projectNote->getPreScoring() ? $projectNote->getPreScoring() : 'PAS DE DONNEE') : 'Pas de donnée',
                'score_altares'    => $scoreAltares ? $scoreAltares->getValue() : 'Pas de donnée',
                'traffic_light'    => $trafficLightEuler ? $trafficLightEuler->getValue() : 'Pas de donnée',
                'grade_euler'      => $gradeEuler ? $gradeEuler->getValue() : 'Pas de donnée',
                'score_infolegale' => $scoreInfolegale ? $scoreInfolegale->getValue() : 'Pas de donnée',
                'turnover'         => $project->getCaDeclaraClient(),
                'own_funds'        => $project->getFondsPropresDeclaraClient(),
                'operation_income' => $project->getResultatExploitationDeclaraClient(),
                'is_rcs'           => empty($company->getRcs()) ? 'Non' : 'Oui',
                'naf'              => $company->getCodeNaf(),
                'status'           => $status ? $status->getLabel() : '',
                'common_check'     => $projectEligibilityAssessment->getStatus() ? 'OK' : $projectEligibilityAssessment->getIdRule()->getLabel()
            ];

            $extraction[] = $row;
        }

        return $extraction;
    }

    /**
     * @param array  $data
     * @param array  $header
     * @param string $filePath
     *
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    private function export(array $data, array $header, string $filePath): void
    {
        $writer = WriterFactory::create(Type::XLSX);
        $writer
            ->openToFile($filePath)
            ->addRow($header)
            ->addRows($data)
            ->close();

        die;
    }
}
