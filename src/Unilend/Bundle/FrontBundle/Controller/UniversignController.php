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
     * @Route("/universign/{status}/pouvoir/{documentId}/{clientHash}", name="proxy_signature_status", requirements={"documentId":"\d+"})
     * @param string $status
     * @param string $documentId
     * @param string $clientHash
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function proxySignatureStatusAction($status, $documentId, $clientHash)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        /** @var \projects_pouvoir $proxy */
        $proxy = $entityManager->getRepository('projects_pouvoir');
        $proxy->get($documentId);
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        $client->get($clientHash, 'hash');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        $project->get($proxy->id_project);
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');
        $company->get($project->id_company);

        if ($company->id_client_owner != $client->id_client || $proxy->status != \projects_pouvoir::STATUS_PENDING) {
            return $this->redirectToRoute('home');
        }

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
                $logger->notice('Unknown proxy status (' . $proxy->status . ') - Cannot create PDF for Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);
                return $this->redirectToRoute('home');
        }

        $proxyStatusLabel = $this->getProxyStatusLabel($proxy);

        if (null === $proxyStatusLabel) {
            return $this->redirectToRoute('home');
        }

        $template         = [
            'pdf_link'    => $proxy->url_pdf,
            'pdf_display' => ($proxy->status == \projects_pouvoir::STATUS_SIGNED),
            'faq_url'     => $settings->value,
            'document'    => 'proxy',
            'status'      => $proxyStatusLabel
        ];

        $logger->notice('Proxy status : ' . $proxyStatusLabel, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

        return $this->render('pages/universign.html.twig', $template);
    }

    /**
     * @Route("/universign/{status}/mandat/{documentId}/{clientHash}", name="mandate_signature_status", requirements={"documentId":"\d+"})
     * @param string $status
     * @param string $documentId
     * @param string $clientHash
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function mandateSignatureStatusAction($status, $documentId, $clientHash)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        /** @var \clients_mandats $mandate */
        $mandate = $entityManager->getRepository('clients_mandats');
        $mandate->get($documentId);
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        $client->get($clientHash, 'hash');

        if ($mandate->id_client != $client->id_client || $mandate->status != \clients_mandats::STATUS_PENDING) {
            return $this->redirectToRoute('home');
        }

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
                $logger->notice('Unknown mandate status (' . $mandate->status . ') - Cannot create PDF for Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
                return $this->redirectToRoute('home');
        }

        $mandateStatusLabel = $this->getMandateStatusLabel($mandate);

        if (null === $mandateStatusLabel) {
            return $this->redirectToRoute('home');
        }

        $template           = [
            'pdf_link'    => $mandate->url_pdf,
            'pdf_display' => ($mandate->status == \clients_mandats::STATUS_SIGNED),
            'faq_url'     => $settings->value,
            'document'    => 'mandate',
            'status'      => $mandateStatusLabel
        ];

        $logger->notice('Mandate status : ' . $mandateStatusLabel, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

        return $this->render('pages/universign.html.twig', $template);
    }

    /**
     * @Route("/universign/{status}/cgv_emprunteurs/{documentId}/{clientHash}", name="tos_signature_status", requirements={"documentId":"\d+"})
     * @param string $status
     * @param string $documentId
     * @param string $clientHash
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function tosSignatureStatusAction($status, $documentId, $clientHash)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');
        /** @var \project_cgv $tos */
        $tos = $entityManager->getRepository('project_cgv');
        $tos->get($documentId);
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        $client->get($clientHash, 'hash');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        $project->get($tos->id_project);
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');
        $company->get($project->id_company);

        if ($company->id_client_owner != $client->id_client || $tos->status != \project_cgv::STATUS_NO_SIGN) {
            return $this->redirectToRoute('home');
        }

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
                $logger->notice('Tos unknown status', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $tos->id_project]);
                return $this->redirectToRoute('home');
        }

        $tosStatusLabel = $this->getTosStatusLabel($tos);

        if (null === $tosStatusLabel) {
            return $this->redirectToRoute('home');
        }

        $template       = [
            'pdf_link'    => $tos->getUrlPath(),
            'pdf_display' => in_array($tos->status, [\project_cgv::STATUS_SIGN_UNIVERSIGN, \project_cgv::STATUS_SIGN_FO]),
            'faq_url'     => $settings->value,
            'document'    => 'tos',
            'status'      => $tosStatusLabel
        ];

        $logger->notice('Tos status : ' . $tosStatusLabel, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $tos->id_project]);

        return $this->render('pages/universign.html.twig', $template);
    }

    /**
     * @Route("/universign/pouvoir/{proxyId}/{universignUpdate}", name="proxy_generation_no_update_universign", requirements={"proxyId":"\d+"})
     * @Route("/universign/pouvoir/{proxyId}", name="proxy_generation", requirements={"proxyId":"\d+"})
     * @param string $proxyId
     * @param null|string $universignUpdate
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createProxyAction($proxyId, $universignUpdate = '')
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \projects_pouvoir $proxy */
        $proxy = $entityManager->getRepository('projects_pouvoir');
        $proxy->get($proxyId);
        /** @var UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($proxy->status != \projects_pouvoir::STATUS_PENDING) {
            return $this->redirect($proxy->url_universign);
        }

        if ($universignUpdate == 'NoUpdateUniversign' && $proxy->url_universign != '') {
            $logger->notice('Proxy not signed but DB flag exists. Redirection to Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

            return $this->redirect($proxy->url_universign);
        }

        if (true === $universignManager->createProxy($proxy)) {
            $logger->notice('Proxy generation response from Universign OK. Redirection to Universign to sign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

            return $this->redirect($proxy->url_universign);
        }

        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        $template = [
            'pdf_link'    => $proxy->url_pdf,
            'pdf_display' => ($proxy->status == \projects_pouvoir::STATUS_SIGNED),
            'faq_url'     => $settings->value,
            'document'    => 'proxy',
            'status'      => $this->getProxyStatusLabel($proxy)
        ];

        $logger->error('Proxy generation response from Universign NOK (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);
        return $this->render('pages/universign.html.twig', $template);
    }

    /**
     * @Route("/universign/mandat/{mandateId}", name="mandate_generation", requirements={"mandateId":"\d+"})
     * @param string $mandateId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createMandateAction($mandateId)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \clients_mandats $mandate */
        $mandate = $entityManager->getRepository('clients_mandats');
        $mandate->get($mandateId);
        /** @var \Unilend\Bundle\FrontBundle\Service\UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($mandate->status == \clients_mandats::STATUS_PENDING) {
            if ($mandate->url_universign != '') {
                $logger->notice('Mandate not signed. Redirection to Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

                return $this->redirect($mandate->url_universign);
            } else {
                if (true === $universignManager->createMandate($mandate)) {
                    $logger->notice('Mandate response generation from universign OK. Redirection to Universign to sign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

                    return $this->redirect($mandate->url_universign);
                } else {
                    $logger->notice('Mandate response generation from universign NOK (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
                }
            }
        } else {
            $logger->notice('Mandate status (' . $this->getMandateStatusLabel($mandate) . ') - Creation of PDF to send to Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
        }

        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        $template       = [
            'pdf_link'    => $mandate->url_pdf,
            'pdf_display' => ($mandate->status == \clients_mandats::STATUS_SIGNED),
            'faq_url'     => $settings->value,
            'document'    => 'mandate',
            'status'      => $this->getMandateStatusLabel($mandate)
        ];

        return $this->render('pages/universign.html.twig', $template);
    }

    /**
     * @Route("/universign/cgv_emprunteurs/{tosId}/{tosName}", name="tos_generation", requirements={"tosId":"\d+"})
     * @param string $tosId
     * @param string $tosName
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createTosAction($tosId, $tosName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \project_cgv $tos */
        $tos = $entityManager->getRepository('project_cgv');
        $tos->get($tosId);
        /** @var \Unilend\Bundle\FrontBundle\Service\UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($tos->status == \project_cgv::STATUS_NO_SIGN && $tosName === $tos->name) {
            if (date('Y-m-d', strtotime($tos->updated)) === date('Y-m-d') && $tos->url_universign != '') {
                return $this->redirect($tos->url_universign);
            }

            if (true === $universignManager->createTos($tos)) {
                $logger->notice('Tos response generation from universign OK. Redirection to Universign to sign (project ' . $tos->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $tos->id_project]);

                return $this->redirect($tos->url_universign);
            } else {
                $logger->notice('Tos response generation from universign NOK (project ' . $tos->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $tos->id_project]);
            }
        }

        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        $template = [
            'pdf_link'    => $tos->getUrlPath(),
            'pdf_display' => ($tos->status == \clients_mandats::STATUS_SIGNED),
            'faq_url'     => $settings->value,
            'document'    => 'tos',
            'status'      => $this->getTosStatusLabel($tos)
        ];

        return $this->render('pages/universign.html.twig', $template);
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
                $logger->notice('Unknown proxy status (' . $proxy->status . ') - Cannot create PDF for Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);
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
                $logger->notice('Unknown mandate status (' . $mandate->status . ') - Cannot create PDF for Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
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
                $logger->notice('Unknown tos status (' . $tos->status . ') - Cannot create PDF for Universign (project ' . $tos->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $tos->id_project]);
                return null;
        }
    }
}
