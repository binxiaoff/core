<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsMandats;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCgv;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsPouvoir;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\WireTransferOutUniversign;

class UniversignController extends Controller
{
    /**
     * As the status has been removed from the URL, we need this redirection action for the old callback url.
     * It can be delete in the next release after the "deblocage progressif" project.
     *
     * @Route(
     *     "/universign/{status}/{documentType}/{documentId}/{clientHash}",
     *     name="legacy_universign_signature_status",
     *     requirements={
     *         "status": "^(success|fail|cancel)$",
     *         "documentType": "^(pouvoir|mandat|cgv_emprunteurs|virement_emprunteurs)$",
     *         "documentId": "\d+",
     *         "clientHash": "[0-9a-f-]{32,36}"
     *     }
     * )
     * @param string $status
     * @param int    $documentId
     * @param string $documentType
     * @param string $clientHash
     *
     * @return Response
     */
    public function legacyUniversignStatusAction($status, $documentId, $documentType, $clientHash)
    {
        return $this->redirectToRoute('universign_signature_status', ['documentType' => str_replace('_', '-', $documentType), 'documentId' => $documentId, 'clientHash' => $clientHash]);
    }

    /**
     * @Route(
     *     "/universign/{signatureType}/{signatureId}/{clientHash}",
     *     name="universign_signature_status",
     *     requirements={
     *         "signatureType": "^(pouvoir|mandat|cgv-emprunteurs|virement-emprunteurs)$",
     *         "signatureId": "\d+",
     *         "clientHash": "[0-9a-f-]{32,36}"
     *     }
     * )
     * @param string $signatureType
     * @param int    $signatureId
     * @param string $clientHash
     *
     * @return Response
     */
    public function universignStatusAction($signatureType, $signatureId, $clientHash)
    {
        $documents     = [];
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $client        = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);

        if (null === $client) {
            return $this->redirectToRoute('home');
        }

        switch ($signatureType) {
            case ProjectsPouvoir::DOCUMENT_TYPE:
                $documents[] = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsPouvoir')->find($signatureId);
                break;
            case ClientsMandats::DOCUMENT_TYPE:
                $documents[] = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsMandats')->find($signatureId);
                break;
            case ProjectCgv::DOCUMENT_TYPE:
                $documents[] = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCgv')->find($signatureId);
                break;
            case WireTransferOutUniversign::DOCUMENT_TYPE:
                $documents[] = $entityManager->getRepository('UnilendCoreBusinessBundle:WireTransferOutUniversign')->find($signatureId);
                break;
            default:
                return $this->redirectToRoute('home');
        }

        foreach ($documents as $document) {
            $documentClientId = null;

            switch (get_class($document)) {
                case ProjectsPouvoir::class:
                case ProjectCgv::class:
                    /** @var ProjectsPouvoir|ProjectCgv $document */
                    if (
                        $document->getIdProject() instanceof Projects
                        && $document->getIdProject()->getIdCompany() instanceof Companies
                    ) {
                        $documentClientId = $document->getIdProject()->getIdCompany()->getIdClientOwner();
                    }
                    break;
                case ClientsMandats::class:
                    /** @var ClientsMandats $document */
                    if ($document->getIdClient() instanceof Clients) {
                        $documentClientId = $document->getIdClient()->getIdClient();
                    }
                    break;
                case WireTransferOutUniversign::class:
                    /** @var WireTransferOutUniversign $document */
                    if (
                        $document->getIdWireTransferOut() instanceof Virements
                        && $document->getIdWireTransferOut()->getClient() instanceof Clients
                    ) {
                        $documentClientId = $document->getIdWireTransferOut()->getClient()->getIdClient();
                    }
                    break;
            }

            if (null === $documentClientId || $documentClientId !== $client->getIdClient()) {
                return $this->redirectToRoute('home');
            }
        }

        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        $template          = [
            'documents'           => [],
            'borrowerAccountLink' => $this->generateUrl('borrower_account_profile')
        ];

