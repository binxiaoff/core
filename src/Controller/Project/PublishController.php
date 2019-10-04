<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\{FoncarisRequest, Project, ProjectStatus, TrancheAttribute};
use Unilend\Form\Foncaris\{FoncarisRequestType, FoncarisTrancheAttributeType};
use Unilend\Message\Project\ProjectPublished;
use Unilend\Repository\FoncarisRequestRepository;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\{Foncaris\GuaranteeRequestGenerator, User\RealUserFinder};

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
     * @param Project                   $project
     * @param Request                   $request
     * @param ProjectRepository         $projectRepository
     * @param FoncarisRequestRepository $foncarisRequestRepository
     * @param MessageBusInterface       $messageBus
     * @param RealUserFinder            $realUserFinder
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function instructions(
        Project $project,
        Request $request,
        ProjectRepository $projectRepository,
        FoncarisRequestRepository $foncarisRequestRepository,
        MessageBusInterface $messageBus,
        RealUserFinder $realUserFinder
    ) {
        // @todo don't allow go back on this page once form has been submitted
        $form = $this->createForm(FoncarisRequestType::class, (new FoncarisRequest())->setProject($project));

        foreach ($project->getTranches() as $tranche) {
            $form->add($tranche->getId(), FoncarisTrancheAttributeType::class, ['mapped' => false]);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $project->setCurrentStatus(ProjectStatus::STATUS_PUBLISHED, $realUserFinder);
            $foncarisRequest = $form->getData();

            if ($foncarisRequest->needFoncarisGuarantee()) {
                foreach ($project->getTranches() as $tranche) {
                    $greenId        = $form->get($tranche->getId())->get('greenId')->getData();
                    $trancheGreenId = new TrancheAttribute(TrancheAttribute::ATTRIBUTE_CREDIT_AGRICOLE_GREEN_ID, (string) $greenId);
                    $tranche->addTrancheAttribute($trancheGreenId);

                    $fundingType        = $form->get($tranche->getId())->get('fundingType')->getData();
                    $trancheFundingType = new TrancheAttribute(TrancheAttribute::ATTRIBUTE_FONCARIS_FUNDING_TYPE, (string) $fundingType->getId());
                    $tranche->addTrancheAttribute($trancheFundingType);

                    $fundingSecurities = $form->get($tranche->getId())->get('security')->getData();
                    foreach ($fundingSecurities as $fundingSecurity) {
                        $trancheFundingSecurity = new TrancheAttribute(TrancheAttribute::ATTRIBUTE_FONCARIS_FUNDING_SECURITY, (string) $fundingSecurity->getId());
                        $tranche->addTrancheAttribute($trancheFundingSecurity);
                    }
                }
            }

            $foncarisRequestRepository->save($foncarisRequest);
            $projectRepository->save($project);

            $messageBus->dispatch(new ProjectPublished($project));

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
     * @param Project                   $project
     * @param GuaranteeRequestGenerator $guaranteeRequestGenerator
     * @param FoncarisRequestRepository $foncarisRequestRepository
     *
     * @return Response
     */
    public function confirmation(Project $project, GuaranteeRequestGenerator $guaranteeRequestGenerator, FoncarisRequestRepository $foncarisRequestRepository)
    {
        $foncarisRequest = $foncarisRequestRepository->findOneBy(['project' => $project]);
        $guaranteeRequestGenerator->generate($foncarisRequest);

        return $this->render('project/publish/confirmation.html.twig', ['project' => $project]);
    }
}
