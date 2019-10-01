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
use Unilend\Entity\{ClientsStatus, Project, ProjectParticipation, TemporaryLinksLogin};
use Unilend\Message\Client\ClientInvited;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\{Staff\StaffManager, User\RealUserFinder};

class InvitationController extends AbstractController
{
    /**
     * @Route("/projet/invitation/{securityToken}/{projectParticipationId}", name="project_invitation")
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     * @ParamConverter("projectParticipation", options={"mapping": {"projectParticipationId": "id"}})
     *
     * @param TemporaryLinksLogin  $temporaryLink
     * @param ProjectParticipation $projectParticipation
     *
     * @return RedirectResponse
     */
    public function invitation(TemporaryLinksLogin $temporaryLink, ProjectParticipation $projectParticipation): RedirectResponse
    {
        $client = $temporaryLink->getIdClient();

        switch ($client->getCurrentStatus()->getStatus()) {
            case ClientsStatus::STATUS_INVITED:
                return $this->redirectToRoute('account_init', [
                    'securityToken'          => $temporaryLink->getToken(),
                    'projectParticipationId' => $projectParticipation->getId(),
                ]);
            case ClientsStatus::STATUS_CREATED:
                return $this->redirectToRoute('lender_project_details', ['slug' => $projectParticipation->getProject()->getSlug()]);
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
            try {
                $staff = $staffManager->getStaffByEmail($form->getData()['email_guest']);
            } catch (DomainException $domainException) {
                $this->addFlash('sendError', $translator->trans('invite-guest.send-error-message'));

                return $this->redirectToRoute('invite_guest', ['hash' => $project->getHash()]);
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
