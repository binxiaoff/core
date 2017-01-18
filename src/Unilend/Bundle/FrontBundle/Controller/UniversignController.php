<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\UniversignManager;

class UniversignController extends Controller
{
    /**
     * @Route(
     *     "/universign/{status}/pouvoir/{documentId}/{clientHash}",
     *     name="proxy_signature_status",
     *     requirements={"status": "\w+"},
     *     requirements={"documentId": "\d+"},
     *     requirements={"clientHash": "[0-9a-f-]{32,36}"}
     * )
     * @param string $status
     * @param int $documentId
     * @param string $clientHash
     * @return Response
     */
    public function proxySignatureStatusAction($status, $documentId, $clientHash)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        /** @var \projects_pouvoir $proxy */
        $proxy = $entityManager->getRepository('projects_pouvoir');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');

        if (
            $proxy->get($documentId)
            && $client->get($clientHash, 'hash')
            && $project->get($proxy->id_project)
            && $company->get($project->id_company)
            && $company->id_client_owner == $client->id_client
            && $proxy->status == \projects_pouvoir::STATUS_PENDING
        ) {

            switch ($status) {
                case 'success':
                    $universignManager->signProxy($proxy);
                    break;
                case 'cancel':
                    $proxy->status = \projects_pouvoir::STATUS_CANCELLED;
                    $proxy->update();
                    break;
                case 'fail':
                    $proxy->status = \projects_pouvoir::STATUS_FAILED;
                    $proxy->update();
                    break;
                default:
                    $logger->warning('Unknown proxy status (' . $status . ') - Cannot create PDF for Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);
                    return $this->redirectToRoute('home');
            }

            $proxyStatusLabel = $this->getProxyStatusLabel($proxy);

            if ($proxyStatusLabel) {
                $template = [
                    'pdf_link'    => $proxy->url_pdf,
                    'pdf_display' => ($proxy->status == \projects_pouvoir::STATUS_SIGNED),
                    'document'    => 'proxy',
                    'status'      => $proxyStatusLabel
                ];

                return $this->render('pages/universign.html.twig', $template);
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route(
     *     "/universign/{status}/mandat/{documentId}/{clientHash}",
     *     name="mandate_signature_status",
     *     requirements={"status": "\w+"},
     *     requirements={"documentId": "\d+"},
     *     requirements={"clientHash": "[0-9a-f-]{32,36}"}
     * )
     * @param string $status
     * @param int $documentId
     * @param string $clientHash
     * @return Response
     */
    public function mandateSignatureStatusAction($status, $documentId, $clientHash)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        /** @var \clients_mandats $mandate */
        $mandate = $entityManager->getRepository('clients_mandats');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');

        if (
            $client->get($clientHash, 'hash')
            && $mandate->get($documentId)
            && $mandate->id_client == $client->id_client
            && $mandate->status == \clients_mandats::STATUS_PENDING
        ) {
            switch ($status) {
                case 'success':
                    $universignManager->signMandate($mandate);
                    break;
                case 'cancel':
                    $mandate->status = \clients_mandats::STATUS_CANCELED;
                    $mandate->update();
                    break;
                case 'fail':
                    $mandate->status = \clients_mandats::STATUS_FAILED;
                    $mandate->update();
                    break;
                default:
                    $logger->warning('Unknown mandate status (' . $mandate->status . ') - Cannot create PDF for Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
                    return $this->redirectToRoute('home');
            }

            $mandateStatusLabel = $this->getMandateStatusLabel($mandate);

            if ($mandateStatusLabel) {
                $template = [
                    'pdf_link'    => $mandate->url_pdf,
                    'pdf_display' => ($mandate->status == \clients_mandats::STATUS_SIGNED),
                    'document'    => 'mandate',
                    'status'      => $mandateStatusLabel
                ];

                return $this->render('pages/universign.html.twig', $template);
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route(
     *     "/universign/{status}/cgv_emprunteurs/{documentId}/{clientHash}",
     *     name="tos_signature_status",
     *     requirements={"status": "\w+"},
     *     requirements={"documentId": "\d+"},
     *     requirements={"clientHash": "[0-9a-f-]{32,36}"}
     * )
     * @param string $status
     * @param int $documentId
     * @param string $clientHash
     * @return Response
     */
    public function tosSignatureStatusAction($status, $documentId, $clientHash)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        /** @var \project_cgv $tos */
        $tos = $entityManager->getRepository('project_cgv');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');

        if (
            $tos->get($documentId)
            && $client->get($clientHash, 'hash')
            && $project->get($tos->id_project)
            && $company->get($project->id_company)
            && $company->id_client_owner == $client->id_client
            && $tos->status == \project_cgv::STATUS_NO_SIGN
        ) {

            switch ($status) {
                case 'success':
                    $universignManager->signTos($tos);
                    break;
                case 'cancel':
                    $tos->status = \project_cgv::STATUS_SIGN_CANCELLED;
                    $tos->update();
                    break;
                case 'fail':
                    $tos->status = \project_cgv::STATUS_SIGN_FAILED;
                    $tos->update();
                    break;
                default:
                    $logger->warning('Tos unknown status', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $tos->id_project]);
                    return $this->redirectToRoute('home');
            }

            $tosStatusLabel = $this->getTosStatusLabel($tos);

            if ($tosStatusLabel) {
                $template = [
                    'pdf_link'    => $tos->getUrlPath(),
                    'pdf_display' => in_array($tos->status, [\project_cgv::STATUS_SIGN_UNIVERSIGN, \project_cgv::STATUS_SIGN_FO]),
                    'document'    => 'tos',
                    'status'      => $tosStatusLabel
                ];

                return $this->render('pages/universign.html.twig', $template);
            }
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
     * @return Response
     */
    public function createProxyAction($proxyId, $universignUpdate = null)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \projects_pouvoir $proxy */
        $proxy = $entityManager->getRepository('projects_pouvoir');
        /** @var UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($proxy->get($proxyId) && $proxy->status == \projects_pouvoir::STATUS_PENDING) {
            if ($universignUpdate == 'NoUpdateUniversign' && false === empty($proxy->url_universign) || $universignManager->createProxy($proxy)) {
                return $this->redirect($proxy->url_universign);
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/universign/mandat/{mandateId}", name="mandate_generation", requirements={"mandateId":"\d+"})
     *
     * @param int $mandateId
     * @return Response
     */
    public function createMandateAction($mandateId)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients_mandats $mandate */
        $mandate = $entityManager->getRepository('clients_mandats');
        /** @var \Unilend\Bundle\FrontBundle\Service\UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($mandate->get($mandateId) && $mandate->status == \clients_mandats::STATUS_PENDING) {
            if (false === empty($mandate->url_universign) || $universignManager->createMandate($mandate)) {
                return $this->redirect($mandate->url_universign);
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/universign/cgv_emprunteurs/{tosId}/{tosName}", name="tos_generation", requirements={"tosId":"\d+"})
     *
     * @param int    $tosId
     * @param string $tosName
     * @return Response
     */
    public function createTosAction($tosId, $tosName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \project_cgv $tos */
        $tos = $entityManager->getRepository('project_cgv');
        /** @var \Unilend\Bundle\FrontBundle\Service\UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($tos->get($tosId) && $tos->status == \project_cgv::STATUS_NO_SIGN && $tosName === $tos->name) {
            $tosLastUpdateDate = \DateTime::createFromFormat('Y-m-d H:i:s', $tos->updated);
            if ($tosLastUpdateDate->format('Y-m-d') === date('Y-m-d') && false === empty($tos->url_universign) || $universignManager->createTos($tos)) {
                return $this->redirect($tos->url_universign);
            }
        }

        return $this->redirectToRoute('home');
    }

    /**
     * @param \projects_pouvoir $proxy
     * @return null|string
     */
    private function getProxyStatusLabel(\projects_pouvoir $proxy)
    {
        switch ($proxy->status) {
            case \projects_pouvoir::STATUS_PENDING:
                return 'pending';
            case \projects_pouvoir::STATUS_SIGNED:
                return 'signed';
            case \projects_pouvoir::STATUS_CANCELLED:
                return 'cancel';
            case \projects_pouvoir::STATUS_FAILED:
                return 'fail';
            default:
                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning('Unknown proxy status (' . $proxy->status . ') - Cannot create PDF for Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);
                return null;
        }
    }

    /**
     * @param \clients_mandats $mandate
     * @return null|string
     */
    private function getMandateStatusLabel(\clients_mandats $mandate)
    {
        switch ($mandate->status) {
            case \clients_mandats::STATUS_SIGNED:
                return 'signed';
            case \clients_mandats::STATUS_PENDING:
                return 'pending';
            case \clients_mandats::STATUS_CANCELED:
                return 'cancel';
            case \clients_mandats::STATUS_FAILED:
                return 'fail';
            default:
                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning('Unknown mandate status (' . $mandate->status . ') - Cannot create PDF for Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
                return null;
        }
    }

    /**
     * @param \project_cgv $tos
     * @return null|string
     */
    private function getTosStatusLabel(\project_cgv $tos)
    {
        switch ($tos->status) {
            case \project_cgv::STATUS_SIGN_FO:
            case \project_cgv::STATUS_SIGN_UNIVERSIGN:
                return 'signed';
            case \project_cgv::STATUS_NO_SIGN:
                return 'pending';
            case \project_cgv::STATUS_SIGN_CANCELLED:
                return 'cancel';
            case \project_cgv::STATUS_SIGN_FAILED:
                return 'fail';
            default:
                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->warning('Unknown tos status (' . $tos->status . ') - Cannot create PDF for Universign (project ' . $tos->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $tos->id_project]);
                return null;
        }
    }
}
