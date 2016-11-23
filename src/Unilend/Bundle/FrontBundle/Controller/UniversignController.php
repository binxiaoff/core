<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Sonata\SeoBundle\Seo\SeoPage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\UniversignManager;
use Unilend\core\Loader;

class UniversignController extends Controller
{

    /**
     * @Route("/universign/{status}/pouvoir/{documentId}", name="proxy_signature_status", requirements={"documentId":"\d+"})
     * @param $status
     * @param $documentId
     * @return Response
     */
    public function proxySignatureStatusAction($status, $documentId)
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

        if ($status == 'success' && $proxy->status == \projects_pouvoir::STATUS_PENDING) {
            $universignManager->signProxy($proxy);
        }

        $proxyStatusLabel = $this->getProxyStatusLabel($proxy);
        $template         = [
            'lien_pdf' => $proxy->url_pdf,
            'faq_url'  => $settings->value,
            'document' => 'proxy',
            'status'   => $proxyStatusLabel
        ];

        $logger->notice('Proxy status : ' . $proxyStatusLabel, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

        return $this->render('pages/universign.html.twig', $template);
    }

    /**
     * @Route("/universign/{status}/mandat/{documentId}", name="mandate_signature_status", requirements={"documentId":"\d+"})
     * @param $status
     * @param $documentId
     * @return Response
     */
    public function mandateSignatureStatusAction($status, $documentId)
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
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        if ($status == 'success' && $mandate->status == \clients_mandats::STATUS_PENDING) {
            $universignManager->signMandate($mandate);
        }

        $mandateStatusLabel = $this->getMandateStatusLabel($mandate);
        $template           = [
            'lien_pdf' => $mandate->url_pdf,
            'faq_url'  => $settings->value,
            'document' => 'mandate',
            'status'   => $mandateStatusLabel
        ];

        $logger->notice('Mandate status : ' . $mandateStatusLabel, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);

        return $this->render('pages/universign.html.twig', $template);
    }


    /**
     * @Route("/universign/pouvoir/{proxyId}/{noUpdateUniversign}", name="proxy_generation_no_update_universign", requirements={"proxyId":"\d+"})
     * @Route("/universign/pouvoir/{proxyId}", name="proxy_generation", requirements={"proxyId":"\d+"})
     * @param $proxyId
     * @return Response
     */
    public function createProxyAction($proxyId) // TODO : NoUpdateUniversign
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

        if ($proxy->status == \projects_pouvoir::STATUS_SIGNED) {
            return $this->redirect($proxy->url_universign);
        }

        if ($proxyId == 'NoUpdateUniversign' && $proxy->url_universign != '' && $proxy->status == \projects_pouvoir::STATUS_PENDING) {
            $logger->notice('Proxy not signed but DB flag exists. Redirection to Universign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

            return $this->redirectToRoute($proxy->url_universign);
        }

        if (true === $universignManager->createProxy($proxy)) {
            $logger->notice('Proxy generation response from Universign OK. Redirection to Universign to sign (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

            return $this->redirect($proxy->url_universign);
        } else {
            $logger->error('Proxy generation response from Universign NOK (project ' . $proxy->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project]);

            return $this->render('pages/universign.html.twig', ['document' => 'pouvoir', 'status' => $this->getProxyStatusLabel($proxy), 'lien_pdf' => $proxy->url_pdf]);
        }
    }

    /**
     * @Route("/universign/mandat/{mandateId}", name="mandate_generation", requirements={"mandateId":"\d+"})
     * @param $mandateId
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
        $mandate->get($mandateId);
        /** @var \Unilend\Bundle\FrontBundle\Service\UniversignManager $universignManager */
        $universignManager = $this->get('unilend.frontbundle.service.universign_manager');

        if ($mandate->status != \clients_mandats::STATUS_SIGNED) {
            if ($mandate->url_universign != '' && $mandate->status == \clients_mandats::STATUS_PENDING) {
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

        return $this->render('pages/universign.html.twig', ['document' => 'mandat', 'status' => $this->getMandateStatusLabel($mandate), 'lien_pdf' => $mandate->url_pdf]);
    }

    /**
     * @param \projects_pouvoir $proxy
     * @return null|string
     */
    private function getProxyStatusLabel(\projects_pouvoir $proxy)
    {
        switch ($proxy->status) {
            case \projects_pouvoir::STATUS_PENDING:
                return ('pending');
            case \projects_pouvoir::STATUS_SIGNED:
                return ('signed');
            case \projects_pouvoir::STATUS_CANCELLED:
                return ('cancel');
            case \projects_pouvoir::STATUS_FAILED:
                return ('fail');
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
                return ('signed');
            case \clients_mandats::STATUS_PENDING:
                return ('pending');
            case \clients_mandats::STATUS_CANCELED:
                return ('cancel');
            case \clients_mandats::STATUS_FAILED:
                return ('fail');
            default:
                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->notice('Unknown mandate status (' . $mandate->status . ') - Cannot create PDF for Universign (project ' . $mandate->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project]);
                return null;
        }
    }
}
