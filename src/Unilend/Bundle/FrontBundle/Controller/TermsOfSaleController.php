<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, JsonResponse, Request, Response};
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\{AcceptationsLegalDocs, Clients, Tree, Wallet, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\Document\LenderTermsOfSaleGenerator;
use Unilend\Bundle\CoreBusinessBundle\Service\{NewsletterManager, TermsOfSaleManager};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
//use Unilend\Bundle\FrontBundle\Service\SeoManager;

class TermsOfSaleController extends Controller
{
    const ERROR_CANNOT_FIND_TOS          = 'cannot-find-tos';
    const ERROR_CANNOT_FIND_CLIENT       = 'cannot-find-client';
    const ERROR_CANNOT_FIND_ACCEPTED_TOS = 'cannot-find-accepted-tos';
    const ERROR_ACCESS_DENIED            = 'access-denied';
    const ERROR_EXCEPTION_OCCURRED       = 'exception-occurred';
    const ERROR_UNKNOWN                  = 'unknown';

    const ROUTE_PARAMETER_LEGAL_ENTITY = 'morale';

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LenderTermsOfSaleGenerator */
    private $lenderTermsOfSaleGenerator;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;
    /** @var LoggerInterface  */
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
    )
    {
        $this->entityManager              = $entityManager;
        $this->termsOfSaleManager         = $termsOfSaleManager;
        $this->lenderTermsOfSaleGenerator = $lenderTermsOfSaleGenerator;
        $this->logger                     = $logger;
    }

    /**
     * @Route("/pdf/cgv/{clientHash}/{idTree}", name="lender_terms_of_sale_pdf", requirements={"clientHash": "[0-9a-f-]{32,36}", "idTree": "\d+"})
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     * @param string                     $clientHash
     * @param int                        $idTree
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
        } catch (\Exception $exception) {
            return $this->getTermsOfSaleErrorResponse($translator, self::ERROR_EXCEPTION_OCCURRED, $idTree, $acceptedTos, $exception);
        }

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type'        => $this->lenderTermsOfSaleGenerator->getContentType(),
            'Content-Length'      => filesize($filePath),
            'Content-Disposition' => 'attachement; filename="' . $acceptedTos->getPdfName() . '"'
        ]);
    }

    /**
     * @Route("/cgv_preteurs/{type}", name="lenders_terms_of_sales", requirements={"type": "morale"})
     *
     * @param UserInterface|Clients|null $client
     * @param string                     $type
     * @param SeoManager                 $seoManager
     *
     * @return Response
     */
    public function lenderTermsOfSalesAction(?UserInterface $client, string $type = ''/*, SeoManager $seoManager*/): Response
    {
        $idTree = $this->termsOfSaleManager->getCurrentVersionForPerson();

        if ($type === self::ROUTE_PARAMETER_LEGAL_ENTITY) {
            $idTree = $this->termsOfSaleManager->getCurrentVersionForLegalEntity();
        }

        if ($client instanceof Clients) {
            $accepted = $this->entityManager
                ->getRepository(AcceptationsLegalDocs::class)
                ->findOneBy(['idClient' => $client], ['added' => 'DESC']);

            if ($accepted->getIdLegalDoc() === $idTree) {
                $this->redirectToRoute('lender_terms_of_sale_pdf', ['clientHash' => $client->getHash(), 'idTree' => $accepted->getIdLegalDoc()]);
            }
        }

        $tree = $this->entityManager
            ->getRepository(Tree::class)
            ->findOneBy(['idTree' => $idTree]);

//        $seoManager->setCmsSeoData($tree);

        $content = $this->lenderTermsOfSaleGenerator->getNonPersonalizedContent($tree->getIdTree(), $type);
        $cms     = [
            'title'         => $tree->getTitle(),
            'header_image'  => $tree->getImgMenu(),
            'left_content'  => '',
            'right_content' => $content
        ];

        return $this->render('terms_of_sale/template_cgv.html.twig', ['cms' => $cms]);
    }

    /**
     * @param TranslatorInterface        $translator
     * @param string                     $error
     * @param int                        $idTree
     * @param AcceptationsLegalDocs|null $acceptedTermsOfSale
     * @param \Exception|null            $exception
     *
     * @return Response
     */
    private function getTermsOfSaleErrorResponse(
        TranslatorInterface $translator,
        string $error,
        int $idTree,
        ?AcceptationsLegalDocs $acceptedTermsOfSale = null,
        ?\Exception $exception = null
    ): Response
    {
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
                    'id_client'      => $acceptedTermsOfSale->getIdClient()
                ];
                break;
            case self::ERROR_EXCEPTION_OCCURRED:
                $message = 'Loan contract could not be displayed: exception occurred - Message: ' . $exception->getMessage();
                $context = [
                    'id_acceptation' => $acceptedTermsOfSale->getIdAcceptation(),
                    'id_client'      => $acceptedTermsOfSale->getIdClient(),
                    'file'           => $exception->getFile(),
                    'line'           => $exception->getLine()
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
            'errorDetails' => $translator->trans('tos-pdf-download_error-details-contact-link', ['%contactUrl%' => $this->generateUrl('contact')])
        ])->setStatusCode(Response::HTTP_NOT_FOUND);
    }


    /**
     * @Route("/cgv-popup", name="lender_tos_popup", condition="request.isXmlHttpRequest()")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     * @param EntityManagerSimulator     $entityManagerSimulator
     * @param NewsletterManager          $newsletterManager
     *
     * @return JsonResponse|Response
     */
    public function lastTermsOfServiceAction(Request $request, ?UserInterface $client, EntityManagerSimulator $entityManagerSimulator, NewsletterManager $newsletterManager): Response
    {
        $tosDetails      = '';
        $newsletterOptIn = true;

        if (null !== $client) {
            $newsletterOptIn = empty($client->getOptin1());

            if ($request->isMethod(Request::METHOD_GET)) {
                $elementSlug = 'tos-new';
                /** @var \acceptations_legal_docs $acceptationsTos */
                $acceptationsTos = $entityManagerSimulator->getRepository('acceptations_legal_docs');

                if ($acceptationsTos->exist($client->getIdClient(), 'id_client')) {
                    $wallet                = $this->entityManager->getRepository(Wallet::class)->getWalletByType($client->getIdClient(), WalletType::LENDER);
                    $newTermsOfServiceDate = $this->termsOfSaleManager->getDateOfNewTermsOfSaleWithTwoMandates();
                    /** @var \loans $loans */
                    $loans = $entityManagerSimulator->getRepository('loans');

                    if (0 < $loans->counter('id_wallet = ' . $wallet->getId() . ' AND added < "' . $newTermsOfServiceDate->format('Y-m-d H:i:s') . '"')) {
                        $elementSlug = 'tos-update-lended';
                    } else {
                        $elementSlug = 'tos-update';
                    }
                }

                /** @var \elements $elements */
                $elements = $entityManagerSimulator->getRepository('elements');

                if ($elements->get($elementSlug, 'slug')) {
                    /** @var \blocs_elements $blockElement */
                    $blockElement = $entityManagerSimulator->getRepository('blocs_elements');

                    if ($blockElement->get($elements->id_element, 'id_element')) {
                        $tosDetails = $blockElement->value;
                    } else {
                        $this->logger->error('The block element ID: ' . $elements->id_element . ' doesn\'t exist');
                    }
                } else {
                    $this->logger->error('The element slug: ' . $elementSlug . ' doesn\'t exist');
                }
            } elseif ($request->isMethod(Request::METHOD_POST)) {
                if ('true' === $request->request->get('terms')) {
                    try {
                        $this->termsOfSaleManager->acceptCurrentVersion($client);
                    } catch (\Exception $exception) {
                        $this->get('logger')->error('TOS could not be accepted by lender ' . $client->getIdClient() . ' - Message: ' . $exception->getMessage(), [
                            'id_client' => $client->getIdClient(),
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'file'      => $exception->getFile(),
                            'line'      => $exception->getLine()
                        ]);
                    }
                }

                if ($newsletterOptIn) {
                    if ('true' === $request->request->get('newsletterOptIn')) {
                        $newsletterManager->subscribeNewsletter($client, $request->getClientIp());
                    } else {
                        $newsletterManager->unsubscribeNewsletter($client, $request->getClientIp());
                    }
                }

                return $this->json([]);
            }
        }

        return $this->render('partials/lender_tos_popup.html.twig', [
            'tosDetails'      => $tosDetails,
            'newsletterOptIn' => $newsletterOptIn
        ]);
    }
}
