<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    /**
     * @Route("/pdf/facture_EF/{clientHash}/{idProject}", name="borrower_invoice_funds_commission", requirements={"idProject": "\d+", "clientHash": "[0-9a-f-]{32,36}"})
     *
     * @param string  $clientHash
     * @param int     $idProject
     *
     * @return Response
     */
    public function downloadProjectFundsCommissionAction($clientHash, $idProject)
    {
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $invoiceManager = $this->get('unilend.service.invoice_manager');
        $translator     = $this->get('translator');
        $project        = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($idProject);
        $client         = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);

        if (null === $project) {
            return $this->render(
                ':frontbundle/exception:error.html.twig',
                [
                    'errorTitle'   => $translator->trans('borrower-invoice-download_project-not-found-error-title'),
                    'errorDetails' => $translator->trans('borrower-invoice-download_error-details-contact-link', ['contactUrl' => $this->generateUrl('borrower_account_contact')])
                ]
            )->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        if ($project->getIdCompany()->getIdClientOwner() != $client) {
            return $this->render(
                ':frontbundle/exception:error.html.twig',
                [
                    'errorTitle'   => $translator->trans('borrower-invoice-download_client-not-found-error-title'),
                    'errorDetails' => $translator->trans('borrower-invoice-download_error-details-contact-link', ['contactUrl' => $this->generateUrl('borrower_account_contact')])
                ]
            )->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        try {
            $invoice       = $invoiceManager->getBorrowerInvoice($project);
        } catch (\Exception $exception) {
            return $this->render(
                ':frontbundle/exception:error.html.twig',
                [
                    'errorTitle'   => $translator->trans('borrower-invoice-download_invoice-not-found-error-title'),
                    'errorDetails' => $translator->trans('borrower-invoice-download_error-details-contact-link', ['%contactUrl%' => $this->generateUrl('borrower_account_contact')])
                ]
            )->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $namePdfClient = $invoiceManager->getBorrowerInvoiceFileName($invoice);
        $filePath      = $invoiceManager->getBorrowerInvoiceFilePath($invoice);

        if (false === file_exists($filePath)) {
            $invoiceManager->generateProjectFundsCommissionInvoice($invoice);
        }

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachement; filename="' . $namePdfClient . '.pdf"'
        ]);
    }

    /**
     * @Route("/pdf/facture_ER/{clientHash}/{idProject}/{order}", name="borrower_invoice_payment_commission", requirements={"idProject": "\d+", "clientHash": "[0-9a-f-]{32,36}", "order":"\d+"})
     *
     * @param string  $clientHash
     * @param int     $idProject
     * @param int     $order
     *
     * @return Response
     */
    public function downloadRepaymentCommissionInvoiceAction($clientHash, $idProject, $order)
    {
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $invoiceManager = $this->get('unilend.service.invoice_manager');
        $translator     = $this->get('translator');
        $project        = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($idProject);
        $client         = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);

        if (null === $project) {
            return $this->render(
                ':frontbundle/exception:error.html.twig',
                [
                    'errorTitle'   => $translator->trans('borrower-invoice-download_project-not-found-error-title'),
                    'errorDetails' => $translator->trans('borrower-invoice-download_error-details-contact-link', ['contactUrl' => $this->generateUrl('borrower_account_contact')])
                ]
            )->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        if ($project->getIdCompany()->getIdClientOwner() != $client) {
            return $this->render(
                ':frontbundle/exception:error.html.twig',
                [
                    'errorTitle'   => $translator->trans('borrower-invoice-download_client-not-found-error-title'),
                    'errorDetails' => $translator->trans('borrower-invoice-download_error-details-contact-link', ['contactUrl' => $this->generateUrl('borrower_account_contact')])
                ]
            )->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        try {
            $invoice = $invoiceManager->getBorrowerInvoice($project, $order);
        } catch (\Exception $exception) {
            return $this->render(
                ':frontbundle/exception:error.html.twig',
                [
                    'errorTitle'   => $translator->trans('borrower-invoice-download_invoice-not-found-error-title'),
                    'errorDetails' => $translator->trans('borrower-invoice-download_error-details-contact-link', ['%contactUrl%' => $this->generateUrl('borrower_account_contact')])
                ]
            )->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $namePdfClient = $invoiceManager->getBorrowerInvoiceFileName($invoice);
        $filePath      = $invoiceManager->getBorrowerInvoiceFilePath($invoice);

        if (false === file_exists($filePath)) {
            $invoiceManager->generateProjectRepaymentCommissionInvoice($invoice);
        }

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachement; filename="' . $namePdfClient . '.pdf"'
        ]);
    }
}
