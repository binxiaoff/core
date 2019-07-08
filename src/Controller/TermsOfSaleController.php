<?php

namespace Unilend\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{AcceptationsLegalDocs, Clients};
use Unilend\Repository\{AcceptationLegalDocsRepository, BlocsElementsRepository, ElementsRepository, TreeRepository};
use Unilend\Service\Document\TermsOfSaleGenerator;
use Unilend\Service\TermsOfSale\TermsOfSaleManager;

class TermsOfSaleController extends AbstractController
{
    public const ERROR_CANNOT_FIND_TOS          = 'cannot-find-tos';
    public const ERROR_CANNOT_FIND_CLIENT       = 'cannot-find-client';
    public const ERROR_CANNOT_FIND_ACCEPTED_TOS = 'cannot-find-accepted-tos';
    public const ERROR_ACCESS_DENIED            = 'access-denied';
    public const ERROR_EXCEPTION_OCCURRED       = 'exception-occurred';
    public const ERROR_UNKNOWN                  = 'unknown';

    public const ROUTE_PARAMETER_LEGAL_ENTITY = 'morale';

    /**
     * @Route("/pdf/cgu/{idAcceptation}", name="terms_of_sale_pdf", requirements={"idAcceptation": "\d+"})
     *
     * @param UserInterface|Clients|null $client
     * @param AcceptationsLegalDocs      $acceptationsLegalDoc
     * @param TermsOfSaleGenerator       $termsOfSaleGenerator
     *
     * @throws Exception
     *
     * @return Response
     */
    public function termsOfSaleDownload(
        ?UserInterface $client,
        AcceptationsLegalDocs $acceptationsLegalDoc,
        TermsOfSaleGenerator $termsOfSaleGenerator
    ) {
        if ($client !== $acceptationsLegalDoc->getClient()) {
            $this->createAccessDeniedException();
        }

        if (false === $termsOfSaleGenerator->exists($acceptationsLegalDoc)) {
            $termsOfSaleGenerator->generate($acceptationsLegalDoc);
        }

        $filePath = $termsOfSaleGenerator->getPath($acceptationsLegalDoc);

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type'        => $termsOfSaleGenerator->getContentType(),
            'Content-Length'      => filesize($filePath),
            'Content-Disposition' => 'attachement; filename="' . $acceptationsLegalDoc->getPdfName() . '"',
        ]);
    }

    /**
     * @Route("/cgu", name="terms_of_sales")
     *
     * @param UserInterface|Clients|null     $client
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param TreeRepository                 $treeRepository
     * @param TermsOfSaleManager             $termsOfSaleManager
     * @param TermsOfSaleGenerator           $termsOfSaleGenerator
     *
     * @throws Exception
     *
     * @return Response
     */
    public function termsOfSales(
        ?UserInterface $client,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        TreeRepository $treeRepository,
        TermsOfSaleManager $termsOfSaleManager,
        TermsOfSaleGenerator $termsOfSaleGenerator
    ): Response {
        $legalDocsAcceptance = $acceptationLegalDocsRepository->findOneBy(['client' => $client, 'legalDoc' => $termsOfSaleManager->getCurrentVersionId()]);

        if ($legalDocsAcceptance) {
            return $this->redirectToRoute('terms_of_sale_pdf', ['idAcceptation' => $legalDocsAcceptance->getIdAcceptation()]);
        }

        $tree = $treeRepository->findOneBy(['idTree' => $termsOfSaleManager->getCurrentVersionId()]);

        $content = $termsOfSaleGenerator->getNonPersonalizedContent($tree->getIdTree());
        $cms     = [
            'title'         => $tree->getTitle(),
            'header_image'  => $tree->getImgMenu(),
            'left_content'  => '',
            'right_content' => $content,
        ];

        return $this->render('terms_of_sale/template_cgv.html.twig', ['cms' => $cms]);
    }

    /**
     * @Route("/cgv-popup", name="tos_popup", condition="request.isXmlHttpRequest()")
     *
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                        $request
     * @param UserInterface|Clients|null     $client
     * @param AcceptationLegalDocsRepository $acceptationLegalDocsRepository
     * @param ElementsRepository             $elementsRepository
     * @param BlocsElementsRepository        $blocsElementsRepository
     * @param TermsOfSaleManager             $termsOfSaleManager
     * @param LoggerInterface                $logger
     *
     * @return JsonResponse|Response
     */
    public function lastTermsOfSaleAction(
        Request $request,
        ?UserInterface $client,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        ElementsRepository $elementsRepository,
        BlocsElementsRepository $blocsElementsRepository,
        TermsOfSaleManager $termsOfSaleManager,
        LoggerInterface $logger
    ): Response {
        $tosDetails = '';

        if ($request->isMethod(Request::METHOD_GET)) {
            $elementSlug = 'tos-new';
            if ($acceptationLegalDocsRepository->findOneBy(['client' => $client])) {
                $elementSlug = 'tos-update';
            }

            $element = $elementsRepository->findOneBy(['slug' => $elementSlug]);
            if ($element) {
                $blockElement = $blocsElementsRepository->findOneBy(['idElement' => $element->getIdElement()]);

                if ($blockElement) {
                    $tosDetails = $blockElement->getValue();
                } else {
                    $logger->error('The block element ID: ' . $element->getIdElement() . ' doesn\'t exist');
                }
            } else {
                $logger->error('The element slug: ' . $elementSlug . ' doesn\'t exist');
            }
        }
        if ($request->isMethod(Request::METHOD_POST)) {
            if ('true' === $request->request->get('terms')) {
                try {
                    $termsOfSaleManager->acceptCurrentVersion($client);
                } catch (Exception $exception) {
                    $logger->error('TOS could not be accepted by lender ' . $client->getIdClient() . ' - Message: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine(),
                    ])
                    ;
                }
            }

            return $this->json([]);
        }

        return $this->render('partials/lender_tos_popup.html.twig', [
            'tosDetails' => $tosDetails,
        ]);
    }
}
