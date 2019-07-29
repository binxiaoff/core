<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Swift_SwiftException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Bids, Clients, Project, ProjectConfidentialityAcceptance};
use Unilend\Form\Bid\BidType;
use Unilend\Form\Project\ConfidentialityAcceptanceType;
use Unilend\Repository\{BidsRepository, ProjectAttachmentRepository, ProjectConfidentialityAcceptanceRepository};
use Unilend\Service\{NotificationManager, User\RealUserFinder};

class ViewController extends AbstractController
{
    /**
     * @Route("/projet/details/{slug}", name="lender_project_details")
     *
     * @IsGranted("view", subject="project")
     *
     * @param Project                                    $project
     * @param Request                                    $request
     * @param UserInterface|Clients|null                 $user
     * @param BidsRepository                             $bidsRepository
     * @param ProjectAttachmentRepository                $projectAttachmentRepository
     * @param ProjectConfidentialityAcceptanceRepository $acceptanceRepository
     * @param NotificationManager                        $notificationManager
     * @param RealUserFinder                             $realUserFinder
     * @param LoggerInterface                            $logger
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
        BidsRepository $bidsRepository,
        ProjectAttachmentRepository $projectAttachmentRepository,
        ProjectConfidentialityAcceptanceRepository $acceptanceRepository,
        NotificationManager $notificationManager,
        RealUserFinder $realUserFinder,
        LoggerInterface $logger
    ): Response {
        if (false === $this->isConfidentialityGranted($project, $user, $acceptanceRepository)) {
            return $this->redirectToRoute('project_confidentiality_acceptance', ['slug' => $project->getSlug()]);
        }

        $bidForms = [];

        foreach ($project->getTranches() as $tranche) {
            $userBid = $tranche->getBids([Bids::STATUS_PENDING], $user->getCompany())->first();
            if (!$userBid) {
                $userBid = new Bids();
                $userBid
                    ->setTranche($tranche)
                    ->setLender($user->getCompany())
                    ->setStatus(Bids::STATUS_PENDING)
                    ->setAddedByValue($realUserFinder)
                ;
                $userBid->getMoney()->setCurrency($tranche->getMoney()->getCurrency());

                if ($tranche->getRate()) {
                    $userBid->setRate($tranche->getRate());
                }

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
                    $notificationManager->createBidSubmitted($bid);
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
            'bidForms'           => $bidForms,
            'projectAttachments' => $projectAttachmentRepository->getAttachmentsWithoutSignature($project, ['added' => 'DESC']),
        ]);
    }

    /**
     * @Route("/projet/confidentialite/{slug}", name="project_confidentiality_acceptance")
     *
     * @param Project                                    $project
     * @param Request                                    $request
     * @param UserInterface|Clients|null                 $user
     * @param ProjectConfidentialityAcceptanceRepository $acceptanceRepository
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function confidentialityAcceptance(
        Project $project,
        Request $request,
        ?UserInterface $user,
        ProjectConfidentialityAcceptanceRepository $acceptanceRepository
    ): Response {
        if ($this->isConfidentialityGranted($project, $user, $acceptanceRepository)) {
            return $this->redirectToRoute('lender_project_details', ['slug' => $project->getSlug()]);
        }

        $acceptanceForm = $this->createForm(ConfidentialityAcceptanceType::class);
        $acceptanceForm->handleRequest($request);

        if ($acceptanceForm->isSubmitted() && $acceptanceForm->isValid()) {
            $acceptance = new ProjectConfidentialityAcceptance();
            $acceptance
                ->setProject($project)
                ->setClient($user)
            ;

            $acceptanceRepository->save($acceptance);

            return $this->redirectToRoute('lender_project_details', ['slug' => $project->getSlug()]);
        }

        return $this->render('project/view/confidentiality.html.twig', [
            'project'        => $project,
            'acceptanceForm' => $acceptanceForm->createView(),
        ]);
    }

    /**
     * @param Project                                    $project
     * @param Clients                                    $user
     * @param ProjectConfidentialityAcceptanceRepository $acceptanceRepository
     *
     * @return bool
     */
    private function isConfidentialityGranted(Project $project, Clients $user, ProjectConfidentialityAcceptanceRepository $acceptanceRepository): bool
    {
        if (false === $project->isConfidential()) {
            return true;
        }

        if ($user->getCompany() === $project->getSubmitterCompany()) {
            return true;
        }

        $acceptance = $acceptanceRepository->findOneBy(['project' => $project, 'client' => $user]);

        return null !== $acceptance;
    }
}
