<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Project, ProjectComment};
use Unilend\Repository\ProjectCommentRepository;
use Unilend\Service\NotificationManager;

class CommentController extends AbstractController
{
    /**
     * @Route(
     *     "/project/{hash}/comment/add",
     *     name="project_comment_add",
     *     requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"},
     *     methods={"POST"},
     *     condition="request.isXmlHttpRequest()"
     * )
     *
     * @IsGranted("comment", subject="project")
     *
     * @param Project                    $project
     * @param ProjectCommentRepository   $projectCommentRepository
     * @param NotificationManager        $notificationManager
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     *
     * @throws Exception
     * @throws OptimisticLockException
     * @throws ORMException
     *
     * @return Response
     */
    public function add(
        Project $project,
        ProjectCommentRepository $projectCommentRepository,
        NotificationManager $notificationManager,
        Request $request,
        ?UserInterface $user
    ): Response {
        $content = $request->request->get('content');

        if ($content) {
            $parent  = $request->request->getInt('parent');
            $parent  = $parent ? $projectCommentRepository->find($parent) : null;
            $comment = new ProjectComment();
            $comment
                ->setParent($parent)
                ->setProject($project)
                ->setClient($user)
                ->setContent($content)
                ->setVisibility(ProjectComment::VISIBILITY_ALL)
            ;

            $projectCommentRepository->save($comment);

            $notificationManager->createProjectCommentAdded($comment);

            return $this->render('project/comment/response.json.twig', ['comment' => $comment], new JsonResponse());
        }

        return $this->json([
            'error'   => true,
            'message' => 'Unable to add comment',
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route(
     *     "/project/comment/{id}/update",
     *     name="project_comment_update",
     *     options={"expose": true},
     *     requirements={"id": "\d+"},
     *     methods={"PATCH"},
     *     condition="request.isXmlHttpRequest()"
     * )
     *
     * @IsGranted("edit", subject="projectComment")
     *
     * @param ProjectComment           $projectComment
     * @param ProjectCommentRepository $projectCommentRepository
     * @param Request                  $request
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function update(ProjectComment $projectComment, ProjectCommentRepository $projectCommentRepository, Request $request): Response
    {
        $content = $request->request->get('content');

        if ($content) {
            $projectComment->setContent($content);
            $projectCommentRepository->save($projectComment);

            return $this->render('project/comment/response.json.twig', ['comment' => $projectComment], new JsonResponse());
        }

        return $this->json([
            'error'   => true,
            'message' => 'Content is empty',
        ], Response::HTTP_BAD_REQUEST);
    }
}
