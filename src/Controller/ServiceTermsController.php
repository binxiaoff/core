<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Unilend\Entity\{AcceptationsLegalDocs, Clients};
use Unilend\Repository\AcceptationLegalDocsRepository;
use Unilend\Service\ServiceTerms\ServiceTermsGenerator;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class ServiceTermsController extends AbstractController
{
    /**
     * @Route("/pdf/conditions-service/{idAcceptation}", name="service_terms_pdf", requirements={"idAcceptation": "\d+"})
     *
     * @param UserInterface|Clients|null $client
     * @param AcceptationsLegalDocs      $acceptationsLegalDoc
     * @param ServiceTermsGenerator      $serviceTermsGenerator
     *
     * @throws Exception
     *
     * @return Response
     */
    public function serviceTermsDownload(?UserInterface $client, AcceptationsLegalDocs $acceptationsLegalDoc, ServiceTermsGenerator $serviceTermsGenerator)
    {
        if ($client !== $acceptationsLegalDoc->getClient()) {
            $this->createAccessDeniedException();
        }

        $serviceTermsGenerator->generate($acceptationsLegalDoc);

        return $this->file($serviceTermsGenerator->getFilePath($acceptationsLegalDoc));
    }

    /**
     * @Route("/conditions-service", name="service_terms", methods={"GET"})
     *
     * @param UserInterface|Clients|null     $client
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param ServiceTermsManager            $serviceTermsManager
     *
     * @throws Exception
     *
     * @return Response
     */
    public function currentServiceTerms(?UserInterface $client, AcceptationLegalDocsRepository $acceptationLegalDocsRepository, ServiceTermsManager $serviceTermsManager): Response
    {
        $currentServiceTerms    = $serviceTermsManager->getCurrentVersion();
        $serviceTermsAcceptance = $acceptationLegalDocsRepository->findOneBy(['client' => $client, 'legalDoc' => $currentServiceTerms]);

        if ($serviceTermsAcceptance) {
            return $this->redirectToRoute('service_terms_pdf', ['idAcceptation' => $serviceTermsAcceptance->getIdAcceptation()]);
        }

        return $this->render('service_terms/view.html.twig', ['serviceTerms' => $currentServiceTerms]);
    }

    /**
     * @Route("/accepter-conditions-service", name="service_terms_accept")
     *
     * @param Request                        $request
     * @param UserInterface|Clients|null     $client
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param ServiceTermsManager            $serviceTermsManager
     * @param FormFactoryInterface           $formFactory
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return JsonResponse|Response
     */
    public function currentServiceTermsAcceptation(
        Request $request,
        UserInterface $client,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        ServiceTermsManager $serviceTermsManager,
        FormFactoryInterface $formFactory
    ): Response {
        $currentServiceTerms  = $serviceTermsManager->getCurrentVersion();
        $acceptationLegalDocs = $acceptationLegalDocsRepository->findOneBy(['client' => $client]);

        // TODO Duplicated code with some code in CALS-333
        $form = $formFactory->createBuilder()
            ->add(
                'cgu',
                CheckboxType::class,
                [
                    'constraints'                  => [new IsTrue()],
                    'label'                        => 'service-terms.label',
                    'label_translation_parameters' => [
                        'serviceTermsURI' => $this->generateUrl('service_terms'),
                    ],
                ]
            )->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $serviceTermsManager->acceptCurrentVersion($client);

            return $this->redirect($this->generateUrl('wallet'));
        }

        return $this->render('service_terms/accept.html.twig', [
            'serviceTermsDetails' => null === $acceptationLegalDocs ? $currentServiceTerms->getFirstTimeInstruction() : $currentServiceTerms->getDifferentialInstruction(),
            'form'                => $form->createView(),
        ]);
    }
}
