<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRating;

class RiskDataMonitoringController extends Controller
{
    const AUTHENTICATION_ERROR = 'authentication_failed';
    const VALIDATION_ERROR     = 'validation_error';
    const SUCCESS              = 'success';

    /**
     * @Route("/ws/monitoring/euler-hermes/grade", name="risk_data_monitoring_euler_hermes_grade")
     * @Method("POST")
     *
     * @return Response
     */
    public function eulerHermesGradeMonitoringAction(Request $request)
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

        $riskDataMonitoringManager = $this->get('unilend.service.risk_data_monitoring_manager');
        $riskDataMonitoringManager->saveEulerHermesGradeMonitoringEvent($data['siren']);

        return $this->endpointFeedback(self::SUCCESS, 'Grade change has been saved', 201);
    }

    /**
     * @Route("/ws/monitoring/euler-hermes/grade/end", name="risk_data_monitoring_euler_hermes_grade_end")
     * @Method("PUT")
     *
     * @return Response
     */
    public function eulerHermesGradeMonitoringEndAction(Request $request)
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

        $riskDataMonitoringManager = $this->get('unilend.service.risk_data_monitoring_manager');
        $riskDataMonitoringManager->saveEndOfMonitoringPeriodNotification($data['siren'], CompanyRating::TYPE_EULER_HERMES_GRADE);

        return $this->endpointFeedback(self::SUCCESS, 'End of monitoring period saved', 200);
    }

    /**
     * @param string $type
     * @param string $message
     * @param int    $status
     *
     * @return JsonResponse
     */
    private function endpointFeedback($type, $message, $status) {

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
        $settingsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings');

        if ($this->getParameter('kernel.environment') === 'prod') {
            $authorizedIpsSetting = $settingsRepository->findOneBy(['type' => 'Euler Hermes Monitoring production ips']);
        } else {
            $authorizedIpsSetting = $settingsRepository->findOneBy(['type' => 'Euler Hermes Monitoring testing ips']);
        }

        $authorizedIps = json_decode($authorizedIpsSetting->getValue(), true);
        // For local endpoint testing purposes
        $authorizedIps = array_merge($authorizedIps, ['192.168.110.12']);

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
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Siren ' . $siren . ' is missing', 404);
        }

        if (1 !== preg_match('/^[0-9]*$/', $siren)) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Siren format is not valid', 404);
        }

        if (null === $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['siren' => $siren])) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Siren  ' . $siren . ' is unknown to database', 404);
        }

        $riskDataMonitoringManager = $this->get('unilend.service.risk_data_monitoring_manager');

        if (false === $riskDataMonitoringManager->isSirenMonitored($siren, CompanyRating::TYPE_EULER_HERMES_GRADE)) {
            return $this->endpointFeedback(self::VALIDATION_ERROR, 'Siren  ' . $siren . ' is not actively monitored', 404);
        }

        return true;
    }
}
