<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use LogicException;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{ClientsStatus, Project, ProjectParticipation, TemporaryToken};
use Unilend\Exception\{Client\ClientNotFoundException, Staff\StaffNotFoundException};
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Service\{ProjectParticipation\ProjectParticipationManager, Staff\StaffManager, User\RealUserFinder};

class InvitationController extends AbstractController
{
    /**
     * @Route("/projet/invitation/{securityToken}/{hash}", name="project_invitation")
     *
     * @ParamConverter("temporaryToken", options={"mapping": {"securityToken": "token"}})
     * @ParamConverter("project", options={"mapping": {"hash": "hash"}})
     *
     * @param TemporaryToken $temporaryToken
     * @param Project        $project
     *
     * @return RedirectResponse
     */
    public function invitation(TemporaryToken $temporaryToken, Project $project): RedirectResponse
    {
        $client = $temporaryToken->getClient();

        switch ($client->getCurrentStatus()->getStatus()) {
            case ClientsStatus::STATUS_INVITED:
                return $this->redirectToRoute('account_init', [
                    'securityToken' => $temporaryToken->getToken(),
                    'hash'          => $project->getHash(),
                ]);
            case ClientsStatus::STATUS_CREATED:
                return $this->redirectToRoute('lender_project_details', ['hash' => $project->getHash()]);
            default:
                throw new LogicException('This code should not be reached');
        }
    }

    /**
     * @Route("/projet/inviter-interlocuteur/{hash}", name="invite_guest", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param Project                        $project
     * @param Request                        $request
     * @param TranslatorInterface            $translator
     * @param ProjectParticipationRepository $projectParticipationRepository
     * @param RealUserFinder                 $realUserFinder
     * @param StaffManager                   $staffManager
     * @param ProjectParticipationManager    $projectParticipationManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function addInterlocutor(
        Project $project,
        Request $request,
        TranslatorInterface $translator,
        ProjectParticipationRepository $projectParticipationRepository,
        RealUserFinder $realUserFinder,
        StaffManager $staffManager,
        ProjectParticipationManager $projectParticipationManager
    ): Response {
        $form = $this->createFormBuilder()->add('email_guest', EmailType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inviteeEmail = $form->getData()['email_guest'];

            try {
                $staff = $staffManager->getStaffByEmail($inviteeEmail);
            } catch (ClientNotFoundException | StaffNotFoundException $notFoundException) {
                $staff = $staffManager->addStaffFromEmail($inviteeEmail);
            }

            $projectParticipation = $projectParticipationRepository->findOneBy(['company' => $staff->getCompany(), 'project' => $project]);

            if (null === $projectParticipation) {
                $projectParticipation = $project->addProjectParticipation($staff->getCompany(), ProjectParticipation::DUTY_PROJECT_PARTICIPATION_PARTICIPANT, $realUserFinder);
            }

            try {
                $projectParticipationManager->addProjectParticipantContact($staff->getClient(), $projectParticipation);
            } catch (RuntimeException $exception) {
                $this->addFlash('sendError', $translator->trans('invite-guest.email-already-sent'));

                return $this->redirectToRoute('invite_guest', ['hash' => $project->getHash()]);
            }

            $this->addFlash('sendSuccess', $translator->trans('invite-guest.send-success-message'));

            return $this->redirectToRoute('invite_guest', ['hash' => $project->getHash()]);
        }

        return $this->render('project/invite_guest.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
