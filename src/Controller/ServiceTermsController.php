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
use Unilend\Service\Document\ServiceTermsGenerator;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class ServiceTermsController extends AbstractController
{
    public const ERROR_CANNOT_FIND_TOS          = 'cannot-find-tos';
    public const ERROR_CANNOT_FIND_CLIENT       = 'cannot-find-client';
    public const ERROR_CANNOT_FIND_ACCEPTED_TOS = 'cannot-find-accepted-tos';
    public const ERROR_ACCESS_DENIED            = 'access-denied';
    public const ERROR_EXCEPTION_OCCURRED       = 'exception-occurred';
    public const ERROR_UNKNOWN                  = 'unknown';

    public const ROUTE_PARAMETER_LEGAL_ENTITY = 'morale';

    /**
     * @Route("/pdf/cgu/{idAcceptation}", name="service_terms_pdf", requirements={"idAcceptation": "\d+"})
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

        if (false === $serviceTermsGenerator->exists($acceptationsLegalDoc)) {
            $serviceTermsGenerator->generate($acceptationsLegalDoc);
        }

        $file = $this->file($serviceTermsGenerator->getPath($acceptationsLegalDoc), $acceptationsLegalDoc->getPdfName());
        $file->headers->set('Content-Type', $serviceTermsGenerator->getContentType());

        return $file;
    }

    /**
     * @Route("/cgu", name="service_terms", methods={"GET"})
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

        return $this->render('service_terms/template_cgv.html.twig', ['cms' => $cms]);
    }

    /**
     * @Route("/cgv-popup", name="service_terms_popup", condition="request.isXmlHttpRequest()", methods={"GET"})
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

        $elementSlug = 'tos-new';
        if ($acceptationLegalDocsRepository->findOneBy(['client' => $client])) {
            $elementSlug = 'tos-update';
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

        return $this->render('partials/service_terms_popup.html.twig', [
            'serviceTermsDetails' => $serviceTermsDetails,
        ]);
    }

    /**
     * @Route("/cgv-popup", name="service_terms_popup_accepted", condition="request.isXmlHttpRequest()", methods={"POST"})
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
