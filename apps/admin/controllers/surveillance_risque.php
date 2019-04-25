<?php

use Unilend\Entity\{Operation, ProjectsStatus, RiskDataMonitoring, Zones};
use Unilend\Repository\RiskDataMonitoringRepository;
use Unilend\Entity\External\Euler\CompanyRating as EulerCompanyRating;

class surveillance_risqueController extends bootstrap
{

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        /** @var IntlDateFormatter dateFormatter */
        $this->dateFormatter = $this->get('date_formatter');
        $this->dateFormatter->setPattern('d MMM y');
        $this->currencyFormatter = $this->get('currency_formatter');
        $this->translator        = $this->get('translator');

        $start = new \DateTime('2 months ago');

        /** @var RiskDataMonitoringRepository $riskDataMonitoringRepository */
        $riskDataMonitoringRepository = $this->get('doctrine.orm.entity_manager')->getRepository(RiskDataMonitoring::class);

        try {
            $this->companyRatingEvents = $this->formatEvents($riskDataMonitoringRepository->getCompanyRatingEvents($start));
        } catch (\Exception $exception) {
            $this->get('logger')->error('admin:surveillance_risque: Could not get list of company rating events. Exception message : ' . $exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
            $this->companyRatingEvents = [];
        }

        try {
            $this->eligibilityEvents = $this->formatEvents($riskDataMonitoringRepository->getEligibilityEvents());
        } catch (\Exception $exception) {
            $this->get('logger')->error('admin:surveillance_risque: Could not get list of eligibility events. Exception message : ' . $exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
            $this->eligibilityEvents = [];
        }
    }

    /**
     * @param array $events
     *
     * @return array
     */
    private function formatEvents(array $events)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Repository\OperationRepository $operationRepository */
        $operationRepository              = $entityManager->getRepository(Operation::class);
        $activeStatus                     = [
            ProjectsStatus::SALES_TEAM_UPCOMING_STATUS,
            ProjectsStatus::SALES_TEAM,
            ProjectsStatus::RISK_TEAM,
            ProjectsStatus::STATUS_CONTRACTS_SIGNED,
            ProjectsStatus::STATUS_LOST
        ];
        $formattedEvents                  = [];

        foreach ($events as $event) {
            if (isset($event['value']) && isset($event['previous_value']) && $event['value'] === $event['previous_value']) {
                continue;
            }

            if (false === in_array($event['status'], [ProjectsStatus::STATUS_CANCELLED, ProjectsStatus::STATUS_CANCELLED, ProjectsStatus::STATUS_CANCELLED, ProjectsStatus::STATUS_CANCELLED])) {
                if (false === isset($formattedEvents[$event['siren']])) {
                    $formattedEvents[$event['siren']] = [
                        'label'       => $event['name'],
                        'count'       => 0,
                        'activeSiren' => in_array($event['status'], $activeStatus)
                    ];
                }

                if ($event['status'] >= ProjectsStatus::STATUS_CONTRACTS_SIGNED) {
                    $event['remainingDueCapital'] = $operationRepository->getRemainingDueCapitalForProjects(new \DateTime('NOW'), [$event['id_project']]);
                }

                if ($formattedEvents[$event['siren']]['activeSiren'] || in_array($event['status'], $activeStatus)) {
                    $formattedEvents[$event['siren']]['activeSiren'] = true;
                }

                if (empty($event['previous_value']) && false === is_numeric($event['value']) && EulerCompanyRating::GRADE_UNKNOWN !== $event['value']) {
                    $event['value'] = $this->get('unilend.service.project_status_manager')->getStatusReasonByLabel($event['value'], 'rejection');
                }

                $formattedEvents[$event['siren']]['count']++;
                $formattedEvents[$event['siren']]['events'][] = $event;
            }
        }

        return $formattedEvents;
    }
}
