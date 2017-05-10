<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
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

    const DOCUMENT_TYPE_PROXY             = 'pouvoir';
    const DOCUMENT_TYPE_MANDATE           = 'mandat';
    const DOCUMENT_TYPE_TERM_OF_USER      = 'cgv-emprunteurs';
    const DOCUMENT_TYPE_WIRE_TRANSFER_OUT = 'virement-emprunteurs';

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
     *     "/universign/{documentType}/{documentId}/{clientHash}",
     *     name="universign_signature_status",
     *     requirements={
     *         "documentType": "^(pouvoir|mandat|cgv-emprunteurs|virement-emprunteurs)$",
     *         "documentId": "\d+",
     *         "clientHash": "[0-9a-f-]{32,36}"
     *     }
     * )
     * @param int    $documentId
     * @param string $documentType
     * @param string $clientHash
     *
     * @return Response
     */
    public function universignStatusAction($documentId, $documentType, $clientHash)
    {
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        $entityManager     = $this->get('doctrine.orm.entity_manager');

        switch ($documentType) {
            case self::DOCUMENT_TYPE_PROXY:
                $repository = 'UnilendCoreBusinessBundle:ProjectsPouvoir';
                break;
            case self::DOCUMENT_TYPE_MANDATE:
                $repository = 'UnilendCoreBusinessBundle:ClientsMandats';
                break;
            case self::DOCUMENT_TYPE_TERM_OF_USER:
                $repository = 'UnilendCoreBusinessBundle:ProjectCgv';
                break;
            case self::DOCUMENT_TYPE_WIRE_TRANSFER_OUT:
                $repository = 'UnilendCoreBusinessBundle:WireTransferOutUniversign';
                break;
            default :
                return $this->redirectToRoute('home');
        }

        /** @var UniversignEntityInterface|ProjectsPouvoir|ClientsMandats|ProjectCgv|WireTransferOutUniversign $universign */
        $universign         = $entityManager->getRepository($repository)->find($documentId);
        $client             = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);
        $clientIdUniversign = null;
        switch (get_class($universign)) {
            case ProjectsPouvoir::class:
            case ProjectCgv::class:
                if ($universign->getIdProject() instanceof Projects && $universign->getIdProject()->getIdCompany() instanceof Companies) {
                    $clientIdUniversign = $universign->getIdProject()->getIdCompany()->getIdClientOwner();
                }
                break;
            case ClientsMandats::class :
                if ($universign->getIdClient() instanceof Clients) {
                    $clientIdUniversign = $universign->getIdClient()->getIdClient();
                }
                break;
            case WireTransferOutUniversign::class:
                if ($universign->getIdWireTransferOut() instanceof Virements
                    && $universign->getIdWireTransferOut()->getClient() instanceof Clients
                ) {
                    $clientIdUniversign = $universign->getIdWireTransferOut()->getClient()->getIdClient();
                }
                break;
        }

        if ($universign && $client && $clientIdUniversign === $client->getIdClient() && UniversignEntityInterface::STATUS_PENDING === $universign->getStatus()) {
            $universignManager->sign($universign);

            if ($universign instanceof WireTransferOutUniversign) {
                $pdfLink = $this->generateUrl('wire_transfer_out_request_pdf', ['wireTransferOutId' => $universign->getIdWireTransferOut()->getIdVirement(), 'clientHash' => $clientHash]);
            } elseif ($universign instanceof ProjectCgv) {
                $pdfLink = $universign->getUrlPath();
            } else {
                $pdfLink = $universign->getUrlPdf();
            }

            return $this->render('pages/universign.html.twig', [
                'pdfLink'             => $pdfLink,
                'document'            => $this->getDocumentTypeTranslationLabel($universign),
                'universign'          => $universign,
                'status'              => $this->getStatusTranslationLabel($universign),
                'borrowerAccountLink' => $this->generateUrl('borrower_account_profile')
            ]);
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route(
     *     "/universign/pouvoir/{proxyId}/{universignUpdate}",
     *     name="proxy_generation_no_update_universign",
     *     requirements={"proxyId":"\d+"},
     *     requirements={"universignUpdate":"\w+"}
     *     )
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
        /** @var ProjectCgv $tos */
        $tos = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCgv')->find($tosId);

        if ($tos && $tos->getStatus() == UniversignEntityInterface::STATUS_PENDING && $tosName === $tos->getName()) {
            $tosLastUpdateDate = $tos->getUpdated();
            if ($tosLastUpdateDate->format('Y-m-d') === date('Y-m-d') && false === empty($tos->getUrlUniversign()) || $universignManager->createTos($tos)) {
                return $this->redirect($tos->getUrlUniversign());
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/pdf/virement_emprunteurs/{wireTransferOutId}/demande_virement_tiers_{clientHash}.pdf", name="wire_transfer_out_request_pdf", requirements={"wireTransferOutId":"\d+", "clientHash": "[0-9a-f-]{32,36}"})
     *
     * @param Request $request
     * @param string  $clientHash
     * @param int     $wireTransferOutId
     *
     * @return Response
     */
    public function createWireTransferOutRequestPdfAction(Request $request, $clientHash, $wireTransferOutId)
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $client        = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);
        /** @var Virements $wireTransferOut */
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
        if ($universign instanceof WireTransferOutUniversign
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
            $company           = $wireTransferOut->getProject()->getIdCompany();
            $companyManager    = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($company->getIdClientOwner());
            $bankAccount       = $wireTransferOut->getBankAccount();
            $pdfContent = $this->renderView('/pdf/wire_transfer_out/borrower_request_third_party.html.twig', [
                'companyManagerName'      => $companyManager->getNom(),
                'companyManagerFirstName' => $companyManager->getPrenom(),
                'companyManagerFunction'  => $companyManager->getFonction(),
                'companyName'             => $company->getName(),
                'amount'                  => $this->get('currency_formatter')->formatCurrency(bcdiv($wireTransferOut->getMontant(), 100, 4), 'EUR'),
                'destinationName'         => $bankAccount->getIdClient()->getNom(),
                'destinationFirstName'    => $bankAccount->getIdClient()->getPrenom(),
                'iban'                    => $bankAccount->getIban(),
            ]);
            $snappy     = $this->get('knp_snappy.pdf');
            $outputFile = $wireTransferOutPdfRoot . DIRECTORY_SEPARATOR . $universign->getName();
            $options    = [
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
