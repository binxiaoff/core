<?php

namespace Unilend\Controller\Endpoint;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Service\RiskDataMonitoring\EulerHermesManager;
use Unilend\Entity\{Companies, Settings};

class RiskDataMonitoringController extends Controller
{
    const AUTHENTICATION_ERROR = 'authentication_failed';
    const VALIDATION_ERROR     = 'validation_error';
    const SUCCESS              = 'success';

    /**
     * @Route("/ws/monitoring/euler-hermes/grade", name="risk_data_monitoring_euler_hermes_grade_legacy", methods={"POST"})
     * @Route("/ws/surveillance/euler-hermes/grade", name="risk_data_monitoring_euler_hermes_grade", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function eulerHermesGradeMonitoringAction(Request $request): JsonResponse
    {
        if (true !== ($response = $this->authenticateEulerHermes($request))) {
            return $response;
        }

        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Data was not send in valid format. It should be a JSON object', 400);
        }

        if (empty($data['siren']) && empty($data['singleInvoiceId'])) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Siren and singeInvoiceId are missing, there should be at least one', 401);
        }

        if (true !== ($response = $this->checkSiren($data['siren']))) {
            return $response;
        }

        $riskDataMonitoringEulerHermesManager= $this->get('unilend.service.risk_data_euler_hermes_manager');
        try {
            $riskDataMonitoringEulerHermesManager->saveEulerHermesGradeMonitoringEvent($data['siren']);
        } catch (\Exception $exception) {
            $this->container->get('logger')->warning('Euler Hermes monitoring event for siren ' . $data['siren'] . ' event could not be saved. Exception: ' . $exception->getMessage(), [
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'siren'    => $data['siren']
            ]);
        }

        return $this->endpointFeedback(self::SUCCESS, 'Grade change has been saved', 201);
    }

    /**
     * @Route("/ws/monitoring/euler-hermes/grade/end", name="risk_data_monitoring_euler_hermes_grade_end_legacy", methods={"PUT"})
     * @Route("/ws/surveillance/euler-hermes/grade/end", name="risk_data_monitoring_euler_hermes_grade_end", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function eulerHermesGradeMonitoringEndAction(Request $request): JsonResponse
    {
        if (true !== ($response = $this->authenticateEulerHermes($request))) {
            return $response;
        }

        $data = json_decode($request->getContent(), true);
        if (null === $data) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Data was not send in valid format. It should be a JSON object', 400);
        }

        if (true !== ($response = $this->checkSiren($data['siren']))) {
            return $response;
        }

        $riskDataMonitoringCycleManager = $this->get('unilend.service.risk_data_monitoring_cycle_manager');
        try {
            $riskDataMonitoringCycleManager->saveEndOfMonitoringPeriodNotification($data['siren'], EulerHermesManager::PROVIDER_NAME);
        } catch (\Exception $exception) {
            $this->container->get('logger')->warning('Euler Hermes monitoring end for siren ' . $data['siren'] . ' event could not be saved. Exception: ' . $exception->getMessage(), [
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'siren'    => $data['siren']
            ]);
        }

        return $this->endpointFeedback(self::SUCCESS, 'End of monitoring period saved', 200);
    }

    /**
     * @param string $type
     * @param string $message
     * @param int    $status
     *
     * @return JsonResponse
     */
    private function endpointFeedback(string $type, string $message, int $status): JsonResponse
    {

        $response =  new JsonResponse(['type' => $type, 'message' => $message], $status);

        if (self::SUCCESS !== $type) {
            $response->headers->set('Content-Type', 'application/problem+json');
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return bool|JsonResponse
     */
    private function authenticateEulerHermes(Request $request)
    {
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $settingsRepository = $entityManager->getRepository(Settings::class);

        if ($this->getParameter('kernel.environment') === 'prod') {
            $authorizedIpsSetting = $settingsRepository->findOneBy(['type' => 'Euler Hermes Monitoring production ips']);
        } else {
            $authorizedIpsSetting = $settingsRepository->findOneBy(['type' => 'Euler Hermes Monitoring testing ips']);
        }

        $authorizedIps = json_decode($authorizedIpsSetting->getValue(), true);
        // For local endpoint testing purposes
        $authorizedIps = array_merge($authorizedIps, ['192.168.110.12', '192.168.1.196']);

        if (false === in_array($request->getClientIp(), $authorizedIps)) {
            return $this->endpointFeedback(self::AUTHENTICATION_ERROR, 'Your Ip address is not authorized', 403);
        }

        $authentication = $request->headers->get('auth');
        $tokenSetting   = $settingsRepository->findOneBy(['type' => 'Euler Hermes Monitoring Token']);

        if (empty($authentication)) {
            return $this->endpointFeedback(self::AUTHENTICATION_ERROR, 'token missing', 401);
        }

        if ($tokenSetting->getValue() !== $authentication) {
            return $this->endpointFeedback(self::AUTHENTICATION_ERROR, 'wrong token', 401);
        }

        return true;
    }

    /**
     * @param $siren
     *
     * @return bool|JsonResponse
     */
    private function checkSiren($siren)
    {
        if (empty($siren)) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Siren is missing', 404);
        }

        if (1 !== preg_match('/^[0-9]*$/', $siren)) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Siren format is not valid', 404);
        }

        if (null === $this->get('doctrine.orm.entity_manager')->getRepository(Companies::class)->findOneBy(['siren' => $siren])) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Siren ' . $siren . ' is unknown to database', 404);
        }

        $riskDataMonitoringManager = $this->get('unilend.service.risk_data_monitoring_manager');

        if (false === $riskDataMonitoringManager->isSirenMonitored($siren, EulerHermesManager::PROVIDER_NAME)) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Siren ' . $siren . ' is not actively monitored', 404);
        }

        return true;
    }
}
