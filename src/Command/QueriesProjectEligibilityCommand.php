<?php

namespace Unilend\Command;

use Box\Spout\{
    Common\Type, Writer\WriterFactory
};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};
use Unilend\Entity\{BorrowingMotive, CompanyRating, ProjectEligibilityAssessment, Projects, ProjectsNotes};

class QueriesProjectEligibilityCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('queries:project_eligibility')
            ->setDescription('Extract eligibility of projects');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $filePath = $this->getContainer()->getParameter('path.protected') . '/queries/' . 'projects_eligibility.xlsx';
        $header   = $header = [
            'ID projet',
            'Date de dépôt',
            'Raison sociale',
            'SIREN',
            'Date de creation',
            'Source',
            'Partenaire',
            'Motif exprimé',
            'Montant',
            'Durée',
            'Prescore',
            'Score Altares',
            'TrafficLight Euler',
            'Grade Euler',
            'Score Infolegale',
            'CA',
            'FP',
            'REX',
            'RCS',
            'NAF',
            'Statut projet',
            'Tronc commun'
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
        $entityManager             = $this->getContainer()->get('doctrine.orm.entity_manager');
        $assessmentRepository      = $entityManager->getRepository(ProjectEligibilityAssessment::class);
        $companyRatingRepository   = $entityManager->getRepository(CompanyRating::class);
        $borrowingMotiveRepository = $entityManager->getRepository(BorrowingMotive::class);
        $projectNotesRepository    = $entityManager->getRepository(ProjectsNotes::class);
        $indexedProjectStatus      = $this->getContainer()->get('unilend.service.project_status_manager')->getIndexedProjectStatus();
        $ratingTypes               = [
            CompanyRating::TYPE_ALTARES_SCORE_20,
            CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT,
            CompanyRating::TYPE_EULER_HERMES_GRADE,
            CompanyRating::TYPE_INFOLEGALE_SCORE
        ];

        $extraction        = [];
        $evaluatedProjects = $assessmentRepository->getEvaluatedProjects();

        /** @var Projects $project */
        foreach ($evaluatedProjects as $project) {
            $company     = $project->getIdCompany();
            $motivation  = $project->getIdBorrowingMotive() ? $borrowingMotiveRepository->find($project->getIdBorrowingMotive()) : null;
            $projectNote = $projectNotesRepository->findOneBy(['idProject' => $project]);
            $ratings     = $companyRatingRepository->getRatingsByTypeAndHistory($project->getIdCompanyRatingHistory(), $ratingTypes);

            $source = '';
            if ($company->getIdClientOwner() && false === $project->getCreateBo() && false === empty($company->getIdClientOwner()->getSource())) {
                $source = $company->getIdClientOwner()->getSource();
            }

            $partner = '';
            if ($project->getIdPartner() && $project->getIdPartner()->getIdCompany()) {
                $partner = $project->getIdPartner()->getIdCompany()->getName();
            }

            $projectEligibilityAssessment = $assessmentRepository->findOneBy(
                ['idProject' => $project],
                ['added' => 'DESC', 'id' => 'DESC']
            );

            $extraction[] = [
                'id projet'        => $project->getIdProject(),
                'added'            => $project->getAdded()->format('d/m/Y'),
                'company_name'     => $company->getName(),
                'siren'            => $company->getSiren(),
                'date_creation'    => $company->getDateCreation() ? $company->getDateCreation()->format('d/m/Y') : '',
                'source'           => $source,
                'partner'          => $partner,
                'motivation'       => $motivation ? $motivation->getMotive() : '',
                'amount'           => $project->getAmount(),
                'duration'         => $project->getPeriod(),
                'prescore'         => $projectNote && $projectNote->getPreScoring() ? $projectNote->getPreScoring() : 'Pas de donnée',
                'score_altares'    => $ratings[CompanyRating::TYPE_ALTARES_SCORE_20] ?? 'Pas de donnée',
                'traffic_light'    => $ratings[CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT] ?? 'Pas de donnée',
                'grade_euler'      => $ratings[CompanyRating::TYPE_EULER_HERMES_GRADE] ?? 'Pas de donnée',
                'score_infolegale' => $ratings[CompanyRating::TYPE_INFOLEGALE_SCORE] ?? 'Pas de donnée',
                'turnover'         => $project->getCaDeclaraClient(),
                'own_funds'        => $project->getFondsPropresDeclaraClient(),
                'operation_income' => $project->getResultatExploitationDeclaraClient(),
                'is_rcs'           => empty($company->getRcs()) ? 'Non' : 'Oui',
                'naf'              => $company->getCodeNaf(),
                'status'           => isset($indexedProjectStatus[$project->getStatus()]) ? $indexedProjectStatus[$project->getStatus()] : '',
                'common_check'     => $projectEligibilityAssessment->getStatus() ? 'OK' : $projectEligibilityAssessment->getIdRule()->getLabel()
            ];
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
    }
}
