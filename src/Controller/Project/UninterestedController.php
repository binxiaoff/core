<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Project, TemporaryToken};
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Repository\TemporaryTokenRepository;
use Unilend\Security\Voter\ProjectParticipationVoter;

class UninterestedController extends AbstractController
{
    /**
     * @var ProjectParticipationRepository
     */
    private $projectParticipationRepository;

    /**
     * RefuseController constructor.
     *
     * @param ProjectParticipationRepository $projectParticipationRepository
     */
    public function __construct(
        ProjectParticipationRepository $projectParticipationRepository
    ) {
        $this->projectParticipationRepository = $projectParticipationRepository;
    }

    /**
     * @Route("/projet/refuse/{hash}", name="project_uninterested_connected")
     *
     * @ParamConverter("project", options={"mapping": {"hash": "hash"}})
     *
     * @param UserInterface|null $user
     * @param Project            $project
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return RedirectResponse
     */
    public function uninterestedAsConnected(
        ?UserInterface $user,
        Project $project
    ): RedirectResponse {
        $this->setUninterested($user, $project);

        return $this->redirect($this->generateUrl('edit_project_details', ['hash' => $project->getHash()]));
    }

    /**
     * @Route("/projet/refuse/{securityToken}/{hash}", name="project_uninterested_anonymous")
     *
     * @ParamConverter("temporaryToken", options={"mapping": {"securityToken": "token"}})
     * @ParamConverter("project", options={"mapping": {"hash": "hash"}})
     *
     * @param TemporaryToken           $temporaryToken
     * @param Project                  $project
     * @param TemporaryTokenRepository $temporaryTokenRepository
     *
     *@throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function uninterestedAsAnonymous(
        TemporaryToken $temporaryToken,
        Project $project,
        TemporaryTokenRepository $temporaryTokenRepository
    ): RedirectResponse {
        if ($temporaryToken->isValid()) {
            throw new AccessDeniedException('Token is invalid');
        }

        $temporaryToken->setAccessed();
        $temporaryTokenRepository->save($temporaryToken);

        $this->setUninterested($temporaryToken->getClient(), $project);

        $temporaryToken->setExpired();

        $temporaryTokenRepository->save($temporaryToken);

        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * @param Clients $clients
     * @param Project $project
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function setUninterested(
        Clients $clients,
        Project $project
    ): void {
        $projectParticipation = $this->projectParticipationRepository->findByProjectAndClient($project, $clients);

        $this->denyAccessUnlessGranted(ProjectParticipationVoter::ATTRIBUTE_REFUSE, $projectParticipation);

        if (!$projectParticipation) {
            throw new NotFoundHttpException();
        }

        $projectParticipation->setUninterested();
        $this->projectParticipationRepository->save($projectParticipation);
    }
}
