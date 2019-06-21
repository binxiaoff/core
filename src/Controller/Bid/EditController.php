<?php

declare(strict_types=1);

namespace Unilend\Controller\Bid;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Psr\Log\LoggerInterface;
use Swift_SwiftException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\Bids;
use Unilend\Entity\Embeddable\Money;
use Unilend\Form\Bid\PartialBid;
use Unilend\Repository\BidsRepository;
use Unilend\Security\Voter\BidVoter;
use Unilend\Service\{BidManager, MailerManager};

class EditController extends AbstractController
{
    /**
     * @Route("/bid/status", name="edit_bid_status", methods={"POST"})
     *
     * @param Request         $request
     * @param BidManager      $bidManager
     * @param BidsRepository  $bidsRepository
     * @param MailerManager   $mailerManager
     * @param LoggerInterface $logger
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function transition(
        Request $request,
        BidManager $bidManager,
        BidsRepository $bidsRepository,
        MailerManager $mailerManager,
        LoggerInterface $logger
    ): RedirectResponse {
        $bidId  = $request->request->get('bid');
        $status = $request->request->getInt('status');
        $bid    = $bidsRepository->find($bidId);

        if ($bid && in_array($status, $bid->getAllStatus())) {
            $this->denyAccessUnlessGranted(BidVoter::ATTRIBUTE_MANAGE, $bid);

            switch ($status) {
                case Bids::STATUS_ACCEPTED:
                    $bidManager->accept($bid);

                    break;
                case Bids::STATUS_REJECTED:
                    $bidManager->reject($bid);

                    break;
                default:
                    $bid->setStatus($status);
                    $bidsRepository->save($bid);

                    break;
            }

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

            return $this->redirectToRoute('edit_project_details', ['hash' => $bid->getTranche()->getProject()->getHash()]);
        }

        return $this->redirect(filter_var($request->headers->get('referer'), FILTER_SANITIZE_URL));
    }

    /**
     * @Route("/bid/partial", name="edit_bid_partial", methods={"POST"})
     *
     * @param Request         $request
     * @param BidManager      $bidManager
     * @param BidsRepository  $bidsRepository
     * @param MailerManager   $mailerManager
     * @param LoggerInterface $logger
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function partial(
        Request $request,
        BidManager $bidManager,
        BidsRepository $bidsRepository,
        MailerManager $mailerManager,
        LoggerInterface $logger
    ): RedirectResponse {
        $form = $this->createForm(PartialBid::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $bid  = $bidsRepository->find($data['id']);

            if ($bid && $data['amount'] <= $bid->getMoney()->getAmount()) {
                $this->denyAccessUnlessGranted(BidVoter::ATTRIBUTE_MANAGE, $bid);

                $money = (new Money())
                    ->setCurrency($bid->getMoney()->getCurrency())
                    ->setAmount((string) $data['amount'])
                ;

                $bidManager->accept($bid, $money);

                try {
                    // @todo partial bid acceptance
                    $mailerManager->sendBidAcceptedRejected($bid);
                } catch (Swift_SwiftException $exception) {
                    $logger->error('An error occurred while sending accepted bid email. Message: ' . $exception->getMessage(), [
                        'class'    => __CLASS__,
                        'function' => __FUNCTION__,
                        'file'     => $exception->getFile(),
                        'line'     => $exception->getLine(),
                    ]);
                }

                return $this->redirectToRoute('edit_project_details', ['hash' => $bid->getTranche()->getProject()->getHash()]);
            }
        }

        return $this->redirect(filter_var($request->headers->get('referer'), FILTER_SANITIZE_URL));
    }
}
