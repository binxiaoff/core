<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use DateTime;
use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Project, TemporaryLinksLogin};
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Repository\TemporaryLinksLoginRepository;
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
     * @Route("/projet/refuse/{slug}", name="project_uninterested_connected")
     *
     * @ParamConverter("project", options={"mapping": {"slug": "slug"}})
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
     * @Route("/projet/refuse/{securityToken}/{slug}", name="project_uninterested_anonymous")
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     * @ParamConverter("project", options={"mapping": {"slug": "slug"}})
     *
     * @param TemporaryLinksLogin           $login
     * @param Project                       $project
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return RedirectResponse
     */
    public function uninterestedAsAnonymous(
        TemporaryLinksLogin $login,
        Project $project,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository
    ): RedirectResponse {
        if ($login->isExpires()) {
            throw new AccessDeniedException();
        }

        $this->setUninterested($login->getIdClient(), $project);

        $login->setAccessed(new DateTime());
        $login->setExpires(new DateTime());

        $temporaryLinksLoginRepository->save($login);

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
    protected function setUninterested(
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
