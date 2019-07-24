<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Swift_SwiftException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, SubmitType};
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Unilend\Entity\{Bids, Clients, Project, ProjectConfidentialityAcceptance};
use Unilend\Form\Bid\BidType;
use Unilend\Repository\{BidsRepository, ProjectAttachmentRepository, ProjectConfidentialityAcceptanceRepository};
use Unilend\Security\Voter\ProjectVoter;
use Unilend\Service\{NotificationManager, User\RealUserFinder};

class ViewController extends AbstractController
{
    /**
     * @Route("/projet/details/{slug}", name="lender_project_details")
     *
     * @IsGranted("view", subject="project")
     *
     * @param Project                       $project
     * @param Request                       $request
     * @param UserInterface|Clients|null    $user
     * @param BidsRepository                $bidsRepository
     * @param ProjectAttachmentRepository   $projectAttachmentRepository
     * @param NotificationManager           $notificationManager
     * @param RealUserFinder                $realUserFinder
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param LoggerInterface               $logger
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
        NotificationManager $notificationManager,
        RealUserFinder $realUserFinder,
        AuthorizationCheckerInterface $authorizationChecker,
        LoggerInterface $logger
    ): Response {
        if (false === $authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW_CONFIDENTIAL, $project)) {
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
     * @param AuthorizationCheckerInterface              $authorizationChecker
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
        ProjectConfidentialityAcceptanceRepository $acceptanceRepository,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {
        if ($authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW_CONFIDENTIAL, $project)) {
            return $this->redirectToRoute('lender_project_details', ['slug' => $project->getSlug()]);
        }

        $acceptanceForm = $this->get('form.factory')
            ->createNamedBuilder('acceptance')
            ->add('accept', CheckboxType::class, [
                'value'       => true,
                'label'       => false,
                'required'    => true,
                'constraints' => new NotBlank(),
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'project-confidentiality.submit-button-label',
            ])
            ->getForm()
        ;

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
}
