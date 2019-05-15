<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Project, ProjectComment};
use Unilend\Repository\ProjectCommentRepository;

class CommentController extends AbstractController
{
    /**
     * @Route("/project/comment/{hash}", name="project_comment_add", requirements={"project": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @IsGranted("comment", subject="project")
     *
     * @param Project                    $project
     * @param ProjectCommentRepository   $projectCommentRepository
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function add(Project $project, ProjectCommentRepository $projectCommentRepository, Request $request, ?UserInterface $user): Response
    {
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

            return $this->render('project/project_comment/response.json.twig', ['comment' => $comment], new JsonResponse());
        }

        return $this->json([
            'error'   => true,
            'message' => 'Unable to add comment',
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/project/comment", name="project_comment_update")
     *
     * @IsGranted("comment", subject="project")
     *
     * @param ProjectCommentRepository   $projectCommentRepository
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function update(ProjectCommentRepository $projectCommentRepository, Request $request, ?UserInterface $user): Response
    {
        $commentId = $request->request->getInt('id');

        if (empty($commentId)) {
            return $this->json([
                'error'   => true,
                'message' => 'Invalid parameters',
            ], Response::HTTP_BAD_REQUEST);
        }

        $comment = $projectCommentRepository->find($commentId);

        if (null === $comment) {
            return $this->json([
                'error'   => true,
                'message' => 'Unknown comment',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($comment->getClient() !== $user) {
            return $this->json([
                'error'   => true,
                'message' => 'User cannot edit this comment',
            ], Response::HTTP_FORBIDDEN);
        }

        $content = $request->request->get('content');

        if ($content) {
            $comment->setContent($content);
            $projectCommentRepository->save($comment);

            return $this->render('project/comment/response.json.twig', ['comment' => $comment], new JsonResponse());
        }

        return $this->json([
            'error'   => true,
            'message' => 'Invalid user ID',
        ], Response::HTTP_BAD_REQUEST);
    }
}
