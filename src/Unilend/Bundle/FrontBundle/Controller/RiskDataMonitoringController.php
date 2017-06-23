<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRating;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;

class RiskDataMonitoringController extends Controller
{
    /**
     * @Route("/ws/monitoring/euler-hermes/grade", name="risk_data_monitoring_euler_hermes_grade")
     * @Method("POST")
     *
     * @return Response
     */
    public function eulerHermesGradeMonitoringAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Data was not send in valid format. It should be a JSON object and contain token, siren and/or singleInvoiceId'], 422);
        }

        if (empty($data['token'])) {
            return new JsonResponse(['type' => 'Authentication Failed', 'error' => 'token missing'], 401);
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var Settings $tokenSetting */
        $tokenSetting = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Euler Hermes Monitoring Token']);

        if ($tokenSetting->getValue() !== $data['token']) {
            return new JsonResponse(['type' => 'Authentication Failed', 'error' => 'wrong token'], 401);
        }

        if (empty($data['siren']) && empty($data['singleInvoiceId'])) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Siren and singeInvoiceId are missing, there should be at least one'], 401);
        }

        if (false === empty($data['siren']) && 1 !== preg_match('/^[0-9]*$/', $data['siren'])) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Siren format is not valid'], 404);
        }

        if (null === $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['siren' => $data['siren']])) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Siren is unknown to database'], 404);
        }

        $riskDataMonitoringManager = $this->get('unilend.service.ws_client.risk_data_monitoring_manager');
        $riskDataMonitoring        = $riskDataMonitoringManager->getMonitoring(CompanyRating::TYPE_EULER_HERMES_GRADE, $data['siren']);

        if (null !== $riskDataMonitoring) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'This siren is not monitored'], 404);
        }

        if (false === $riskDataMonitoring->isOngoing()) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'This siren is no longer monitored'], 404);
        }

        $riskDataMonitoringManager->saveEulerHermesGradeMonitoringEvent($data['siren']);

        return new Response(['type' => 'Success', 'message' => 'Grade change has been saved'], 200);
    }

    /**
     * @Route("/ws/monitoring/euler-hermes/grade/end", name="risk_data_monitoring_euler_hermes_grade_end")
     * @Method("PUT")
     *
     * @return Response
     */
    public function eulerHermesGradeMonitoringEndAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Data was not send in valid format. It should be a JSON object and contain token and siren'], 422);
        }

        if (empty($data['token'])) {
            return new JsonResponse(['type' => 'Authentication Failed', 'error' => 'token missing'], 401);
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var Settings $tokenSetting */
        $tokenSetting = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Euler Hermes Monitoring Token']);

        if ($tokenSetting->getValue() !== $data['token']) {
            return new JsonResponse(['type' => 'Authentication Failed', 'error' => 'Wrong token'], 401);
        }

        if (empty($data['siren'])) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Siren is missing'], 401);
        }

        if (1 !== preg_match('/^[0-9]*$/', $data['siren'])) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Siren format is not valid'], 404);
        }

        if (null === $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['siren' => $data['siren']])) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Siren is unknown to database'], 404);
        }

        $riskDataMonitoringManager = $this->get('unilend.service.ws_client.risk_data_monitoring_manager');

        try {
            $riskDataMonitoringManager->stopMonitoringPeriod(CompanyRating::TYPE_EULER_HERMES_GRADE, $data['siren']);
        } catch (\Exception $exception) {
            return new JsonResponse(['type' => 'Update Error', 'message' => $exception->getMessage()]);
        }

        return new Response(['type' => 'Success', 'message' => 'End of monitoring period saved'], 200);
    }
}
