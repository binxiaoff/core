<?php

namespace Unilend\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{AcceptationsLegalDocs, Clients};
use Unilend\Repository\{AcceptationLegalDocsRepository, BlocsElementsRepository, ElementsRepository, TreeRepository};
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
     * @param TreeRepository                 $treeRepository
     * @param ServiceTermsManager            $serviceTermsManager
     * @param ServiceTermsGenerator          $serviceTermsGenerator
     *
     * @throws Exception
     *
     * @return Response
     */
    public function currentServiceTerms(
        ?UserInterface $client,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        TreeRepository $treeRepository,
        ServiceTermsManager $serviceTermsManager,
        ServiceTermsGenerator $serviceTermsGenerator
    ): Response {
        $legalDocsAcceptance = $acceptationLegalDocsRepository->findOneBy(['client' => $client, 'legalDoc' => $serviceTermsManager->getCurrentVersionId()]);

        if ($legalDocsAcceptance) {
            return $this->redirectToRoute('service_terms_pdf', ['idAcceptation' => $legalDocsAcceptance->getIdAcceptation()]);
        }

        $tree = $treeRepository->findOneBy(['idTree' => $serviceTermsManager->getCurrentVersionId()]);

        $content = $serviceTermsGenerator->getNonPersonalizedContent($tree->getIdTree());
        $cms     = [
            'title'         => $tree->getTitle(),
            'header_image'  => $tree->getImgMenu(),
            'left_content'  => '',
            'right_content' => $content,
        ];

        return $this->render('service_terms/view.html.twig', ['cms' => $cms]);
    }

    /**
     * @Route("/conditions-service-popup", name="service_terms_popup", condition="request.isXmlHttpRequest()", methods={"GET"})
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null     $client
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param ElementsRepository             $elementsRepository
     * @param BlocsElementsRepository        $blocsElementsRepository
     * @param LoggerInterface                $logger
     *
     * @return JsonResponse|Response
     */
    public function currentServiceTermsAcceptation(
        ?UserInterface $client,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        ElementsRepository $elementsRepository,
        BlocsElementsRepository $blocsElementsRepository,
        LoggerInterface $logger
    ): Response {
        $serviceTermsDetails = '';

        $elementSlug = 'service-terms-new';
        if ($acceptationLegalDocsRepository->findOneBy(['client' => $client])) {
            $elementSlug = 'service-terms-update';
        }

        $element = $elementsRepository->findOneBy(['slug' => $elementSlug]);
        if ($element) {
            $blockElement = $blocsElementsRepository->findOneBy(['idElement' => $element->getIdElement()]);

            if ($blockElement) {
                $serviceTermsDetails = $blockElement->getValue();
            } else {
                $logger->error('The block element ID: ' . $element->getIdElement() . ' doesn\'t exist');
            }
        } else {
            $logger->error('The element slug: ' . $elementSlug . ' doesn\'t exist');
        }

        return $this->render('service_terms/popup.html.twig', [
            'serviceTermsDetails' => $serviceTermsDetails,
        ]);
    }

    /**
     * @Route("/conditions-service-popup", name="service_terms_popup_accepted", condition="request.isXmlHttpRequest()", methods={"POST"})
     *
     * @Security("has_role('ROLE_LENDER')")
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
