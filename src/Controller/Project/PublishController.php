<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Project, ProjectStatusHistory, TrancheAttribute};
use Unilend\Form\Foncaris\{FoncarisRequestType, FoncarisTrancheAttributeType};
use Unilend\Message\Project\ProjectPublished;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\ProjectStatusManager;

class PublishController extends AbstractController
{
    /**
     * @Route(
     *     "/projet/{hash}/proposer-au-financement/instructions",
     *     name="project_publish_instructions",
     *     requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}
     * )
     *
     * @IsGranted("edit", subject="project")
     *
     * @param Project                    $project
     * @param UserInterface|Clients|null $user
     * @param Request                    $request
     * @param ProjectRepository          $projectRepository
     * @param ProjectStatusManager       $projectStatusManager
     * @param MessageBusInterface        $messageBus
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function instructions(
        Project $project,
        ?UserInterface $user,
        Request $request,
        ProjectRepository $projectRepository,
        ProjectStatusManager $projectStatusManager,
        MessageBusInterface $messageBus
    ) {
        $form = $this->createForm(FoncarisRequestType::class);

        foreach ($project->getTranches() as $tranche) {
            $form->add($tranche->getId(), FoncarisTrancheAttributeType::class);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $foncarisRequest = $form->getData();
            $project->setFoncarisGuarantee($foncarisRequest['guarantee']);
            $projectStatusManager->addProjectStatus($user, ProjectStatusHistory::STATUS_PUBLISHED, $project);

            if ($project->needFoncarisGuarantee()) {
                foreach ($project->getTranches() as $tranche) {
                    $greenId        = $form->get($tranche->getid())->get('greenId')->getData();
                    $trancheGreenId = new TrancheAttribute(TrancheAttribute::ATTRIBUTE_CREDIT_AGRICOLE_GREEN_ID, (string) $greenId);

                    $fundingType        = $form->get($tranche->getid())->get('fundingType')->getData();
                    $trancheFundingType = new TrancheAttribute(TrancheAttribute::ATTRIBUTE_FONCARIS_FUNDING_TYPE, (string) $fundingType->getId());

                    $fundingSecurity        = $form->get($tranche->getid())->get('security')->getData();
                    $trancheFundingSecurity = new TrancheAttribute(TrancheAttribute::ATTRIBUTE_FONCARIS_FUNDING_SECURITY, (string) $fundingSecurity->getId());

                    $tranche
                        ->addTrancheAttribute($trancheFundingType)
                        ->addTrancheAttribute($trancheFundingSecurity)
                        ->addTrancheAttribute($trancheGreenId)
                    ;
                }
            }

            $projectRepository->save($project);

            $messageBus->dispatch(new ProjectPublished($project->getId()));

            return $this->redirectToRoute('project_publish_confirmation', ['hash' => $project->getHash()]);
        }

        return $this->render('project/publish/instructions.html.twig', [
            'form'    => $form->createView(),
            'project' => $project,
        ]);
    }

    /**
     * @Route(
     *     "/projet/{hash}/proposer-au-financement/confirmation",
     *     name="project_publish_confirmation",
     *     requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}
     * )
     *
     * @IsGranted("edit", subject="project")
     *
     * @param Project $project
     *
     * @return Response
     */
    public function confirmation(Project $project)
    {
        return $this->render('project/publish/confirmation.html.twig', [
            'projectLink' => $this->get('router')->generate('edit_project_details', ['hash' => $project->getHash()]),
        ]);
    }
}
