<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Psr\Log\LoggerInterface;
use Swift_SwiftException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Bids, Clients, Project};
use Unilend\Form\Bid\BidType;
use Unilend\Repository\{BidsRepository, ProjectAttachmentRepository};
use Unilend\Service\Front\ProjectDisplayManager;
use Unilend\Service\MailerManager;
use Unilend\Service\User\RealUserFinder;

class ViewController extends AbstractController
{
    /**
     * @Route("/projets/details/lender/{slug}", name="lender_project_details")
     *
     * @param Project                     $project
     * @param Request                     $request
     * @param UserInterface|Clients|null  $user
     * @param BidsRepository              $bidsRepository
     * @param ProjectAttachmentRepository $projectAttachmentRepository
     * @param ProjectDisplayManager       $projectDisplayManager
     * @param MailerManager               $mailerManager
     * @param RealUserFinder              $realUserFinder
     * @param LoggerInterface             $logger
     *
     * @throws Exception
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
        ProjectDisplayManager $projectDisplayManager,
        MailerManager $mailerManager,
        RealUserFinder $realUserFinder,
        LoggerInterface $logger
    ): Response {
        if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $user)) {
            return $this->redirectToRoute('list_projects');
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
            'bidForms'           => $bidForms,
            'projectAttachments' => $projectAttachmentRepository->getAttachmentsWithoutSignature($project, ['added' => 'DESC']),
        ]);
    }
}
