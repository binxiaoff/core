<?php

namespace Unilend\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{AcceptationsLegalDocs, Clients, Tree};
use Unilend\Repository\AcceptationLegalDocsRepository;
use Unilend\Repository\BlocsElementsRepository;
use Unilend\Repository\ElementsRepository;
use Unilend\Service\Document\LenderTermsOfSaleGenerator;
use Unilend\Service\TermsOfSaleManager;

class TermsOfSaleController extends AbstractController
{
    public const ERROR_CANNOT_FIND_TOS          = 'cannot-find-tos';
    public const ERROR_CANNOT_FIND_CLIENT       = 'cannot-find-client';
    public const ERROR_CANNOT_FIND_ACCEPTED_TOS = 'cannot-find-accepted-tos';
    public const ERROR_ACCESS_DENIED            = 'access-denied';
    public const ERROR_EXCEPTION_OCCURRED       = 'exception-occurred';
    public const ERROR_UNKNOWN                  = 'unknown';

    public const ROUTE_PARAMETER_LEGAL_ENTITY = 'morale';

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LenderTermsOfSaleGenerator */
    private $lenderTermsOfSaleGenerator;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface     $entityManager
     * @param LenderTermsOfSaleGenerator $lenderTermsOfSaleGenerator
     * @param TermsOfSaleManager         $termsOfSaleManager
     * @param LoggerInterface            $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LenderTermsOfSaleGenerator $lenderTermsOfSaleGenerator,
        TermsOfSaleManager $termsOfSaleManager,
        LoggerInterface $logger
    ) {
        $this->entityManager              = $entityManager;
        $this->termsOfSaleManager         = $termsOfSaleManager;
        $this->lenderTermsOfSaleGenerator = $lenderTermsOfSaleGenerator;
        $this->logger                     = $logger;
    }

    /**
     * @Route("/pdf/cgv/{clientHash}/{idTree}", name="lender_terms_of_sale_pdf", requirements={"clientHash": "[0-9a-f-]{32,36}", "idTree": "\d+"})
     *
     * @param UserInterface|Clients|null $client
     * @param string                     $clientHash
     * @param int                        $idTree
     * @param TranslatorInterface        $translator
     *
     * @return Response
     */
    public function lenderTermsOfSaleDownload(?UserInterface $client, string $clientHash, int $idTree, TranslatorInterface $translator)
    {
        if ($client instanceof Clients && $client->getHash() !== $clientHash) {
            return $this->getTermsOfSaleErrorResponse($translator, self::ERROR_ACCESS_DENIED, $idTree);
        }

        if ($client->isInSubscription()) {
            $this->redirectToRoute('lender_subscription_personal_information');
        }

        $termsOfSalesRepository = $this->entityManager->getRepository(Tree::class);
        $termsOfSalesTree       = $termsOfSalesRepository->findBy(['idTree' => $idTree]);

        if (null === $termsOfSalesTree) {
            return $this->getTermsOfSaleErrorResponse($translator, self::ERROR_CANNOT_FIND_TOS, $idTree);
        }

        $acceptedTosRepository = $this->entityManager->getRepository(AcceptationsLegalDocs::class);
        $acceptedTos           = $acceptedTosRepository->findOneBy(['idClient' => $client, 'idLegalDoc' => $idTree]);

        if (null === $acceptedTos) {
            return $this->getTermsOfSaleErrorResponse($translator, self::ERROR_CANNOT_FIND_ACCEPTED_TOS, $idTree);
        }

        try {
            if (false === $this->lenderTermsOfSaleGenerator->exists($acceptedTos)) {
                $this->lenderTermsOfSaleGenerator->generate($acceptedTos);
            }

            $filePath = $this->lenderTermsOfSaleGenerator->getPath($acceptedTos);
        } catch (Exception $exception) {
            return $this->getTermsOfSaleErrorResponse($translator, self::ERROR_EXCEPTION_OCCURRED, $idTree, $acceptedTos, $exception);
        }

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type'        => $this->lenderTermsOfSaleGenerator->getContentType(),
            'Content-Length'      => filesize($filePath),
            'Content-Disposition' => 'attachement; filename="' . $acceptedTos->getPdfName() . '"',
        ]);
    }

    /**
     * @Route("/cgu", name="terms_of_sales")
     *
     * @param UserInterface|Clients|null $client
     *
     * @throws Exception
     *
     * @return Response
     */
    public function termsOfSales(?UserInterface $client): Response
    {
        $idTree = $this->termsOfSaleManager->getCurrentVersionId();
        if ($this->termsOfSaleManager->hasAcceptedCurrentVersion($client)) {
            $this->redirectToRoute('lender_terms_of_sale_pdf', ['clientHash' => $client->getHash(), 'idTree' => $idTree]);
        }

        $tree = $this->entityManager
            ->getRepository(Tree::class)
            ->findOneBy(['idTree' => $idTree])
        ;

        $content = $this->lenderTermsOfSaleGenerator->getNonPersonalizedContent($tree->getIdTree());
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
     *
     * @return JsonResponse|Response
     */
    public function lastTermsOfSaleAction(
        Request $request,
        ?UserInterface $client,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository,
        ElementsRepository $elementsRepository,
        BlocsElementsRepository $blocsElementsRepository
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
                    $this->logger->error('The block element ID: ' . $element->getIdElement() . ' doesn\'t exist');
                }
            } else {
                $this->logger->error('The element slug: ' . $elementSlug . ' doesn\'t exist');
            }
        }
        if ($request->isMethod(Request::METHOD_POST)) {
            if ('true' === $request->request->get('terms')) {
                try {
                    $this->termsOfSaleManager->acceptCurrentVersion($client);
                } catch (Exception $exception) {
                    $this->get('logger')->error('TOS could not be accepted by lender ' . $client->getIdClient() . ' - Message: ' . $exception->getMessage(), [
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

    /**
     * @param TranslatorInterface        $translator
     * @param string                     $error
     * @param int                        $idTree
     * @param AcceptationsLegalDocs|null $acceptedTermsOfSale
     * @param Exception|null             $exception
     *
     * @return Response
     */
    private function getTermsOfSaleErrorResponse(
        TranslatorInterface $translator,
        string $error,
        int $idTree,
        ?AcceptationsLegalDocs $acceptedTermsOfSale = null,
        ?Exception $exception = null
    ): Response {
        $context = [];

        switch ($error) {
            case self::ERROR_CANNOT_FIND_TOS:
                $message = 'Terms of Sale with idTree' . $idTree . ' could not be displayed: cannot find idTree';
                $context = ['id_tree' => $idTree];

                break;
            case self::ERROR_CANNOT_FIND_CLIENT:
                $message = 'Terms of Sale with idTree ' . $idTree . ' could not be displayed: cannot find client';
                $context = ['id_tree' => $idTree];

                break;
            case self::ERROR_CANNOT_FIND_ACCEPTED_TOS:
                $message = 'Terms of Sale with idTree ' . $idTree . ' could not be displayed: cannot find accepted terms of sale for client';
                $context = ['id_tree' => $idTree];

                break;
            case self::ERROR_ACCESS_DENIED:
                $message = 'Terms of Sale with idTree ' . $idTree . ' could not be displayed: access denied';
                $context = [
                    'id_acceptation' => $acceptedTermsOfSale->getIdAcceptation(),
                    'id_client'      => $acceptedTermsOfSale->getClient(),
                ];

                break;
            case self::ERROR_EXCEPTION_OCCURRED:
                $message = 'Loan contract could not be displayed: exception occurred - Message: ' . $exception->getMessage();
                $context = [
                    'id_acceptation' => $acceptedTermsOfSale->getIdAcceptation(),
                    'id_client'      => $acceptedTermsOfSale->getClient(),
                    'file'           => $exception->getFile(),
                    'line'           => $exception->getLine(),
                ];

                break;
            default:
                $message = $error;
                $error   = self::ERROR_UNKNOWN;

                break;
        }

        $this->logger->error($message, $context);

        return $this->render('exception/error.html.twig', [
            'errorTitle'   => $translator->trans('tos-pdf-download_' . $error . '-error-title'),
            'errorDetails' => $translator->trans('tos-pdf-download_error-details-contact-link', ['%contactUrl%' => $this->generateUrl('contact')]),
        ])->setStatusCode(Response::HTTP_NOT_FOUND)
            ;
    }
}
