<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use DomainException;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{ClientsStatus, Project, ProjectParticipation, TemporaryToken};
use Unilend\Exception\{Client\ClientNotFoundException, Staff\StaffNotFoundException};
use Unilend\Message\Client\ClientInvited;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\{Staff\StaffManager, User\RealUserFinder};

class InvitationController extends AbstractController
{
    /**
     * @Route("/projet/invitation/{securityToken}/{slug}", name="project_invitation")
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     * @ParamConverter("project", options={"mapping": {"slug": "slug"}})
     *
     * @param TemporaryToken $temporaryLink
     * @param Project             $project
     *
     * @return RedirectResponse
     */
    public function invitation(TemporaryToken $temporaryLink, Project $project): RedirectResponse
    {
        $client = $temporaryLink->getIdClient();

        switch ($client->getCurrentStatus()->getStatus()) {
            case ClientsStatus::STATUS_INVITED:
                return $this->redirectToRoute('account_init', [
                    'securityToken' => $temporaryLink->getToken(),
                    'slug'          => $project->getSlug(),
                ]);
            case ClientsStatus::STATUS_CREATED:
                return $this->redirectToRoute('lender_project_details', ['slug' => $project->getSlug()]);
            default:
                throw new LogicException('This code should not be reached');
        }
    }

    /**
     * @Route("/projet/inviter-interlocuteur/{hash}", name="invite_guest", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param Project                               $project
     * @param Request                               $request
     * @param TranslatorInterface                   $translator
     * @param MessageBusInterface                   $messageBus
     * @param ProjectRepository                     $projectRepository
     * @param ProjectParticipationRepository        $projectParticipationRepository
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     * @param RealUserFinder                        $realUserFinder
     * @param StaffManager                          $staffManager
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
        MessageBusInterface $messageBus,
        ProjectRepository $projectRepository,
        ProjectParticipationRepository $projectParticipationRepository,
        ProjectParticipationContactRepository $projectParticipationContactRepository,
        RealUserFinder $realUserFinder,
        StaffManager $staffManager
    ): Response {
        $form = $this->createFormBuilder()->add('email_guest', EmailType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inviteeEmail = $form->getData()['email_guest'];

            try {
                $staff = $staffManager->getStaffByEmail($inviteeEmail);
            } catch (DomainException $domainException) {
                $this->addFlash('sendError', $translator->trans('invite-guest.send-error-message'));

                return $this->redirectToRoute('invite_guest', ['hash' => $project->getHash()]);
            } catch (ClientNotFoundException | StaffNotFoundException $notFoundException) {
                $staff = $staffManager->addStaffFromEmail($inviteeEmail);
            }

            $projectParticipation = $projectParticipationRepository->findOneBy(['company' => $staff->getCompany()]);

            if (null === $projectParticipation) {
                $projectParticipation = $project->addProjectParticipation($staff->getCompany(), ProjectParticipation::ROLE_PROJECT_LENDER, $realUserFinder);
            }

            if ($projectParticipationContactRepository->findBy(['client' => $staff->getClient(), 'projectParticipation' => $projectParticipation])) {
                $this->addFlash('sendError', $translator->trans('invite-guest.email-already-sent'));

                return $this->redirectToRoute('invite_guest', ['hash' => $project->getHash()]);
            }

            $projectParticipationContact = $projectParticipation->addProjectParticipationContact($staff->getClient(), $realUserFinder);

            $projectRepository->save($project);
            $this->addFlash('sendSuccess', $translator->trans('invite-guest.send-success-message'));

            $messageBus->dispatch(new ClientInvited($projectParticipationContact->getId()));

            return $this->redirectToRoute('invite_guest', ['hash' => $project->getHash()]);
        }

        return $this->render('project/invite_guest.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
