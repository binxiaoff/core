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
     * @throws \Exception
     */
    public function downloadProjectFundsCommissionAction($clientHash, $idProject)
    {
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $invoiceManager = $this->get('unilend.service.invoice_manager');
        $project        = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($idProject);
        $client         = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);

        if (null === $project) {
            throw new \Exception('The project ' . $idProject . ' does not exist');
        }

        if ($project->getIdCompany()->getIdClientOwner() != $client->getIdClient()) {
            throw new \Exception('Project owner and client do not match.');
        }

        $invoice       = $invoiceManager->getBorrowerInvoice($project);
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
     * @throws \Exception
     */
    public function downloadRepaymentCommissionInvoiceAction($clientHash, $idProject, $order)
    {
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $invoiceManager = $this->get('unilend.service.invoice_manager');
        $project        = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($idProject);
        $client         = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $clientHash]);

        if (null === $project) {
            throw new  \Exception('The project ' . $idProject . ' does not exist');
        }

        if ($project->getIdCompany()->getIdClientOwner() != $client->getIdClient()) {
            throw new \Exception('Project owner and client do not match.');
        }

        $invoice       = $invoiceManager->getBorrowerInvoice($project, $order);
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
