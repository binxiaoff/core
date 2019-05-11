<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\{PercentFee, Project, ProjectPercentFee};
use Unilend\Repository\{FeeTypeRepository, ProjectRepository};

class ProjectFeesController extends AbstractController
{
    /**
     * @Route("/project/{hash}/fees/add", name="project_fees_add", methods={"POST"}, requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param Project           $project
     * @param Request           $request
     * @param FeeTypeRepository $feeTypeRepository
     * @param ProjectRepository $projectRepository
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return JsonResponse
     */
    public function addProjectFees(Project $project, Request $request, FeeTypeRepository $feeTypeRepository, ProjectRepository $projectRepository): JsonResponse
    {
        $projectForm        = $request->request->get('project_type');
        $projectPercentFees = empty($projectForm['projectPercentFees']) ? [] : $projectForm['projectPercentFees'];

        if ($project && is_iterable($projectPercentFees)) {
            foreach ($projectPercentFees as $fee) {
                $percentFee = new PercentFee();
                $type       = $feeTypeRepository->find($fee['percentFee']['type']);
                $percentFee->setType($type)
                    ->setRate(str_replace(',', '.', $fee['percentFee']['rate']))
                    ->setIsRecurring((bool) empty($fee['percentFee']['isRecurring']) ? false : $fee['percentFee']['isRecurring'])
                ;

                $projectPercentFee = new ProjectPercentFee();
                $projectPercentFee->setPercentFee($percentFee);

                $project->addProjectPercentFee($projectPercentFee);
            }

            $projectRepository->save($project);
        }

        return $this->json('OK');
    }

    /**
     * @Route(
     *     "/project/{hash}/fees/remove/{projectPercentFee}", name="project_fees_remove",
     *     requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}
     * )
     *
     * @ParamConverter("attachment", options={"mapping": {"hash": "hash"}})
     * @ParamConverter("projectPercentFee", options={"mapping": {"projectPercentFee": "id"}})
     *
     * @param Project           $project
     * @param ProjectPercentFee $projectPercentFee
     * @param ProjectRepository $projectRepository
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return RedirectResponse
     */
    public function removeProjectFees(Project $project, ProjectPercentFee $projectPercentFee, ProjectRepository $projectRepository): RedirectResponse
    {
        $project->removeProjectPercentFee($projectPercentFee);
        $projectRepository->save($project);

        return $this->redirectToRoute('edit_project_details', ['hash' => $project->getHash()]);
    }
}
