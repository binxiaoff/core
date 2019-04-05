<?php

namespace Unilend\Controller\Endpoint;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Service\GreenPointValidationManager;
use Unilend\Service\WebServiceClient\GreenPointManager;
use Unilend\Entity\{Attachment, Settings};

class GreenPointController extends Controller
{
    /** @var LoggerInterface  */
    private $logger;
    /** @var EntityManager */
    private $entityManager;
    /** @var GreenPointValidationManager */
    private $validationManager;

    /**
     * @Route("/ws/kyc", name="greenpoint_asynchronous_feedback", methods={"POST"})
     */
    public function greenPointAsynchronousFeedbackAction(Request $request): Response
    {
        $this->logger            = $this->get('logger');
        $this->entityManager     = $this->get('doctrine.orm.entity_manager');
        $this->validationManager = $this->get(GreenPointValidationManager::class);

        if (true !== ($response = $this->checkIp($request))) {
            return $response;
        }

        $document = $request->request->getInt('document');
        $file     = $request->request->getInt('dossier');

        if (empty($document) || empty($file)) {
            return new Response('Data incomplete. Client ID or document ID missing', 400);
        }

        $type = $request->request->getInt('type');
        if (false === in_array($type, [GreenPointManager::TYPE_IDENTITY_DOCUMENT, GreenPointManager::TYPE_RIB, GreenPointManager::TYPE_HOUSING_CERTIFICATE])) {
            $this->logger->warning(
                'GreenPoint returned feedback for unknown type', [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'type'     => $type
            ]);

            return new Response('Unexpected Type', 404);
        }

        $attachment = $this->entityManager->getRepository(Attachment::class)->find($document);
        if (null === $attachment) {
            $this->logger->warning(
                'Attachement not found', [
                'class'         => __CLASS__,
                'function'      => __FUNCTION__,
                'attachment_id' => $request->request->getInt('document')
            ]);
            return new Response('Data incomplete. Document ID unknown', 400);
        }

        try {
            $this->validationManager->handleAsynchronousFeedback($type, $request->request->all(), $attachment);
            $return = GreenPointManager::ASYNCHRONOUS_SUCCESS;

        } catch (\Exception $exception) {
            $this->logger->error(
                'An error occurred during asynchronous GreenPoint feedback. Message: ' . $exception->getMessage(), [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'id_client' => $attachment->getClient()->getIdClient()
            ]);
            $return = 'Error';
        }

        return new Response($return);
    }


    /**
     * @param Request $request
     *
     * @return bool|Response
     */
    private function checkIp(Request $request)
    {
        $settingRepository = $this->entityManager->getRepository(Settings::class);

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
        $allowedIp = [];

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
        $allowedIp = [];

        foreach (explode(',', $outOfRange) as $suffix) {
            $allowedIp[] = $root . $suffix;
        }

        return $allowedIp;
    }
}
