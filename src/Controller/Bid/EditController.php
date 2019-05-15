<?php

declare(strict_types=1);

namespace Unilend\Controller\Bid;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Psr\Log\LoggerInterface;
use Swift_SwiftException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\Bids;
use Unilend\Repository\BidsRepository;
use Unilend\Service\DemoMailerManager;

class EditController extends AbstractController
{
    /**
     * @Route("/bid/change/status", name="edit_bid_status")
     *
     * @param Request           $request
     * @param BidsRepository    $bidsRepository
     * @param DemoMailerManager $mailerManager
     * @param LoggerInterface   $logger
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return JsonResponse
     */
    public function changeStatus(Request $request, BidsRepository $bidsRepository, DemoMailerManager $mailerManager, LoggerInterface $logger): JsonResponse
    {
        $bidId  = $request->request->get('bid');
        $status = $request->request->get('status');
        $bid    = $bidsRepository->find($bidId);

        if ($bid && in_array($status, $bid->getAllStatus())) {
            $bid->setStatus($status);
            $bidsRepository->save($bid);

            if (in_array($status, [Bids::STATUS_ACCEPTED, Bids::STATUS_REJECTED])) {
                try {
                    $mailerManager->sendBidAcceptedRejected($bid);
                } catch (Swift_SwiftException $exception) {
                    $logger->error('An error occurred while sending accepted/rejected bid email. Message: ' . $exception->getMessage(), [
                        'class'    => __CLASS__,
                        'function' => __FUNCTION__,
                        'file'     => $exception->getFile(),
                        'line'     => $exception->getLine(),
                    ]);
                }
            }
        }

        return $this->json('OK');
    }
}
