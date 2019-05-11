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
use Unilend\Form\Lending\BidType;
use Unilend\Repository\{BidsRepository, ProjectAttachmentRepository, WalletRepository};
use Unilend\Service\{DemoMailerManager, Front\ProjectDisplayManager};

class LenderProjectController extends AbstractController
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
     * @param DemoMailerManager           $mailerManager
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
        DemoMailerManager $mailerManager,
        LoggerInterface $logger
    ): Response {
        if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $user)) {
            return $this->redirectToRoute('list_projects');
        }

        $wallet = $walletRepository->getWalletByType($user, WalletType::LENDER);
        $bid    = $bidsRepository->findOneBy([
            'wallet'  => $wallet,
            'project' => $project,
            'status'  => [Bids::STATUS_PENDING, Bids::STATUS_ACCEPTED],
        ])
        ;

        if (null === $bid && $wallet) {
            $bid = new Bids();
            $bid->setProject($project)
                ->setWallet($wallet)
                ->setStatus(Bids::STATUS_PENDING)
            ;
        }

        $form = $this->createForm(BidType::class, $bid);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

        return $this->render('demo/project.html.twig', [
            'project'            => $project,
            'wallet'             => $wallet,
            'bid'                => $bid,
            'form'               => $form->createView(),
            'projectAttachments' => $projectAttachmentRepository->findBy(['project' => $project], ['added' => 'DESC']),
        ]);
    }
}
