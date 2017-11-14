<?php

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRating;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatusHistory;

class societeController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();
        $this->users->checkAccess();
    }

    public function _notation()
    {
        if (
            false === isset($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
        ) {
            header('Location: ' . $this->lurl);
            return;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->params[0]);

        if (null === $company) {
            header('Location: ' . $this->lurl . '/dashboard');
            return;
        }

        $companyRatingHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyRatingHistory');
        $ratings                        = [
            CompanyRating::TYPE_EULER_HERMES_GRADE,
            CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT,
            CompanyRating::TYPE_ALTARES_SCORE_20,
            CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_100,
            CompanyRating::TYPE_INFOLEGALE_SCORE,
            CompanyRating::TYPE_XERFI_RISK_SCORE,
            CompanyRating::TYPE_UNILEND_XERFI_RISK
        ];
        $companyRatings                 = $companyRatingHistoryRepository->getRatingsSirenByDate($company->getSiren(), $ratings);
        $formattedRatings               = $this->formatRatings($companyRatings);
        $dates                          = $formattedRatings['dates'];
        unset($formattedRatings['dates']);

        $projectsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $allProjects        = $projectsRepository->findProjectsBySiren($company->getSiren());
        $formattedProjects  = $this->formatProjectData($allProjects);

        $ongoingProjectStatus = [
            ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION,
            ProjectsStatus::SIMULATION,
            ProjectsStatus::INCOMPLETE_REQUEST,
            ProjectsStatus::COMPLETE_REQUEST,
            ProjectsStatus::POSTPONED,
            ProjectsStatus::COMMERCIAL_REVIEW,
            ProjectsStatus::COMMERCIAL_REJECTION,
            ProjectsStatus::PENDING_ANALYSIS,
            ProjectsStatus::ANALYSIS_REVIEW,
            ProjectsStatus::COMITY_REVIEW,
            ProjectsStatus::SUSPENSIVE_CONDITIONS,
            ProjectsStatus::PREP_FUNDING,
            ProjectsStatus::A_FUNDER,
            ProjectsStatus::AUTO_BID_PLACED,
            ProjectsStatus::EN_FUNDING,
            ProjectsStatus::BID_TERMINATED,
            ProjectsStatus::FUNDE,
            ProjectsStatus::FUNDING_KO,
            ProjectsStatus::PRET_REFUSE,
            ProjectsStatus::REMBOURSEMENT,
            ProjectsStatus::LOSS
        ];

        $this->render(null, [
            'company'                 => $company,
            'numberOngoingProjects'   => $projectsRepository->getCountProjectsByStatusAndSiren($ongoingProjectStatus, $company->getSiren()),
            'numberRepaidProjects'    => $projectsRepository->getCountProjectsByStatusAndSiren([
                ProjectsStatus::REMBOURSE,
                ProjectsStatus::REMBOURSEMENT_ANTICIPE
            ], $company->getSiren()),
            'numberAbandonedProjects' => $projectsRepository->getCountProjectsByStatusAndSiren([ProjectsStatus::ABANDONED], $company->getSiren()),
            'numberRejectedProjects'  => $projectsRepository->getCountProjectsByStatusAndSiren([
                ProjectsStatus::NOT_ELIGIBLE,
                ProjectsStatus::COMITY_REJECTION,
                ProjectsStatus::ANALYSIS_REJECTION,
                ProjectsStatus::COMMERCIAL_REJECTION
            ], $company->getSiren()),
            'ratings'                 => $formattedRatings,
            'dates'                   => $dates,
            'projects'                => $formattedProjects,
            'remainingDueCapital'     => $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getRemainingDueCapitalForProjects(new \DateTime('NOW'), array_column($formattedProjects, 'id'))
        ]);
    }

    /**
     * @param array $ratings
     *
     * @return array
     */
    private function formatRatings(array $ratings)
    {
        $formattedRating = [];
        $previousIndex   = 0;
        foreach ($ratings as $index => $rating) {
            if (
                $index > 0
                && $ratings[$previousIndex][CompanyRating::TYPE_EULER_HERMES_GRADE] === $ratings[$index][CompanyRating::TYPE_EULER_HERMES_GRADE]
                && $ratings[$previousIndex][CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT ] === $ratings[$index][CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT]
                && $ratings[$previousIndex][CompanyRating::TYPE_ALTARES_SCORE_20] === $ratings[$index][CompanyRating::TYPE_ALTARES_SCORE_20]
                && $ratings[$previousIndex][CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_100] === $ratings[$index][CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_100]
                && $ratings[$previousIndex][CompanyRating::TYPE_INFOLEGALE_SCORE] === $ratings[$index][CompanyRating::TYPE_INFOLEGALE_SCORE]
                && $ratings[$previousIndex][CompanyRating::TYPE_XERFI_RISK_SCORE] === $ratings[$index][CompanyRating::TYPE_XERFI_RISK_SCORE]
                && $ratings[$previousIndex][CompanyRating::TYPE_UNILEND_XERFI_RISK] === $ratings[$index][CompanyRating::TYPE_UNILEND_XERFI_RISK]
            ) {
                continue;
            }

            $nextIndex = $index + 1;
            if ($index > 0 && $ratings[$nextIndex]['date'] == $ratings[$index]['date']) {
                continue;
            }

            $date = $rating['date'];
            unset($rating['date']);

            foreach ($rating as $type => $value) {
                $data                                = ['value' => $value, 'date' => $date];
                $formattedRating[$type][$date]       = array_merge($data, $this->checkRatingChangeClass($ratings[$previousIndex][$type], $value, $type));
                $formattedRating['dates'][$date]     = $date;
                $previousDate                        = \DateTime::createFromFormat('Y-m-d', $ratings[$previousIndex]['date']);
                $currentDate                         = \DateTime::createFromFormat('Y-m-d', $ratings[$index]['date']);
                $interval                            = $previousDate->diff($currentDate);
                $formattedRating['intervals'][$date] = $interval;
            }
            $previousIndex = $index;
        }

        return $formattedRating;
    }

    /**
     * @param string|int|null $firstRating
     * @param string|int|null $secondRating
     * @param string          $ratingType
     *
     * @return array
     */
    private function checkRatingChangeClass($firstRating, $secondRating, $ratingType)
    {
        if ($firstRating == $secondRating) {
            $change = [
                'change'    => false,
                'class'     => '',
                'direction' => ''
            ];
        } else {
            switch ($ratingType) {
                case CompanyRating::TYPE_ALTARES_SCORE_20:
                case CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_100:
                case CompanyRating::TYPE_INFOLEGALE_SCORE:
                    if (empty($firstRating) || empty($secondRating)) {
                        $change = [
                            'change'    => true,
                            'class'     => 'warning',
                            'direction' => 'up'
                        ];
                    } elseif ($firstRating > $secondRating) {
                        $change = [
                            'change'    => true,
                            'class'     => 'error',
                            'direction' => 'down'
                        ];
                    } else {
                        $change = [
                            'change'    => true,
                            'class'     => 'success',
                            'direction' => 'up'
                        ];
                    }
                    break;
                case CompanyRating::TYPE_EULER_HERMES_GRADE:
                case CompanyRating::TYPE_XERFI_RISK_SCORE:
                    if (empty($firstRating) || empty($secondRating)) {
                        $change = [
                            'change'    => true,
                            'class'     => 'warning',
                            'direction' => 'down'
                        ];
                    } elseif ($firstRating > $secondRating) {
                        $change = [
                            'change'    => true,
                            'class'     => 'success',
                            'direction' => 'down'
                        ];
                    } else {
                        $change = [
                            'change'    => true,
                            'class'     => 'error',
                            'direction' => 'up'
                        ];
                    }
                    break;
                case CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT:
                    $firstValue  = $this->changeTrafficLightColorToNumericValue($firstRating);
                    $secondValue = $this->changeTrafficLightColorToNumericValue($secondRating);
                    if (0 === $firstValue || 0 === $secondValue) {
                        $change = [
                            'change'    => true,
                            'class'     => 'warning',
                            'direction' => 'up'
                        ];
                    } elseif ($firstValue > $secondValue) {
                        $change = [
                            'change'    => true,
                            'class'     => 'error',
                            'direction' => 'down'
                        ];
                    } else {
                        $change = [
                            'change'    => true,
                            'class'     => 'success',
                            'direction' => 'up'
                        ];
                    }
                    break;
                default:
                    $change = [
                        'change'    => true,
                        'class'     => 'warning',
                        'direction' => 'up'
                    ];
                    break;
            }
        }

        return $change;
    }

    /**
     * @param string $color
     *
     * @return int
     */
    private function changeTrafficLightColorToNumericValue($color)
    {
        switch($color){
            case 'Green':
                return 1;
            case 'Yellow':
                return 2;
            case 'Red':
                return 3;
            case 'Black':
                return 4;
            default:
                return 0;
        }
    }

    /**
     * @param array $projects
     *
     * @return array
     */
    private function formatProjectData(array $projects)
    {
        $projectDetails = [];
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        /** @var Projects $project */
        foreach($projects as $index => $project) {
            $projectStatus                = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')->findBy(['idProject' => $project->getIdProject()]);
            $start                        = $project->getAdded();
            $projectNeed                  = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectNeed')->find($project->getIdProjectNeed());
            $projectDetails[$index]['id'] = $project->getIdProject();

            /** @var ProjectsStatusHistory $status */
            foreach ($projectStatus as $status){
                $projectStatus                        = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->find($status->getIdProjectStatus());
                $color                                = $this->getColorForProjectStatus($projectStatus->getStatus());
                $data                                 = [
                    'start'  => $start,
                    'end'    => $status->getAdded(),
                    'color'  => $color,
                    'label'  => $projectStatus->getLabel(),
                    'type'   => null === $projectNeed ? 'NA' : $projectNeed->getLabel(),
                    'amount' => $project->getAmount()
                ];
                $projectDetails[$index]['statuses'][] = $data;
                $start                                = $status->getAdded();
            }
        }

        return $projectDetails;
    }

    /**
     * @param string $projectStatus
     *
     * @return string
     */
    private function getColorForProjectStatus($projectStatus)
    {
        switch($projectStatus){
            case ProjectsStatus::COMMERCIAL_REJECTION:
            case ProjectsStatus::ANALYSIS_REJECTION:
            case ProjectsStatus::COMITY_REJECTION:
            case ProjectsStatus::NOT_ELIGIBLE:
                return '#b6babe';
            case ProjectsStatus::INCOMPLETE_REQUEST:
                return '#d0f5d0';
            case ProjectsStatus::COMPLETE_REQUEST:
                return '#aae3c9';
            case ProjectsStatus::ABANDONED:
            case ProjectsStatus::POSTPONED:
                return '#eccd81';
            case ProjectsStatus::COMMERCIAL_REVIEW:
                return '#98d9d4';
            case ProjectsStatus::PENDING_ANALYSIS:
            case ProjectsStatus::ANALYSIS_REVIEW:
                return '#91c8d9';
            case ProjectsStatus::COMITY_REVIEW:
                return '#80b5d9';
            case ProjectsStatus::SUSPENSIVE_CONDITIONS:
                return '#b995c7';
            case ProjectsStatus::PREP_FUNDING:
            case ProjectsStatus::A_FUNDER:
            case ProjectsStatus::AUTO_BID_PLACED:
            case ProjectsStatus::EN_FUNDING:
            case ProjectsStatus::BID_TERMINATED:
            case ProjectsStatus::FUNDE:
                return '#6ea8dc';
            case ProjectsStatus::FUNDING_KO:
            case ProjectsStatus::PRET_REFUSE:
                return '#f2980c';
            case ProjectsStatus::REMBOURSEMENT:
                return '#1b88db';
            case ProjectsStatus::REMBOURSE:
            case ProjectsStatus::REMBOURSEMENT_ANTICIPE:
                return '#4fa8b0';
            case ProjectsStatus::PROBLEME:
                break;
            case ProjectsStatus::LOSS:
                return '#787679';
            default:
                break;
        }

        return '';
    }

}
