<?php

namespace Unilend\Controller\Unilend;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\Clients;
use Unilend\Entity\ClientsMandats;
use Unilend\Entity\Companies;
use Unilend\Entity\ProjectBeneficialOwnerUniversign;
use Unilend\Entity\ProjectCgv;
use Unilend\Entity\Projects;
use Unilend\Entity\ProjectsPouvoir;
use Unilend\Entity\UniversignEntityInterface;
use Unilend\Entity\Virements;
use Unilend\Entity\WireTransferOutUniversign;

class UniversignController extends Controller
{
    public const SIGNATURE_TYPE_PROJECT = 'projet';

    /**
     * @Route(
     *     "/universign/{signatureType}/{signatureId}/{clientHash}",
     *     name="universign_signature_status",
     *     requirements={
     *         "signatureType": "^(projet|pouvoir|mandat|cgv-emprunteurs|virement-emprunteurs|beneficiaires-effectifs)$",
     *         "signatureId": "\d+",
     *         "clientHash": "[0-9a-f-]{32,36}"
     *     }
     * )
     *
     * @param string $signatureType
     * @param int    $signatureId
     * @param string $clientHash
     *
     * @return Response
     */
    public function universignStatusAction(string $signatureType, int $signatureId, string $clientHash)
    {
        $documents     = [];
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $client        = $entityManager->getRepository(Clients::class)->findOneBy(['hash' => $clientHash]);

        if (null === $client) {
            return $this->redirectToRoute('home');
        }

        switch ($signatureType) {
            case self::SIGNATURE_TYPE_PROJECT:
                $status  = [UniversignEntityInterface::STATUS_PENDING, UniversignEntityInterface::STATUS_SIGNED];
                $mandate = $entityManager->getRepository(ClientsMandats::class)
                    ->findOneBy(['idProject' => $signatureId, 'status' => $status], ['added' => 'DESC'])
                ;
                $proxy = $entityManager->getRepository(ProjectsPouvoir::class)
                    ->findOneBy(['idProject' => $signatureId, 'status' => $status], ['added' => 'DESC'])
                ;

                if (null === $mandate || null === $proxy) {
                    return $this->redirectToRoute('home');
                }

                if ($this->get('unilend.service.beneficial_owner_manager')->projectNeedsBeneficialOwnerDeclaration($proxy->getIdProject())) {
                    $beneficialOwnerDeclaration = $entityManager->getRepository(ProjectBeneficialOwnerUniversign::class)
                        ->findOneBy(['idProject' => $signatureId, 'status' => $status], ['added' => 'DESC'])
                    ;

                    if (null === $beneficialOwnerDeclaration) {
                        return $this->redirectToRoute('home');
                    }

                    $documents[] = $beneficialOwnerDeclaration;
                }

                $documents[] = $proxy;
                $documents[] = $mandate;

                break;
            case ProjectsPouvoir::DOCUMENT_TYPE:
                $documents[] = $entityManager->getRepository(ProjectsPouvoir::class)->find($signatureId);

                break;
            case ClientsMandats::DOCUMENT_TYPE:
                $documents[] = $entityManager->getRepository(ClientsMandats::class)->find($signatureId);

                break;
            case ProjectCgv::DOCUMENT_TYPE:
                $documents[] = $entityManager->getRepository(ProjectCgv::class)->find($signatureId);

                break;
            case WireTransferOutUniversign::DOCUMENT_TYPE:
                $documents[] = $entityManager->getRepository(WireTransferOutUniversign::class)->find($signatureId);

                break;
            case ProjectBeneficialOwnerUniversign::DOCUMENT_TYPE:
                $documents[] = $entityManager->getRepository(ProjectBeneficialOwnerUniversign::class)->find($signatureId);

                break;
            default:
                return $this->redirectToRoute('home');
        }

        foreach ($documents as $document) {
            $documentClient = null;

            switch (get_class($document)) {
                case ProjectsPouvoir::class:
                case ProjectCgv::class:
                    /** @var ProjectsPouvoir|ProjectCgv $document */
                    if (
                        $document->getIdProject() instanceof Projects
                        && $document->getIdProject()->getIdCompany() instanceof Companies
                    ) {
                        $documentClient = $document->getIdProject()->getIdCompany()->getIdClientOwner();
                    }

                    break;
                case ClientsMandats::class:
                    /** @var ClientsMandats $document */
                    if ($document->getIdClient() instanceof Clients) {
                        $documentClient = $document->getIdClient();
                    }

                    break;
                case WireTransferOutUniversign::class:
                    /** @var WireTransferOutUniversign $document */
                    if (
                        $document->getIdWireTransferOut() instanceof Virements
                        && $document->getIdWireTransferOut()->getClient() instanceof Clients
                    ) {
                        $documentClient = $document->getIdWireTransferOut()->getClient();
                    }

                    break;
                case ProjectBeneficialOwnerUniversign::class:
                    /** @var ProjectBeneficialOwnerUniversign $document */
                    if (
                        $document->getIdProject() instanceof Projects
                        && $document->getIdProject()->getIdCompany() instanceof Companies
                    ) {
                        $documentClient = $document->getIdProject()->getIdCompany()->getIdClientOwner();
                    }

                    break;
            }

            if (null === $documentClient || empty($documentClient->getIdClient()) || $documentClient !== $client) {
                return $this->redirectToRoute('home');
            }
        }

        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        $template          = [
            'documents'           => [],
            'borrowerAccountLink' => $this->generateUrl('borrower_account_profile'),
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
            } elseif ($document instanceof ProjectBeneficialOwnerUniversign) {
                $pdfLink = $this->generateUrl('beneficial_owner_declaration_pdf', ['idProject' => $document->getIdProject()->getIdProject(), 'clientHash' => $clientHash]);
            } elseif ($document instanceof ProjectCgv) {
                $pdfLink = $document->getUrlPath();
            } else {
                $pdfLink = $document->getUrlPdf();
            }

            $template['documents'][] = [
                'pdfLink'    => $pdfLink,
                'name'       => $this->getDocumentTypeTranslationLabel($document),
                'status'     => $this->getStatusTranslationLabel($document),
                'universign' => $document,
            ];
        }