        foreach ($documents as $document) {
            if (UniversignEntityInterface::STATUS_PENDING === $document->getStatus()) {
                $universignManager->sign($document);
            }

            if ($document instanceof WireTransferOutUniversign) {
                $pdfLink = $this->generateUrl(
                    'wire_transfer_out_request_pdf',
                    ['wireTransferOutId' => $document->getIdWireTransferOut()->getIdVirement(), 'clientHash' => $clientHash]
                );
            } elseif ($document instanceof ProjectCgv) {
                $pdfLink = $document->getUrlPath();
            } else {
                $pdfLink = $document->getUrlPdf();
            }

            $template['documents'][] = [
                'pdfLink'    => $pdfLink,
                'name'       => $this->getDocumentTypeTranslationLabel($document),
                'status'     => $this->getStatusTranslationLabel($document),
                'universign' => $document
            ];
        }

        return $this->render('pages/universign.html.twig', $template);
    }

    /**
     * @Route(
     *     "/universign/pouvoir/{proxyId}/{universignUpdate}",
     *     name="proxy_generation_no_update_universign",
     *     requirements={"proxyId":"\d+", "universignUpdate":"\w+"}
     * )
     * @Route("/universign/pouvoir/{proxyId}", name="proxy_generation", requirements={"proxyId":"\d+"})
     *
     * @param int         $proxyId
     * @param null|string $universignUpdate
     *
     * @return Response
     */
    public function createProxyAction($proxyId, $universignUpdate = null)
    {
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        $proxy             = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsPouvoir')->find($proxyId);

        if ($proxy && UniversignEntityInterface::STATUS_PENDING === $proxy->getStatus()) {
            if ($universignUpdate == 'NoUpdateUniversign' && false === empty($proxy->getUrlUniversign()) || $universignManager->createProxy($proxy)) {
                return $this->redirect($proxy->getUrlUniversign());
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/universign/mandat/{mandateId}", name="mandate_generation", requirements={"mandateId":"\d+"})
     *
     * @param int $mandateId
     *
     * @return Response
     */
    public function createMandateAction($mandateId)
    {
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        $mandate           = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsMandats')->find($mandateId);

        if ($mandate && $mandate->getStatus() == UniversignEntityInterface::STATUS_PENDING) {
            if (false === empty($mandate->getUrlUniversign()) || $universignManager->createMandate($mandate)) {
                return $this->redirect($mandate->getUrlUniversign());
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/universign/cgv_emprunteurs/{tosId}/{tosName}", name="tos_generation", requirements={"tosId":"\d+"})
     *
     * @param int    $tosId
     * @param string $tosName
     *
     * @return Response
     */
    public function createTosAction($tosId, $tosName)
    {
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        $tos               = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCgv')->find($tosId);

        if ($tos && $tos->getStatus() == UniversignEntityInterface::STATUS_PENDING && $tosName === $tos->getName()) {
            $tosLastUpdateDate = $tos->getLastUpdated();
            if ($tosLastUpdateDate->format('Y-m-d') === date('Y-m-d') && false === empty($tos->getUrlUniversign()) || $universignManager->createTos($tos)) {
                return $this->redirect($tos->getUrlUniversign());
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/pdf/virement_emprunteurs/{wireTransferOutId}/demande_virement_tiers_{clientHash}.pdf", name="wire_transfer_out_request_pdf", requirements={"wireTransferOutId":"\d+", "clientHash": "[0-9a-f-]{32,36}"})
     *
     * @param string  $clientHash
     * @param int     $wireTransferOutId
     *
     * @return Response
     */
    public function createWireTransferOutRequestAction($clientHash, $wireTransferOutId)
    {
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $client          = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);
        $wireTransferOut = $entityManager->getRepository('UnilendCoreBusinessBundle:Virements')->find($wireTransferOutId);

        if (null === $wireTransferOut || null === $client) {
            return $this->redirectToRoute('home');
        }

        $company        = $wireTransferOut->getProject()->getIdCompany();
        $companyManager = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($company->getIdClientOwner());
        if ($companyManager !== $client) {
            return $this->redirectToRoute('home');
        }

        $universign             = $wireTransferOut->getUniversign();
        $wireTransferOutPdfRoot = $this->getParameter('path.protected') . 'pdf/wire_transfer_out';

        if (
            $universign instanceof WireTransferOutUniversign
            && UniversignEntityInterface::STATUS_SIGNED === $universign->getStatus()
            && file_exists($wireTransferOutPdfRoot . DIRECTORY_SEPARATOR . $universign->getName())
        ) {
            return new BinaryFileResponse($wireTransferOutPdfRoot . DIRECTORY_SEPARATOR . $universign->getName());
        }

        if (null === $universign) {
            $universign = new WireTransferOutUniversign();
            $universign->setIdWireTransferOut($wireTransferOut)
                ->setName('demande-virement-tiers-' . $clientHash . '-' . $wireTransferOutId . '.pdf')
                ->setStatus(UniversignEntityInterface::STATUS_PENDING);
            $entityManager->persist($universign);
            $entityManager->flush($universign);
        }

        if (false === file_exists($wireTransferOutPdfRoot . DIRECTORY_SEPARATOR . $universign->getName())) {
            $company            = $wireTransferOut->getProject()->getIdCompany();
            $companyManager     = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($company->getIdClientOwner());
            $bankAccount        = $wireTransferOut->getBankAccount();
            $destinationCompany = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $bankAccount->getIdClient()->getIdClient()]);
            $pdfContent         = $this->renderView('/pdf/wire_transfer_out/borrower_request_third_party.html.twig', [
                'companyManagerName'      => $companyManager->getNom(),
                'companyManagerFirstName' => $companyManager->getPrenom(),
                'companyManagerFunction'  => $companyManager->getFonction(),
                'companyName'             => $company->getName(),
                'amount'                  => $this->get('currency_formatter')->formatCurrency(bcdiv($wireTransferOut->getMontant(), 100, 4), 'EUR'),
                'destinationName'         => $bankAccount->getIdClient()->getNom(),
                'destinationFirstName'    => $bankAccount->getIdClient()->getPrenom(),
                'destinationCompanyName'  => $destinationCompany->getName(),
                'iban'                    => $bankAccount->getIban(),
            ]);
            $snappy             = $this->get('knp_snappy.pdf');
            $outputFile         = $wireTransferOutPdfRoot . DIRECTORY_SEPARATOR . $universign->getName();
            $options            = [
                'footer-html'   => '',
                'header-html'   => '',
                'margin-top'    => 20,
                'margin-right'  => 15,
                'margin-bottom' => 10,
                'margin-left'   => 15
            ];
            $snappy->generateFromHtml($pdfContent, $outputFile, $options, true);
        }

        if (UniversignEntityInterface::STATUS_PENDING === $universign->getStatus()) {
            if ($universign->getUrlUniversign()) {
                return $this->redirect($universign->getUrlUniversign());
            }

            $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
            if ($universignManager->createWireTransferOutRequest($universign)) {
                return $this->redirect($universign->getUrlUniversign());
            }
        }

        return $this->redirectToRoute('home');
    }

    private function getStatusTranslationLabel(UniversignEntityInterface $universign)
    {
        switch ($universign->getStatus()) {
            case UniversignEntityInterface::STATUS_SIGNED:
                return 'signed';
            case UniversignEntityInterface::STATUS_PENDING:
                return 'pending';
            case UniversignEntityInterface::STATUS_CANCELED:
                return 'cancel';
            case UniversignEntityInterface::STATUS_FAILED:
                return 'fail';
            default:
                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning('Unknown tos status (' . $universign->status . ') - Cannot create PDF for Universign (project ' . $universign->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $universign->id_project]);
                return null;
        }
    }

    private function getDocumentTypeTranslationLabel(UniversignEntityInterface $universign)
    {
        switch (get_class($universign)) {
            case ClientsMandats::class:
                return 'mandate';
            case ProjectsPouvoir::class:
                return 'proxy';
            case ProjectCgv::class:
                return 'tos';
            case WireTransferOutUniversign::class:
                return 'wire-transfer-out';
            default:
                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning('Unknown tos status (' . $universign->status . ') - Cannot create PDF for Universign (project ' . $universign->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $universign->id_project]);
                return null;
        }
    }
}
