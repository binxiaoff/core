<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{AcceptationsLegalDocs, Clients};
use Unilend\Repository\AcceptationLegalDocsRepository;
use Unilend\Service\ServiceTerms\ServiceTermsGenerator;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class ServiceTermsController extends AbstractController
{
    /**
     * @Route("/pdf/conditions-service/{idAcceptation}", name="service_terms_pdf", requirements={"idAcceptation": "\d+"})
     *
     * @param AcceptationsLegalDocs $acceptationsLegalDoc
     * @param ServiceTermsGenerator $serviceTermsGenerator
     *
     * @throws Exception
     *
     * @return BinaryFileResponse
     */
    public function serviceTermsDownload(
        AcceptationsLegalDocs $acceptationsLegalDoc,
        ServiceTermsGenerator $serviceTermsGenerator
    ): BinaryFileResponse {
        $this->denyAccessUnlessGranted('download', $acceptationsLegalDoc);

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
     * @Route("/conditions-service-popup", name="service_terms_popup", condition="request.isXmlHttpRequest()", methods={"GET"})
     *
     * @param UserInterface|Clients|null     $client
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param ServiceTermsManager            $serviceTermsManager
     *
     * @return JsonResponse|Response
     */
    public function currentServiceTermsAcceptation(
        ?UserInterface $client,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        ServiceTermsManager $serviceTermsManager
    ): Response {
        $currentServiceTerms  = $serviceTermsManager->getCurrentVersion();
        $acceptationLegalDocs = $acceptationLegalDocsRepository->findOneBy(['client' => $client]);

        return $this->render('service_terms/popup.html.twig', [
            'serviceTermsDetails' => null === $acceptationLegalDocs ? $currentServiceTerms->getFirstTimeInstruction() : $currentServiceTerms->getDifferentialInstruction(),
        ]);
    }

    /**
     * @Route("/conditions-service-popup", name="service_terms_popup_accepted", condition="request.isXmlHttpRequest()", methods={"POST"})
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     * @param ServiceTermsManager        $serviceTermsManager
     * @param LoggerInterface            $logger
     *
     * @return JsonResponse|Response
     */
    public function currentServiceTermsAccepted(Request $request, ?UserInterface $client, ServiceTermsManager $serviceTermsManager, LoggerInterface $logger): Response
    {
        if ('true' === $request->request->get('terms')) {
            try {
                $serviceTermsManager->acceptCurrentVersion($client);
            } catch (Exception $exception) {
                $logger->error('Service Terms could not be accepted by lender ' . $client->getIdClient() . ' - Message: ' . $exception->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                ]);
            }
        }

        return $this->json([]);
    }
}