        return $this->render('universign/universign_status.html.twig', $template);
    }

    /**
     * @Route(
     *     "/universign/pouvoir/{proxyId}/{universignUpdate}",
     *     name="universign_proxy_generation_no_update",
     *     requirements={"proxyId": "\d+", "universignUpdate": "\w+"}
     * )
     * @Route("/universign/pouvoir/{proxyId}", name="universign_proxy_generation", requirements={"proxyId": "\d+"})
     *
     * @param int         $proxyId
     * @param string|null $universignUpdate
     *
     * @return Response
     */
    public function createProxyAction($proxyId, $universignUpdate = null)
    {
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        $proxy             = $entityManager->getRepository(ProjectsPouvoir::class)->find($proxyId);

        if (
            $proxy
            && UniversignEntityInterface::STATUS_PENDING === $proxy->getStatus()
            && ('NoUpdateUniversign' == $universignUpdate && false === empty($proxy->getUrlUniversign()) || $universignManager->createProxy($proxy))
        ) {
            return $this->redirect($proxy->getUrlUniversign());
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/universign/mandat/{mandateId}", name="universign_mandate_generation", requirements={"mandateId": "\d+"})
     *
     * @param int $mandateId
     *
     * @return Response
     */
    public function createMandateAction($mandateId)
    {
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        $mandate           = $entityManager->getRepository(ClientsMandats::class)->find($mandateId);

        if (
            $mandate
            && UniversignEntityInterface::STATUS_PENDING === $mandate->getStatus()
            && (false === empty($mandate->getUrlUniversign()) || $universignManager->createMandate($mandate))
        ) {
            return $this->redirect($mandate->getUrlUniversign());
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/universign/projet/{projectId}", name="universign_project_generation", requirements={"projectId": "\d+"})
     *
     * @param int $projectId
     *
     * @return Response
     */
    public function createProjectAction($projectId)
    {
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $universignManager      = $this->get('unilend.frontbundle.service.universign_manager');
        $beneficialOwnerManager = $this->get('unilend.service.beneficial_owner_manager');
        $project                = $entityManager->getRepository(Projects::class)->find($projectId);
        $mandate                = $entityManager->getRepository(ClientsMandats::class)
            ->findOneBy(['idProject' => $projectId, 'status' => UniversignEntityInterface::STATUS_PENDING], ['added' => 'DESC'])
        ;
        $proxy = $entityManager->getRepository(ProjectsPouvoir::class)
            ->findOneBy(['idProject' => $projectId, 'status' => UniversignEntityInterface::STATUS_PENDING], ['added' => 'DESC'])
        ;

        if (null === $project || null === $mandate || null === $proxy) {
            return $this->redirectToRoute('home');
        }

        if ($beneficialOwnerManager->projectNeedsBeneficialOwnerDeclaration($project)) {
            $beneficialOwnerDeclaration = $entityManager->getRepository(ProjectBeneficialOwnerUniversign::class)
                ->findOneBy(['idProject' => $projectId, 'status' => UniversignEntityInterface::STATUS_PENDING], ['added' => 'DESC'])
            ;

            if (
                null !== $beneficialOwnerDeclaration
                && false === empty($proxy->getUrlUniversign())
                && false === empty($mandate->getUrlUniversign())
                && false === empty($beneficialOwnerDeclaration->getUrlUniversign())
                && $proxy->getUrlUniversign() === $mandate->getUrlUniversign()
                && $proxy->getUrlUniversign() === $beneficialOwnerDeclaration->getUrlUniversign()
                || $universignManager->createProject($project, $proxy, $mandate, $beneficialOwnerDeclaration)
            ) {
                return $this->redirect($proxy->getUrlUniversign());
            }
        }

        if (
            false === empty($proxy->getUrlUniversign())
            && false === empty($mandate->getUrlUniversign())
            && $proxy->getUrlUniversign() === $mandate->getUrlUniversign()
            || $universignManager->createProject($project, $proxy, $mandate)
        ) {
            return $this->redirect($proxy->getUrlUniversign());
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/universign/cgv_emprunteurs/{serviceTermsId}/{serviceTermsName}", name="universign_service_terms_generation", requirements={"serviceTermsId": "\d+"})
     *
     * @param int    $serviceTermsId
     * @param string $serviceTermsName
     *
     * @return Response
     */
    public function createServiceTermsAction($serviceTermsId, $serviceTermsName)
    {
        $entityManager       = $this->get('doctrine.orm.entity_manager');
        $universignManager   = $this->get('unilend.frontbundle.service.universign_manager');
        $projectServiceTerms = $entityManager->getRepository(ProjectCgv::class)->find($serviceTermsId);

        if ($projectServiceTerms && UniversignEntityInterface::STATUS_PENDING == $projectServiceTerms->getStatus() && $serviceTermsName === $projectServiceTerms->getName()) {
            $serviceTermsLastUpdateDate = $projectServiceTerms->getLastUpdated();
            if ($serviceTermsLastUpdateDate->format('Y-m-d') === date('Y-m-d') && false === empty($projectServiceTerms->getUrlUniversign()) || $universignManager->createServiceTerms($projectServiceTerms)) {
                return $this->redirect($projectServiceTerms->getUrlUniversign());
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/pdf/virement_emprunteurs/{wireTransferOutId}/demande_virement_tiers_{clientHash}.pdf", name="wire_transfer_out_request_pdf", requirements={"wireTransferOutId": "\d+", "clientHash": "[0-9a-f-]{32,36}"})
     *
     * @param string $clientHash
     * @param int    $wireTransferOutId
     *
     * @return Response
     */
    public function createWireTransferOutRequestAction($clientHash, $wireTransferOutId)
    {
        $entityManager   = $this->get('doctrine.orm.entity_manager');
        $client          = $entityManager->getRepository(Clients::class)->findOneBy(['hash' => $clientHash]);
        $wireTransferOut = $entityManager->getRepository(Virements::class)->find($wireTransferOutId);

        if (null === $wireTransferOut || null === $client) {
            return $this->redirectToRoute('home');
        }

        $company        = $wireTransferOut->getProject()->getIdCompany();
        $companyManager = $entityManager->getRepository(Clients::class)->find($company->getIdClientOwner());
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
                ->setStatus(UniversignEntityInterface::STATUS_PENDING)
            ;
            $entityManager->persist($universign);
            $entityManager->flush($universign);
        }

        if (false === file_exists($wireTransferOutPdfRoot . DIRECTORY_SEPARATOR . $universign->getName())) {
            $company            = $wireTransferOut->getProject()->getIdCompany();
            $companyManager     = $entityManager->getRepository(Clients::class)->find($company->getIdClientOwner());
            $bankAccount        = $wireTransferOut->getBankAccount();
            $destinationCompany = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $bankAccount->getIdClient()->getIdClient()]);
            $pdfContent         = $this->renderView('/pdf/wire_transfer_out/borrower_request_third_party.html.twig', [
                'companyManagerName'      => $companyManager->getLastName(),
                'companyManagerFirstName' => $companyManager->getFirstName(),
                'companyManagerFunction'  => $companyManager->getFonction(),
                'companyName'             => $company->getName(),
                'amount'                  => $this->get('currency_formatter')->formatCurrency(bcdiv($wireTransferOut->getMontant(), 100, 4), 'EUR'),
                'destinationName'         => $bankAccount->getIdClient()->getLastName(),
                'destinationFirstName'    => $bankAccount->getIdClient()->getFirstName(),
                'destinationCompanyName'  => $destinationCompany->getName(),
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
                'margin-left'   => 15,
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

    /**
     * @Route("/pdf/beneficiaires-effectifs/{clientHash}/{idProject}",
     *     name="beneficial_owner_declaration_pdf",
     *     requirements={"idProject": "\d+", "clientHash": "[0-9a-f-]{32,36}"}
     * )
     *
     * @param string $clientHash
     * @param int    $idProject
     *
     * @return Response
     */
    public function createBeneficialOwnerDeclarationAction($clientHash, $idProject)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $client        = $entityManager->getRepository(Clients::class)->findOneBy(['hash' => $clientHash]);
        $project       = $entityManager->getRepository(Projects::class)->find($idProject);

        $beneficialOwnerManager = $this->get('unilend.service.beneficial_owner_manager');
        $return                 = $beneficialOwnerManager->createProjectBeneficialOwnerDeclaration($project, $client);

        if ('read' === $return['action']) {
            return new BinaryFileResponse($return['path']);
        }

        if ('redirect' === $return['action']) {
            return $this->redirect($return['url']);
        }

        if ('sign' === $return['action']) {
            return $this->redirect($return['url']);
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @param UniversignEntityInterface $universign
     *
     * @return string|null
     */
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
                $logger->warning(
                    'Unknown service terms status (' . $universign->status . ') - Cannot create PDF for Universign (project ' . $universign->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $universign->id_project]
                );

                return null;
        }
    }

    /**
     * @param UniversignEntityInterface $universign
     *
     * @return string|null
     */
    private function getDocumentTypeTranslationLabel(UniversignEntityInterface $universign)
    {
        switch (get_class($universign)) {
            case ClientsMandats::class:
                return 'mandate';
            case ProjectsPouvoir::class:
                return 'proxy';
            case ProjectCgv::class:
                return 'service-terms';
            case WireTransferOutUniversign::class:
                return 'wire-transfer-out';
            case ProjectBeneficialOwnerUniversign::class:
                return 'beneficial-owner';
            default:
                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning(
                    'Unknown service terms status (' . $universign->status . ') - Cannot create PDF for Universign (project ' . $universign->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $universign->id_project]
                );

                return null;
        }
    }
}
