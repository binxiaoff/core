<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
            return new JsonResponse('Data was not send in valid format. It should be a JSON object and contain token, siren and/or singleInvoiceId', 422, [], true);
        }

        if (empty($data['token'])) {
            return new JsonResponse(['type' => 'Authentication Failed', 'error' => 'token missing'], 401, [], true);
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var Settings $tokenSetting */
        $tokenSetting = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => "Euler Hermes Monitoring Token"]);

        if ($tokenSetting->getValue() !== $data['token']) {
            return new JsonResponse(['type' => 'Authentication Failed', 'error' => 'wrong token'], 401, [], true);
        }

        if (empty($data['siren']) && empty($data['singleInvoiceId'])) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Siren and singeInvoiceId are missing, there should be at least one'], 401, [], true);
        }

        if (false === empty($data['siren']) && 1 !== preg_match('/^[0-9]*$/', $data['siren'])) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Siren format is not valid'], 404, [], true);
        }

        if (null === $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['siren' => $data['siren']])) {
            return new JsonResponse(['type' => 'Validation error', 'error' => 'Siren is unknown to database'], 404, [], true);
        }

        return new Response('Grade change in Siren successfully saved', 200);
    }
}
