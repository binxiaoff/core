<?php

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager;
use Unilend\Bundle\CoreBusinessBundle\Service\BulkCompanyCheckManager;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectCloseOutNettingManager;

class projetsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_RISK);

        $this->menu_admin = 'emprunteurs';
    }

    public function _depot_liste()
    {
        /** @var BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');
        /** @var BulkCompanyCheckManager $bulkCompanyCheckManager */
        $bulkCompanyCheckManager = $this->get('unilend.service.eligibility.bulk_company_check_manager');

        if ($userManager->isGrantedRisk($this->userEntity)) {
            $success = '';
            $error   = '';
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $this->request->files->get('siren_list');

            if (false === empty($uploadedFile)) {
                $uploadDir = $bulkCompanyCheckManager->getProjectCreationInputPendingDir();
                try {
                    $bulkCompanyCheckManager->uploadFile($uploadDir, $uploadedFile, $this->userEntity);
                    $success = 'Le fichier a été pris en compte. Une notification vous sera envoyé dès qu\'il sera traité';
                } catch (\Exception $exception) {
                    /** @var LoggerInterface $logger */
                    $logger = $this->get('logger');
                    $logger->error(
                        'Could not upload the file into ' . $uploadDir . ' Error: ' . $exception->getMessage(),
                        ['method', __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                    );
                    $error = 'Le fichier n\'a pas été pris en compte. Veuillez rééssayer ou contacter l\'équipe technique.';
                }
            }
            $this->render(null, ['success' => $success, 'error' => $error]);
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    public function _projets_a_dechoir()
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if ($userManager->isGrantedRisk($this->userEntity)) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var DebtCollectionMissionManager $debtCollectionManager */
            $debtCollectionManager = $this->get('unilend.service.debt_collection_mission_manager');
            /** @var \Monolog\Logger $logger */
            $logger            = $this->get('logger');
            $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
            $error             = null;
            $data              = [];

            try {
                $projectsToDeclineSoon = $projectRepository->getProjectsWithUpcomingCloseOutNettingDate(15);

                foreach ($projectsToDeclineSoon as $projectRow) {
                    try {
                        $project = $projectRepository->find($projectRow['id_project']);

                        if ($debtCollectionManager->isDebtCollectionFeeDueToBorrower($project)) {
                            $interval = ProjectCloseOutNettingManager::OVERDUE_LIMIT_DAYS_SECOND_GENERATION_LOANS;
                        } else {
                            $interval = ProjectCloseOutNettingManager::OVERDUE_LIMIT_DAYS_FIRST_GENERATION_LOANS;
                        }
                        $closeOutNettingLimitDate = (new \DateTime($projectRow['last_repayment_date']))->modify('+' . $interval . ' days');
                        $daysInterval             = (new \DateTime())->diff($closeOutNettingLimitDate)->days;

                        $data[] = [
                            $project->getTitle(),
                            $project->getIdCompany()->getName(),
                            $daysInterval === 0 ? 'Aujourd\'hui' : ($daysInterval < 0 ? 'J' : 'J+') . $daysInterval,
                            (new \DateTime($projectRow['funding_date']))->format('d/m/Y'),
                            $projectRepository->getFundedProjectsBelongingToTheSameCompany($project->getIdProject(), $project->getIdCompany()->getSiren()),
                            $project->getIdProject()
                        ];
                    } catch (\Exception $exception) {
                        $logger->warning(
                            'Could not get the project details. Error: ' . $exception->getMessage(),
                            ['method' => __METHOD__, 'id_project' => $projectRow['id_project'], 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                    }
                }
            } catch (\Exception $exception) {
                $error = 'Impossible de charger la liste des dossiers';
                $logger->warning(
                    'Could not load the projects to decline. Error: ' . $exception->getMessage(),
                    ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
            echo json_encode(['data' => $data, 'error' => $error]);
            die;
        }
    }
}
