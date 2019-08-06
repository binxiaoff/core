<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\{Embeddable\Fee, Project, ProjectFee};
use Unilend\Repository\ProjectRepository;

class FeesController extends AbstractController
{
    /**
     * @Route("/project/{hash}/fees/add", name="project_fees_add", methods={"POST"}, requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param Project           $project
     * @param Request           $request
     * @param ProjectRepository $projectRepository
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return JsonResponse
     */
    public function addProjectFees(Project $project, Request $request, ProjectRepository $projectRepository): JsonResponse
    {
        $projectForm = $request->request->get('project_type');
        $projectFees = empty($projectForm['projectFees']) ? [] : $projectForm['projectFees'];

        if ($project && is_iterable($projectFees)) {
            foreach ($projectFees as $fee) {
                $fee = new Fee();
                $fee->setType($fee['fee']['type'])
                    ->setRate(str_replace(',', '.', $fee['fee']['rate']))
                    ->setIsRecurring((bool) empty($fee['fee']['isRecurring']) ? false : $fee['fee']['isRecurring'])
                ;

                $projectFee = new ProjectFee();
                $projectFee->setFee($fee);

                $project->addProjectFee($projectFee);
            }

            $projectRepository->save($project);
        }

        return $this->json('OK');
    }

    /**
     * @Route(
     *     "/project/{hash}/fees/remove/{projectFee}", name="project_fees_remove",
     *     requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}
     * )
     *
     * @ParamConverter("attachment", options={"mapping": {"hash": "hash"}})
     * @ParamConverter("projectFee", options={"mapping": {"projectFee": "id"}})
     *
     * @param Project           $project
     * @param ProjectFee        $projectFee
     * @param ProjectRepository $projectRepository
     *
     * @throws OptimisticLockException
     * @throws ORMException
     *
     * @return RedirectResponse
     */
    public function removeProjectFees(Project $project, ProjectFee $projectFee, ProjectRepository $projectRepository): RedirectResponse
    {
        $project->removeProjectFee($projectFee);
        $projectRepository->save($project);

        return $this->redirectToRoute('edit_project_details', ['hash' => $project->getHash()]);
    }
}
