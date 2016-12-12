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
     *     requirements={"status":"\w+"},
     *     requirements={"documentId":"\d+"},
     *     requirements={"clientHash":"[0-9a-f]{32}"}
     *     )
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
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
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
                    $logger->notice('Unknown proxy status (' . $proxy->status . ') - Cannot create PDF for Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);
                    return $this->redirectToRoute('home');
            }

            $proxyStatusLabel = $this->getProxyStatusLabel($proxy);

            if (null === $proxyStatusLabel) {
                return $this->redirectToRoute('home');
            } else {
                $settings->get('URL FAQ emprunteur', 'type');
                $borrowerFaqUrl = $settings->value;

                $template = [
                    'pdf_link'    => $proxy->url_pdf,
                    'pdf_display' => ($proxy->status == \projects_pouvoir::STATUS_SIGNED),
                    'faq_url'     => $borrowerFaqUrl,
                    'document'    => 'proxy',
                    'status'      => $proxyStatusLabel
                ];

                $logger->notice('Proxy status : ' . $proxyStatusLabel, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

                return $this->render('pages/universign.html.twig', $template);
            }
        } else {
            return $this->redirectToRoute('home');
        }
    }

    /**
     * @Route(
     *     "/universign/{status}/mandat/{documentId}/{clientHash}",
     *     name="mandate_signature_status",
     *     requirements={"status":"\w+"},
     *     requirements={"documentId":"\d+"},
     *     requirements={"clientHash":"[0-9a-f]{32}"}
     *     )
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
                    $logger->notice('Unknown mandate status (' . $mandate->status . ') - Cannot create PDF for Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
                    return $this->redirectToRoute('home');
            }

            $mandateStatusLabel = $this->getMandateStatusLabel($mandate);

            if (null === $mandateStatusLabel) {
                return $this->redirectToRoute('home');
            } else {
                $settings->get('URL FAQ emprunteur', 'type');
                $borrowerFaqUrl = $settings->value;

                $template = [
                    'pdf_link'    => $mandate->url_pdf,
                    'pdf_display' => ($mandate->status == \clients_mandats::STATUS_SIGNED),
                    'faq_url'     => $borrowerFaqUrl,
                    'document'    => 'mandate',
                    'status'      => $mandateStatusLabel
                ];

                $logger->notice('Mandate status : ' . $mandateStatusLabel, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

                return $this->render('pages/universign.html.twig', $template);
            }
        } else {
            return $this->redirectToRoute('home');
        }
    }

    /**
     * @Route(
     *     "/universign/{status}/cgv_emprunteurs/{documentId}/{clientHash}",
     *     name="tos_signature_status",
     *     requirements={"status":"\w+"},
     *     requirements={"documentId":"\d+"},
     *     requirements={"clientHash":"[0-9a-f]{32}"}
     *     )
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
                    $logger->notice('Tos unknown status', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $tos->id_project]);
                    return $this->redirectToRoute('home');
            }

            $tosStatusLabel = $this->getTosStatusLabel($tos);

            if (null === $tosStatusLabel) {
                return $this->redirectToRoute('home');
            } else {
                $settings->get('URL FAQ emprunteur', 'type');
                $borrowerFaqUrl = $settings->value;

                $template = [
                    'pdf_link'    => $tos->getUrlPath(),
                    'pdf_display' => in_array($tos->status, [\project_cgv::STATUS_SIGN_UNIVERSIGN, \project_cgv::STATUS_SIGN_FO]),
                    'faq_url'     => $borrowerFaqUrl,
                    'document'    => 'tos',
                    'status'      => $tosStatusLabel
                ];

                $logger->notice('Tos status : ' . $tosStatusLabel, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $tos->id_project]);

                return $this->render('pages/universign.html.twig', $template);
            }
        } else {
            return $this->redirectToRoute('home');
        }
    }

    /**
     * @Route(
     *     "/universign/pouvoir/{proxyId}/{universignUpdate}",
     *     name="proxy_generation_no_update_universign",
     *     requirements={"proxyId":"\d+"},
     *     requirements={"universignUpdate":"\w+"}
     *     )
     * @Route("/universign/pouvoir/{proxyId}", name="proxy_generation", requirements={"proxyId":"\d+"})
     * @param int $proxyId
     * @param null|string $universignUpdate
     * @return Response
     */
    public function createProxyAction($proxyId, $universignUpdate = null)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \projects_pouvoir $proxy */
        $proxy = $entityManager->getRepository('projects_pouvoir');
        /** @var UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($proxy->get($proxyId) && $proxy->status == \projects_pouvoir::STATUS_PENDING) {

            if ($universignUpdate == 'NoUpdateUniversign' && $proxy->url_universign != '') {
                $logger->notice('Proxy not signed but DB flag exists. Redirection to Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

                return $this->redirect($proxy->url_universign);
            }

            if (true === $universignManager->createProxy($proxy)) {
                $logger->notice('Proxy generation response from Universign OK. Redirection to Universign to sign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

                return $this->redirect($proxy->url_universign);
            }

            $proxyStatusLabel = $this->getProxyStatusLabel($proxy);

            if (null === $proxyStatusLabel) {

                return $this->redirectToRoute('home');
            } else {
                /** @var \settings $settings */
                $settings = $entityManager->getRepository('settings');
                $settings->get('URL FAQ emprunteur', 'type');
                $borrowerFaqUrl = $settings->value;

                $template = [
                    'pdf_link'    => $proxy->url_pdf,
                    'pdf_display' => ($proxy->status == \projects_pouvoir::STATUS_SIGNED),
                    'faq_url'     => $borrowerFaqUrl,
                    'document'    => 'proxy',
                    'status'      => $proxyStatusLabel
                ];

                $logger->error('Proxy generation response from Universign NOK (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

                return $this->render('pages/universign.html.twig', $template);
            }
        } else {
            return $this->redirect($proxy->url_universign);
        }
    }

    /**
     * @Route("/universign/mandat/{mandateId}", name="mandate_generation", requirements={"mandateId":"\d+"})
     * @param int $mandateId
     * @return Response
     */
    public function createMandateAction($mandateId)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \clients_mandats $mandate */
        $mandate = $entityManager->getRepository('clients_mandats');
        /** @var \Unilend\Bundle\FrontBundle\Service\UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($mandate->get($mandateId)) {
            $mandateStatusLabel = $this->getMandateStatusLabel($mandate);
            if (null === $mandateStatusLabel) {
                $logger->notice('Unknown mandate status - Creation of PDF to send to Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

                return $this->redirectToRoute('home');
            } elseif ($mandate->status == \clients_mandats::STATUS_PENDING) {
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
                $logger->notice('Mandate status (' . $mandateStatusLabel . ') - Creation of PDF to send to Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
            }

            /** @var \settings $settings */
            $settings = $entityManager->getRepository('settings');
            $settings->get('URL FAQ emprunteur', 'type');
            $borrowerFaqUrl = $settings->value;

            $template = [
                'pdf_link'    => $mandate->url_pdf,
                'pdf_display' => ($mandate->status == \clients_mandats::STATUS_SIGNED),
                'faq_url'     => $borrowerFaqUrl,
                'document'    => 'mandate',
                'status'      => $mandateStatusLabel
            ];

            return $this->render('pages/universign.html.twig', $template);
        } else {
            return $this->redirectToRoute('home');
        }
    }

    /**
     * @Route("/universign/cgv_emprunteurs/{tosId}/{tosName}", name="tos_generation", requirements={"tosId":"\d+"})
     * @param int $tosId
     * @param string $tosName
     * @return Response
     */
    public function createTosAction($tosId, $tosName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var \project_cgv $tos */
        $tos = $entityManager->getRepository('project_cgv');
        /** @var \Unilend\Bundle\FrontBundle\Service\UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($tos->get($tosId)) {
            $tosStatusLabel = $this->getTosStatusLabel($tos);

            if (null === $tosStatusLabel) {
                return $this->redirectToRoute('home');
            } else {
                if ($tos->status == \project_cgv::STATUS_NO_SIGN && $tosName === $tos->name) {

                    $tosLastUpdateDate = \DateTime::createFromFormat('Y-m-d H:i:s', $tos->updated);
                    if ($tosLastUpdateDate->format('Y-m-d') === date('Y-m-d') && $tos->url_universign != '') {
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
                $borrowerFaqUrl = $settings->value;

                $template = [
                    'pdf_link'    => $tos->getUrlPath(),
                    'pdf_display' => ($tos->status == \clients_mandats::STATUS_SIGNED),
                    'faq_url'     => $borrowerFaqUrl,
                    'document'    => 'tos',
                    'status'      => $tosStatusLabel
                ];

                return $this->render('pages/universign.html.twig', $template);
            }
        } else {
            return $this->redirectToRoute('home');
        }
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
