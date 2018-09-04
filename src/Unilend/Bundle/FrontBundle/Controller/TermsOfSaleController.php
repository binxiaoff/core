<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, Request, Response};
use Unilend\Bundle\CoreBusinessBundle\Entity\{AcceptationsLegalDocs, Tree};
use Unilend\Bundle\CoreBusinessBundle\Service\Document\LenderTermsOfSaleGenerator;
use Unilend\Bundle\CoreBusinessBundle\Service\TermsOfSaleManager;

class TermsOfSaleController extends Controller
{
    const ERROR_CANNOT_FIND_TOS          = 'cannot-find-tos';
    const ERROR_CANNOT_FIND_CLIENT       = 'cannot-find-client';
    const ERROR_CANNOT_FIND_ACCEPTED_TOS = 'cannot-find-accepted-tos';
    const ERROR_ACCESS_DENIED            = 'access-denied';
    const ERROR_EXCEPTION_OCCURRED       = 'exception-occurred';
    const ERROR_UNKNOWN                  = 'unknown';

    const ROUTE_PARAMETER_LEGAL_ENTITY = 'morale';

    /** @var LoggerInterface  */
    private $logger;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LenderTermsOfSaleGenerator */
    private $lenderTermsOfSaleGenerator;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;

    /**
     * @Route("/pdf/cgv/{clientHash}/{idTree}", name="lender_terms_of_sale_pdf", requirements={"clientHash": "[0-9a-f-]{32,36}", "idTree": "\d+"})
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param string $clientHash
     * @param int    $idTree
     *
     * @return Response
     */
    public function lenderTermsOfSaleDownloadAction(string $clientHash, int $idTree)
    {
        //TODO create constructor once TECH-494 is merged
        $this->logger                     = $this->get('logger');
        $this->entityManager              = $this->get('doctrine.orm.entity_manager');
        $this->lenderTermsOfSaleGenerator = $this->get(LenderTermsOfSaleGenerator::class);
        $this->termsOfSaleManager         = $this->get('unilend.service.terms_of_sale_manager');

        /** @var Tree $termsOfSalesTree */
        $termsOfSalesTree = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Tree')
            ->findBy(['idTree' => $idTree]);

        if (null === $termsOfSalesTree) {
            return $this->getTermsOfSaleResponse(self::ERROR_CANNOT_FIND_TOS, $idTree);
        }

        $client = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Clients')
            ->findOneBy(['hash' => $clientHash]);

        if (null === $client) {
            return $this->getTermsOfSaleResponse(self::ERROR_CANNOT_FIND_CLIENT, $idTree);
        }

        //TODO compare user and client once clientrefacto has been merged
//        if ($user->getClientId() !== $loan->getIdLender()->getIdClient()->getIdClient()) {
//            return $this->getLoanErrorResponse(self::ERROR_ACCESS_DENIED, $loan);
//        }

//        if (ClientsStatus::STATUS_TO_BE_CHECKED > $user->getClientStatus()) {
//            header('Location: ' . $this->lurl . '/inscription-preteurs');
//            exit;
//        }

        /** @var AcceptationsLegalDocs $acceptedTos */
        $acceptedTos = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs')
            ->findOneBy(['idClient' => $client, 'idLegalDoc' => $idTree]);

        if (null === $acceptedTos) {
            return $this->getTermsOfSaleResponse(self::ERROR_CANNOT_FIND_ACCEPTED_TOS, $idTree);
        }

        try {
            if (false === $this->lenderTermsOfSaleGenerator->exists($acceptedTos)) {
                $this->lenderTermsOfSaleGenerator->generate($acceptedTos);
            }

            $filePath = $this->lenderTermsOfSaleGenerator->getPath($acceptedTos);
        } catch (\Exception $exception) {
            return $this->getTermsOfSaleResponse(self::ERROR_EXCEPTION_OCCURRED, $acceptedTos, $exception);
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
     * @param string $type
     *
     * @return Response
     */
    public function lenderTermsOfSalesAction(string $type = ''): Response
    {
        //TODO create constructor once TECH-494 is merged
        $this->termsOfSaleManager = $this->get('unilend.service.terms_of_sale_manager');
        $this->entityManager      = $this->get('doctrine.orm.entity_manager');

        //TODO check if lender is connected then redirect to PDF download once TECH-108 is merged

        if ($type === self::ROUTE_PARAMETER_LEGAL_ENTITY) {
            $idTree = $this->termsOfSaleManager->getCurrentVersionForLegalEntity();
        } else {
            $idTree = $this->termsOfSaleManager->getCurrentVersionForPerson();
        }

        $tree = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Tree')
            ->findOneBy(['idTree' => $idTree]);

        $this->get('unilend.frontbundle.seo_manager')->setCmsSeoData($tree);

        $content = $this->get(LenderTermsOfSaleGenerator::class)->getNonPersonalizedContent($tree->getIdTree(), $type);
        $cms  = [
            'title'         => $tree->getTitle(),
            'header_image'  => $tree->getImgMenu(),
            'left_content'  => '',
            'right_content' => $content
        ];

        return $this->render('cms_templates/template_cgv.html.twig', ['cms' => $cms]);
    }

    /**
     * @param string                     $error
     * @param int                        $idTree
     * @param AcceptationsLegalDocs|null $acceptedTermsOfSale
     * @param \Exception|null            $exception
     *
     * @return Response
     */
    private function getTermsOfSaleResponse(string $error, int $idTree, ?AcceptationsLegalDocs $acceptedTermsOfSale = null, ?\Exception $exception = null): Response
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

        $this->get('logger')->error($message, $context);

        $translator = $this->get('translator');

        return $this->render('exception/error.html.twig', [
            'errorTitle'   => $translator->trans('tos-pdf-download_' . $error . '-error-title'),
            'errorDetails' => $translator->trans('tos-pdf-download_error-details-contact-link', ['%contactUrl%' => $this->generateUrl('contact')])
        ])->setStatusCode(Response::HTTP_NOT_FOUND);
    }


    /**
     * @Route("/cgv-popup", name="lender_tos_popup", condition="request.isXmlHttpRequest()")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function lastTermsOfServiceAction(Request $request): Response
    {
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var UserLender $user */
        $user            = $this->getUser();
        $client          = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($user->getClientId());
        $tosDetails      = '';
        $newsletterOptIn = true;

        if (null !== $client) {
            $newsletterOptIn = empty($client->getOptin1());

            if ($request->isMethod(Request::METHOD_GET)) {
                $elementSlug = 'tos-new';
                /** @var \acceptations_legal_docs $acceptationsTos */
                $acceptationsTos = $entityManagerSimulator->getRepository('acceptations_legal_docs');

                if ($acceptationsTos->exist($client->getIdClient(), 'id_client')) {
                    $wallet                = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);
                    $newTermsOfServiceDate = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Date nouvelles CGV avec 2 mandats'])->getValue();
                    /** @var \loans $loans */
                    $loans = $entityManagerSimulator->getRepository('loans');

                    if (0 < $loans->counter('id_lender = ' . $wallet->getId() . ' AND added < "' . $newTermsOfServiceDate . '"')) {
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
                        $this->get('logger')->error('The block element ID: ' . $elements->id_element . ' doesn\'t exist');
                    }
                } else {
                    $this->get('logger')->error('The element slug: ' . $elementSlug . ' doesn\'t exist');
                }
            } elseif ($request->isMethod(Request::METHOD_POST)) {
                if ('true' === $request->request->get('terms')) {
                    try {
                        $this->get('unilend.service.terms_of_sale_manager')->acceptCurrentVersion($client);
                    } catch (OptimisticLockException $exception) {
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
                        $this->get(NewsletterManager::class)->subscribeNewsletter($client, $request->getClientIp());
                    } else {
                        $this->get(NewsletterManager::class)->unsubscribeNewsletter($client, $request->getClientIp());
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
