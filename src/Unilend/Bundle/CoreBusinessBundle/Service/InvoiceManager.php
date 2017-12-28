<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Knp\Snappy\GeneratorInterface;
use Twig_Environment;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompteurFactures;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
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
     * @param Projects $project
     * @param int|null $order
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
            throw new \Exception('The requested invoice does not exist in database. Project : ' . $project->getIdProject() . ' order : ' . $order);
        }

        return $invoice;
    }

    /**
     * @param Factures $invoice
     */
    public function generateProjectFundsCommissionInvoice(Factures $invoice)
    {
        $projectStatusHistoryRepository      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
        $repaymentStatus                     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => ProjectsStatus::REMBOURSEMENT]);
        $projectsStatusHistoryFirstRepayment = $projectStatusHistoryRepository->findStatusFirstOccurrence($invoice->getIdProject(), $repaymentStatus);

        $this->generateInvoice($invoice, $projectsStatusHistoryFirstRepayment->getAdded());
    }

    /**
     * @param Factures $invoice*
     */
    public function generateProjectRepaymentCommissionInvoice(Factures $invoice)
    {
        $this->generateInvoice($invoice);
    }

    /**
     * @param Factures       $invoice
     * @param \DateTime|null $paymentDate
     */
    private function generateInvoice(Factures $invoice, \DateTime $paymentDate = null)
    {
        $options = [
            'footer-html'   => '',
            'header-html'   => '',
            'margin-top'    => 20,
            'margin-right'  => 15,
            'margin-bottom' => 10,
            'margin-left'   => 15
        ];

        $filePath = $this->getBorrowerInvoiceFilePath($invoice);
        $pdfContent = $this->twig->render('/pdf/borrower_invoice.html.twig', [
            'client'      => $invoice->getIdProject()->getIdCompany()->getIdClientOwner(),
            'project'     => $invoice->getIdProject(),
            'invoice'     => $invoice,
            'paymentDate' => null === $paymentDate ? $invoice->getDate() : $paymentDate,
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
        $client = $invoice->getIdProject()->getIdCompany()->getIdClientOwner();

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

    /**
     * @param EcheanciersEmprunteur $paymentSchedule
     *
     * @throws \Exception
     */
    public function createPaymentScheduleInvoice(EcheanciersEmprunteur $paymentSchedule)
    {
        $project = $paymentSchedule->getIdProject();
        $now     = new \DateTime();

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $invoice = new Factures();
            $invoice->setIdProject($project)
                ->setNumFacture($this->getInvoiceNumber($project, $now))
                ->setDate($now)
                ->setOrdre($paymentSchedule->getOrdre())
                ->setIdCompany($project->getIdCompany()->getIdCompany())
                ->setTypeCommission(Factures::TYPE_COMMISSION_REPAYMENT)
                ->setCommission($project->getCommissionRateRepayment())
                ->setMontantHt($paymentSchedule->getCommission())
                ->setTva($paymentSchedule->getTva())
                ->setMontantTtc(bcadd($paymentSchedule->getCommission(), $paymentSchedule->getTva(), 2));

            $this->entityManager->persist($invoice);
            $this->entityManager->flush($invoice);

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Projects  $project
     * @param \DateTime $date
     *
     * @return int
     */
    private function getInvoiceNumber(Projects $project, \DateTime $date)
    {
        $invoiceCount = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:CompteurFactures')
            ->findOneBy(['date' => $date], ['ordre' => 'DESC']);

        $dailyCount = 0;
        if ($invoiceCount) {
            $dailyCount = $invoiceCount->getOrdre();
        }

        $newInvoiceCount = new CompteurFactures();
        $newInvoiceCount->setIdProject($project)
            ->setOrdre(++$dailyCount)
            ->setDate($date);

        $this->entityManager->persist($newInvoiceCount);
        $this->entityManager->flush($newInvoiceCount);

        return vsprintf('FR-E%s%s', ['date' => $date->format('Ymd'), 'sequence' => str_pad($newInvoiceCount->getOrdre(), 5, '0', STR_PAD_LEFT)]);
    }
}
