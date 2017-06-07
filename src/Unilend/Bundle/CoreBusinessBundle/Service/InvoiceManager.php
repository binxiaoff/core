<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Knp\Snappy\GeneratorInterface;
use Twig_Environment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;

class InvoiceManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var GeneratorInterface $snappy */
    private $snappy;
    /** @var Twig_Environment */
    private $twig;
    /** @var string */
    private $protectedPath;

    /**
     * @param EntityManager      $entityManager
     * @param GeneratorInterface $snappy
     * @param Twig_Environment   $twig
     * @param string             $protectedPath
     */
    public function __construct(EntityManager $entityManager, GeneratorInterface $snappy, Twig_Environment $twig, $protectedPath)
    {
        $this->entityManager = $entityManager;
        $this->snappy        = $snappy;
        $this->twig          = $twig;
        $this->protectedPath = $protectedPath;
    }

    /**
     * @param Projects     $project
     * @param int|null     $order
     *
     * @return null||Factures
     * @throws \Exception
     */
    public function getBorrowerInvoice(Projects $project, $order = null)
    {
        $invoiceType = Factures::TYPE_COMMISSION_REPAYMENT;

        if (null === $order) {
            $invoiceType = Factures::TYPE_COMMISSION_FUNDS;
            $order       = '';
        }

        $invoice = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Factures')->findOneBy([
            'typeCommission' => $invoiceType,
            'idCompany'      => $project->getIdCompany()->getIdCompany(),
            'idProject'      => $project->getIdProject(),
            'ordre'          => $order
        ]);

        if (null === $invoice) {
            throw new \Exception('The requested invoice does not exist in database');
        }

        return $invoice;
    }

    /**
     * @param Factures $invoice
     *
     * @throws \Exception
     */
    public function generateProjectFundsCommissionInvoice(Factures $invoice)
    {
        $projectStatusHistoryRepository      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
        $repaymentStatus                     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => ProjectsStatus::REMBOURSEMENT]);
        $projectsStatusHistoryFirstRepayment = $projectStatusHistoryRepository->findStatusFirstOccurrence($invoice->getIdProject(), $repaymentStatus);

        $this->generateInvoice($invoice, $projectsStatusHistoryFirstRepayment->getAdded());
    }

    /**
     * @param Factures $invoice
     *
     * @throws \Exception
     */
    public function generateProjectRepaymentCommissionInvoice(Factures $invoice)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy([
            'idProject' => $invoice->getIdProject()->getIdProject(),
            'ordre'     => $invoice->getOrdre(),
            'statusRa'  => \echeanciers_emprunteur::STATUS_NO_EARLY_REFUND
        ]);

        $this->generateInvoice($invoice, $paymentSchedule->getDateEcheanceEmprunteurReel());
    }

    /**
     * @param Factures  $invoice
     * @param \DateTime $paymentDate
     */
    private function generateInvoice(Factures $invoice, \DateTime $paymentDate)
    {
        $options = [
            'footer-html'   => '',
            'header-html'   => '',
            'margin-top'    => 20,
            'margin-right'  => 15,
            'margin-bottom' => 10,
            'margin-left'   => 15
        ];

        $client   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($invoice->getIdProject()->getIdCompany()->getIdClientOwner());
        $filePath = $this->getBorrowerInvoiceFilePath($invoice);

        $pdfContent = $this->twig->render('/pdf/borrower_invoice.html.twig', [
            'client'      => $client,
            'project'     => $invoice->getIdProject(),
            'invoice'     => $invoice,
            'paymentDate' => $paymentDate,
            'vat'         => $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT),
        ]);

        $this->snappy->generateFromHtml($pdfContent, $filePath, $options, true);
    }

    /**
     * @param Factures $invoice
     *
     * @return string
     */
    public function getBorrowerInvoiceFilePath(Factures $invoice)
    {
        $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($invoice->getIdProject()->getIdCompany()->getIdClientOwner());

        if ($invoice->getOrdre() >= 1) {
            return $this->protectedPath . '/pdf/facture/facture_ER-' . $client->getHash() . '-' . $invoice->getIdProject()->getIdProject() . '-' . $invoice->getOrdre() . '.pdf';
        } else {
            return $this->protectedPath . '/pdf/facture/facture_EF-' . $client->getHash() . '-' . $invoice->getIdProject()->getIdProject() . '.pdf';
        }
    }

    /**
     * @param Factures $invoice
     *
     * @return string
     */
    public function getBorrowerInvoiceFileName(Factures $invoice)
    {
        if ($invoice->getOrdre() >= 1) {
            return 'FACTURE-UNILEND-' . $invoice->getIdProject()->getSlug() . '-' . $invoice->getOrdre();
        } else {
            return 'FACTURE-UNILEND-' . $invoice->getIdProject()->getSlug();
        }
    }
}
