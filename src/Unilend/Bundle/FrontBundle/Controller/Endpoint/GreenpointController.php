<?php

namespace Unilend\Bundle\FrontBundle\Controller\Endpoint;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Service\GreenpointManager;

class GreenpointController extends Controller
{
    /** @var LoggerInterface  */
    private $logger;
    /** @var EntityManager */
    private $entityManager;
    /** @var GreenpointManager */
    private $greenpointManager;

    public function __construct(Loggerinterface $logger, EntityManager $entityManager, GreenpointManager $greenpointManager)
    {
        $this->logger            = $logger;
        $this->entityManager     = $entityManager;
        $this->greenpointManager = $greenpointManager;
    }

    /**
     * @Route("/ws/kyc", name="greenpoint_asynchronous_feedback", methods={"POST"})
     */
    public function greenpointAsynchronousFeedbackAction(Request $request): Response
    {
        if (true !== ($response = $this->checkIp($request))) {
            return $response;
        }

        $document = $request->request->getInt('document');
        $file     = $request->request->getInt('dossier');

        if (empty($document) || empty($file)) {
            return new Response('Data incomplete. Client ID or document ID missing', 400);
        }

        $type = $request->request->getInt('type');
        if (false === in_array($type, [GreenpointManager::TYPE_HOUSING_CERTIFICATE, GreenpointManager::TYPE_HOUSING_CERTIFICATE, GreenpointManager::TYPE_RIB])) {
            $this->logger->error(
                'Greenpoint returned feedback for unknown type', [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'type'     => $type
            ]);

            return new Response('Unexpected Type', 404);
        }

        $attachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->find($document);
        if (null === $attachment) {
            $this->logger->error(
                'Attachement not found', [
                'class'         => __CLASS__,
                'function'      => __FUNCTION__,
                'attachment_id' => $request->request->getInt('document')
            ]);
            return new Response('Data incomplete. Document ID unknown', 400);
        }

        try {
            $this->greenpointManager->handleAsynchronousFeedback($type, $attachment, $request->request->all());
            //$this->updateGreenPointKyc($request->request->getInt('dossier'));

        } catch (\Exception $exception) {
            $this->logger->error(
                'An error occurred during asynchronous greenpoint feedback. Message: ' . $exception->getMessage(), [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'id_client' => $attachment->getClient()->getIdClient()
            ]);
        }



        return new Response('success');
    }


    /**
     * @param Request $request
     *
     * @return bool|Response
     */
    private function checkIp(Request $request)
    {
        $settingRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');

        switch ($this->getParameter('kernel.environment')) {
            case 'prod':
                $allowedIPSettings = $settingRepository->findOneBy(['type' => 'green_point_ip_prod'])->getValue();
                break;
            default:
                $allowedIPSettings = $settingRepository->findOneBy(['type' => 'green_point_ip_test'])->getValue();
                break;
        }

        $allowedIpSettings = json_decode($allowedIPSettings, true);

        if (empty($allowedIpSettings['root']) || empty($allowedIpSettings['out_of_range']) || empty($allowedIpSettings['min_range']) || empty($allowedIpSettings['max_range'])) {
            return new Response('Internal Server Error', 500);
        }

        $allowedIpRange = $this->getIPAddressesInDefinedRange($allowedIpSettings['min_range'], $allowedIpSettings['max_range'], $allowedIpSettings['root']);
        $outOfRangeIp   = $this->getIpAddressesOutOfRange($allowedIpSettings['out_of_range'], $allowedIpSettings['root']);
        $allowedIp      = array_merge($allowedIpRange, $outOfRangeIp, ['192.168.110.12', '192.168.1.196']);

        if (false === in_array($request->getClientIp(), $allowedIp)) {
            return new Response('Forbidden', 403);
        }

        return true;
    }

    /**
     * @param int    $minRange
     * @param int    $maxRange
     * @param string $root
     *
     * @return array
     */
    private function getIPAddressesInDefinedRange(int $minRange, int $maxRange, string $root)
    {
        for ($suffix = $minRange; $suffix <= $maxRange; $suffix++) {
            $allowedIp[] = $root . $suffix;
        }

        return $allowedIp;
    }

    /**
     * @param string $outOfRange
     * @param string $root
     *
     * @return array
     */
    private function getIpAddressesOutOfRange(string $outOfRange, string $root)
    {
        foreach (explode(',', $outOfRange) as $suffix) {
            $allowedIp[] = $root . $suffix;
        }

        return $allowedIp;
    }

//    private function updateGreenPointKyc($iClientId)
//    {
//        /** @var \greenpoint_kyc $oGreenPointKyc */
//        $oGreenPointKyc = $this->loadData('greenpoint_kyc');
//
//        /** @var greenPoint $oGreenPoint */
//        $oGreenPoint = new greenPoint($this->getParameter('kernel.environment'));
//        greenPointStatus::addCustomer($iClientId, $oGreenPoint, $oGreenPointKyc);
//    }
//
//    /**
//     * @param int $iClientId
//     * @param greenPoint $oGreenPoint
//     * @param \greenpoint_kyc $oGreenPointKyc
//     */
//    public function addCustomer($iClientId, greenPoint $oGreenPoint, \greenpoint_kyc $oGreenPointKyc)
//    {
//        $aResult = $oGreenPoint->getCustomer($iClientId);
//        $aKyc    = json_decode($aResult[0]['RESPONSE'], true);
//
//        if (isset($aKyc['resource']['statut_dossier'])) {
//            if (0 < $oGreenPointKyc->counter('id_client = ' . $iClientId)) {
//                $oGreenPointKyc->get($iClientId, 'id_client');
//                $oGreenPointKyc->status      = $aKyc['resource']['statut_dossier'];
//                $oGreenPointKyc->last_update = $aKyc['resource']['modification'];
//                $oGreenPointKyc->update();
//            } else {
//                $oGreenPointKyc->id_client     = $iClientId;
//                $oGreenPointKyc->status        = $aKyc['resource']['statut_dossier'];
//                $oGreenPointKyc->creation_date = $aKyc['resource']['creation'];
//                $oGreenPointKyc->last_update   = $aKyc['resource']['modification'];
//                $oGreenPointKyc->create();
//            }
//        }
//    }

}
