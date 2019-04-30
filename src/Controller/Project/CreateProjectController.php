<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\Project;
use Unilend\Form\Project\ProjectEditType;
use Unilend\Repository\{CompaniesRepository, CompanySectorRepository, ProjectAttachmentTypeRepository};
use Unilend\Service\Project\ProjectCreationManager;
use Unilend\Service\ProjectManager;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class CreateProjectController extends AbstractController
{
    /**
     * @Route("/projet/depot", name="create_project", methods={"GET", "POST"})
     *
     * @param Request                         $request
     * @param ProjectManager                  $projectManager
     * @param ProjectCreationManager          $projectCreationManager
     * @param CompaniesRepository             $companiesRepository
     * @param ProjectAttachmentTypeRepository $projectAttachmentTypeRepository
     * @param CompanySectorRepository         $companySectorRepository
     * @param UserInterface|Clients|null      $client
     *
     * @return Response
     */
    public function create(
        Request $request,
        ProjectManager $projectManager,
        ProjectCreationManager $projectCreationManager,
        CompaniesRepository $companiesRepository,
        ProjectAttachmentTypeRepository $projectAttachmentTypeRepository,
        CompanySectorRepository $companySectorRepository,
        ?UserInterface $client
    ) {
        $form = $this->createForm(ProjectEditType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Project $project */
            $project = $form->getData();
            $projectCreationManager->handleBlamableCreation($project, $client);

            return $this->redirectToRoute('project_creation_success');
        }

        return $this->render('project/create_project/create.html.twig', [
            'loanPeriods'     => $projectManager->getPossibleProjectPeriods(),
            'runs'            => $companiesRepository->findBy(['idCompany' => range(6, 45)], ['name' => 'ASC']),
            'attachmentTypes' => $projectAttachmentTypeRepository->getAttachmentTypes(),
            'sectors'         => $companySectorRepository->findBy([], ['idCompanySector' => 'ASC']),
            'form'            => $form->createView(),
        ]);
    }

    /**
     * @Route("/projet/depot/succes", name="project_creation_success", methods={"GET"})
     */
    public function createSuccess()
    {
    }
}
