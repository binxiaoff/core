<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Psr\Log\LoggerInterface;
use Swift_SwiftException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Bids, Clients, Project, WalletType};
use Unilend\Form\Bid\BidType;
use Unilend\Repository\{BidsRepository, ProjectAttachmentRepository, WalletRepository};
use Unilend\Service\Front\ProjectDisplayManager;
use Unilend\Service\MailerManager;

class ViewController extends AbstractController
{
    /**
     * @Route("/projets/details/lender/{slug}", name="lender_project_details")
     *
     * @param Project                     $project
     * @param Request                     $request
     * @param UserInterface|Clients|null  $user
     * @param WalletRepository            $walletRepository
     * @param BidsRepository              $bidsRepository
     * @param ProjectAttachmentRepository $projectAttachmentRepository
     * @param ProjectDisplayManager       $projectDisplayManager
     * @param MailerManager               $mailerManager
     * @param LoggerInterface             $logger
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function details(
        Project $project,
        Request $request,
        ?UserInterface $user,
        WalletRepository $walletRepository,
        BidsRepository $bidsRepository,
        ProjectAttachmentRepository $projectAttachmentRepository,
        ProjectDisplayManager $projectDisplayManager,
        MailerManager $mailerManager,
        LoggerInterface $logger
    ): Response {
        if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $user)) {
            return $this->redirectToRoute('list_projects');
        }

        $wallet = $walletRepository->getWalletByType($user, WalletType::LENDER);

        $bidForms = [];

        foreach ($project->getTranches() as $tranche) {
            $userBid = $tranche->getBids([Bids::STATUS_PENDING], $wallet)->first();
            if (!$userBid) {
                $userBid = new Bids();
                $userBid
                    ->setTranche($tranche)
                    ->setWallet($wallet)
                    ->setStatus(Bids::STATUS_PENDING)
                ;
                $userBid->getMoney()->setCurrency($tranche->getMoney()->getCurrency());
                $bidForms[$tranche->getId()] = $this->get('form.factory')->createNamed('tranche_' . $tranche->getId(), BidType::class, $userBid);
            }
        }

        foreach ($bidForms as $bidForm) {
            $bidForm->handleRequest($request);

            if ($bidForm->isSubmitted() && $bidForm->isValid()) {
                /** @var Bids $bid */
                $bid = $bidForm->getData();

                $bidsRepository->save($bid);

                try {
                    $mailerManager->sendBidSubmitted($bid);
                } catch (Swift_SwiftException $exception) {
                    $logger->error('An error occurred while sending submitted bid email. Message: ' . $exception->getMessage(), [
                        'class'    => __CLASS__,
                        'function' => __FUNCTION__,
                        'file'     => $exception->getFile(),
                        'line'     => $exception->getLine(),
                    ]);
                }

                return $this->redirectToRoute('lender_project_details', ['slug' => $project->getSlug()]);
            }
        }

        return $this->render('project/view/details.html.twig', [
            'project'            => $project,
            'wallet'             => $wallet,
            'bidForms'           => $bidForms,
            'projectAttachments' => $projectAttachmentRepository->getAttachmentsWithoutSignature($project, ['added' => 'DESC']),
        ]);
    }
}
