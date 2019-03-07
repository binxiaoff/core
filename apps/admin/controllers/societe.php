<?php

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{CompanyRating, Projects, ProjectsStatus, ProjectsStatusHistory, Zones};
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerCompanyRating;

class societeController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();
        $this->users->checkAccess(Zones::ZONE_LABEL_RISK);

        $this->menu_admin = 'emprunteurs';
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
            CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_20,
            CompanyRating::TYPE_INFOLEGALE_SCORE,
            CompanyRating::TYPE_XERFI_RISK_SCORE,
            CompanyRating::TYPE_UNILEND_XERFI_RISK
        ];
        $companyRatings                 = $companyRatingHistoryRepository->getRatingsSirenByDate($company->getSiren(), $ratings);
        $formattedRatings               = $this->formatRatings($companyRatings);
        $dates                          = $formattedRatings['dates'];
        unset($formattedRatings['dates']);

        $projectsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $allProjects        = $projectsRepository->findBySiren($company->getSiren());
        $formattedProjects  = $this->formatProjectData($allProjects);

        $this->render(null, [
            'company'                   => $company,
            'numberOngoingProjects'     => $projectsRepository->getCountProjectsByStatusAndSiren(array_merge(ProjectsStatus::SALES_TEAM, ProjectsStatus::RISK_TEAM), $company->getSiren()),
            'numberRepaidProjects'      => $projectsRepository->getCountProjectsByStatusAndSiren([ProjectsStatus::STATUS_REPAID], $company->getSiren()),
            'numberProjectsInRepayment' => $projectsRepository->getCountProjectsByStatusAndSiren([ProjectsStatus::STATUS_REPAYMENT], $company->getSiren()),
            'numberAbandonedProjects'   => $projectsRepository->getCountProjectsByStatusAndSiren([ProjectsStatus::STATUS_CANCELLED], $company->getSiren()),
            'numberRejectedProjects'    => $projectsRepository->getCountProjectsByStatusAndSiren([ProjectsStatus::STATUS_CANCELLED], $company->getSiren()),
            'ratings'                   => $formattedRatings,
            'dates'                     => $dates,
            'projects'                  => $formattedProjects,
            'remainingDueCapital'       => $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getRemainingDueCapitalForProjects(new \DateTime('NOW'), array_column($formattedProjects, 'id'))
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
                && $ratings[$previousIndex][CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT] === $ratings[$index][CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT]
                && $ratings[$previousIndex][CompanyRating::TYPE_ALTARES_SCORE_20] === $ratings[$index][CompanyRating::TYPE_ALTARES_SCORE_20]
                && $ratings[$previousIndex][CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_20] === $ratings[$index][CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_20]
                && $ratings[$previousIndex][CompanyRating::TYPE_INFOLEGALE_SCORE] === $ratings[$index][CompanyRating::TYPE_INFOLEGALE_SCORE]
                && $ratings[$previousIndex][CompanyRating::TYPE_XERFI_RISK_SCORE] === $ratings[$index][CompanyRating::TYPE_XERFI_RISK_SCORE]
                && $ratings[$previousIndex][CompanyRating::TYPE_UNILEND_XERFI_RISK] === $ratings[$index][CompanyRating::TYPE_UNILEND_XERFI_RISK]
            ) {
                continue;
            }

            $nextIndex = $index + 1;
            if ($index > 0 && isset($ratings[$nextIndex]['date']) && $ratings[$nextIndex]['date'] == $ratings[$index]['date']) {
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
        if (CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT === $ratingType) {
            $firstRating  = $this->convertTrafficLightColorToNumericValue($firstRating);
            $secondRating = $this->convertTrafficLightColorToNumericValue($secondRating);
        }

        $change = [
            'change'    => false,
            'class'     => '',
            'direction' => ''
        ];

        if (null !== $firstRating && null !== $secondRating) {
            switch ($ratingType) {
                case CompanyRating::TYPE_ALTARES_SCORE_20:
                case CompanyRating::TYPE_ALTARES_SECTORAL_SCORE_20:
                case CompanyRating::TYPE_INFOLEGALE_SCORE:
                    if ($firstRating > $secondRating) {
                        $change = [
                            'change'    => true,
                            'class'     => 'error',
                            'direction' => 'down'
                        ];
                    }
                    if ($firstRating < $secondRating){
                        $change = [
                            'change'    => true,
                            'class'     => 'success',
                            'direction' => 'up'
                        ];
                    }
                    break;
                case CompanyRating::TYPE_EULER_HERMES_GRADE:
                case CompanyRating::TYPE_XERFI_RISK_SCORE:
                case CompanyRating::TYPE_EULER_HERMES_TRAFFIC_LIGHT:
                if ($firstRating > $secondRating) {
                        $change = [
                            'change'    => true,
                            'class'     => 'success',
                            'direction' => 'down'
                        ];
                    }
                    if ($firstRating < $secondRating){
                        $change = [
                            'change'    => true,
                            'class'     => 'error',
                            'direction' => 'up'
                        ];
                    }
                    break;
                default:
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
    private function convertTrafficLightColorToNumericValue($color)
    {
        switch ($color) {
            case EulerCompanyRating::COLOR_GREEN:
                return 1;
            case EulerCompanyRating::COLOR_YELLOW:
                return 2;
            case EulerCompanyRating::COLOR_RED:
                return 3;
            case EulerCompanyRating::COLOR_BLACK:
                return 4;
            default:
                return null;
        }
    }

    /**
     * @param array $projects
     *
     * @return array
     */
    private function formatProjectData(array $projects)
    {
        $translator = $this->get('translator');

        $projectDetails = [];
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $finalProjectStatus = [
            ProjectsStatus::STATUS_CANCELLED,
            ProjectsStatus::STATUS_REPAID,
            ProjectsStatus::STATUS_LOSS
        ];

        /** @var Projects $project */
        foreach ($projects as $index => $project) {
            $projectStatusHistory         = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')->findBy(['idProject' => $project->getIdProject()]);
            $projectNeed                  = empty($project->getIdProjectNeed()) ? null : $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectNeed')->find($project->getIdProjectNeed());
            $projectDetails[$index]['id'] = $project->getIdProject();

            /** @var ProjectsStatusHistory $status */
            foreach ($projectStatusHistory as $key => $status) {
                $nextKey       = $key + 1;
                $projectStatus = $status->getIdProjectStatus();
                $color         = $this->getColorForProjectStatus($projectStatus->getStatus());

                if (isset($projectStatusHistory[$nextKey])) {
                    $end = $projectStatusHistory[$nextKey]->getAdded();
                } else {
                    $end = in_array($projectStatus->getStatus(), $finalProjectStatus) ? $status->getAdded() : new \DateTime('NOW');
                }

                $projectDetails[$index]['statuses'][] = [
                    'start'  => $status->getAdded(),
                    'end'    => $end,
                    'color'  => $color,
                    'label'  => $projectStatus->getLabel(),
                    'type'   => null === $projectNeed ? 'non renseigné' : $projectNeed->getLabel(),
                    'motive' => empty($project->getIdBorrowingMotive()) ? 'non renseigné' : $translator->trans('borrowing-motive_motive-' . $project->getIdBorrowingMotive()),
                    'amount' => $project->getAmount()
                ];
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
        switch ($projectStatus) {
            case ProjectsStatus::STATUS_CANCELLED:
                return '#b6babe';
            case ProjectsStatus::STATUS_REQUEST:
                return '#d0f5d0';
            case ProjectsStatus::STATUS_REVIEW:
                return '#aae3c9';
            case ProjectsStatus::STATUS_ONLINE:
            case ProjectsStatus::STATUS_FUNDED:
                return '#6ea8dc';
            case ProjectsStatus::STATUS_REPAYMENT:
                return '#1b88db';
            case ProjectsStatus::STATUS_REPAID:
                return '#4fa8b0';
            case ProjectsStatus::STATUS_LOSS:
                break;
            default:
                break;
        }

        return '';
    }

}
