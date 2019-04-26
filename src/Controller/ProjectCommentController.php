<?php

namespace Unilend\Controller;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, ProjectComment, Projects};
use Unilend\Repository\ProjectCommentRepository;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class ProjectCommentController extends AbstractController
{
    /**
     * @Route("/project/comment/{project}", name="project_comment_add", requirements={"project": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @ParamConverter("project", options={"mapping": {"project": "hash"}})
     *
     * @param Projects                   $project
     * @param ProjectCommentRepository   $projectCommentRepository
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function add(Projects $project, ProjectCommentRepository $projectCommentRepository, Request $request, ?UserInterface $user): Response
    {
        if (null === $project) {
            return $this->json([
                'error'   => true,
                'message' => 'Invalid parameters',
            ], Response::HTTP_BAD_REQUEST);
        }

        $userId = $request->request->filter('user', null, FILTER_VALIDATE_INT);

        if ($userId !== $user->getIdClient()) {
            return $this->json([
                'error'   => true,
                'message' => 'Invalid user ID',
            ], Response::HTTP_BAD_REQUEST);
        }

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

            return $this->render('project_comment/response.json.twig', ['comment' => $comment], new JsonResponse());
        }

        return $this->json([
            'error'   => true,
            'message' => 'Unable to add comment',
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/project/comment", name="project_comment_update")
     *
     * @param ProjectCommentRepository   $projectCommentRepository
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
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

            return $this->render('project_comment/response.json.twig', ['comment' => $comment], new JsonResponse());
        }

        return $this->json([
            'error'   => true,
            'message' => 'Invalid user ID',
        ], Response::HTTP_BAD_REQUEST);
    }
}
