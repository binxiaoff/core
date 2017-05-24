<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class InvoiceController extends Controller
{
    /**
     * @Route("/pdf/facture_EF/{clientHash}/{idProject}", name="invoice_funds_commission", requirements={"idProject":"\d+", "clientHash": "[0-9a-f-]{32,36}"})
     *
     * @param string  $clientHash
     * @param int     $idProject
     *
     * @return Response
     * @throws \Exception
     */
    public function downloadProjectFundsCommissionAction($clientHash, $idProject)
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var Projects $project */
        $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($idProject);
        /** @var Clients $client */
        $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneByHash($clientHash);

        if (null === $project) {
            throw new \Exception('The project ' . $idProject . 'does not exist');
        }

        if ($project->getIdCompany()->getIdClientOwner() !== $client->getIdClient()) {
            throw new \Exception('Project owner and client do not match.');
        }

        $namePdfClient = 'FACTURE-UNILEND-' . $project->getSlug();
        $filePath      = $this->getParameter('path.protected') . '/pdf/facture/facture_EF-' . $clientHash . '-' . $idProject . '.pdf';

        if (false === file_exists($filePath)) {
            $this->get('unilend.service.invoice_manager')->generateProjectFundsCommissionInvoice($project);
        }

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachement; filename="' . $namePdfClient . '.pdf"'
        ]);
    }


    /**
     * @Route("/pdf/facture_ER/{clientHash}/{idProject}/{order}", name="invoice_payment_commission", requirements={"idProject":"\d+", "clientHash": "[0-9a-f-]{32,36}", "order":"\d+"})
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
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var Projects $project */
        $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($idProject);
        /** @var Clients $client */
        $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneByHash($clientHash);

        if (null === $project) {
            throw new  \Exception('The project ' . $idProject . 'does not exist');
        }

        if ($project->getIdCompany()->getIdClientOwner() !== $client->getIdClient()) {
            throw new \Exception('Project owner and client do not match.');
        }

        $namePdfClient = 'FACTURE-UNILEND-' . $project->getSlug() . '-' . $order;
        $filePath      = $this->getParameter('path.protected') . '/pdf/facture/facture_ER-' . $clientHash . '-' . $idProject . '-' . $order . '.pdf';

        if (false === file_exists($filePath)) {
           $this->get('unilend.service.invoice_manager')->generateProjectRepaymentCommissionInvoice($project, $order);
        }

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachement; filename="' . $namePdfClient . '.pdf"'
        ]);
    }
}
