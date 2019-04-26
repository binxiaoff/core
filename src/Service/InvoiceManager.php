<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Twig_Environment;
use Unilend\Entity\{CompteurFactures, EcheanciersEmprunteur, Factures, Projects, ProjectsStatus, ProjectsStatusHistory, TaxType};

class InvoiceManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var GeneratorInterface $snappy */
    private $snappy;
    /** @var Twig_Environment */
    private $twig;
    /** @var string */
    private $protectedPath;
    /** @var ProjectManager */
    private $projectManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param GeneratorInterface     $snappy
     * @param Twig_Environment       $twig
     * @param string                 $protectedPath
     * @param ProjectManager         $projectManager
     */
    public function __construct(EntityManagerInterface $entityManager, GeneratorInterface $snappy, Twig_Environment $twig, ProjectManager $projectManager, string $protectedPath)
    {
        $this->entityManager  = $entityManager;
        $this->snappy         = $snappy;
        $this->twig           = $twig;
        $this->protectedPath  = $protectedPath;
        $this->projectManager = $projectManager;
    }

    /**
     * @param Projects $project
     * @param int|null $order
     *
     * @return null|Factures
     * @throws \Exception
     */
    public function getBorrowerInvoice(Projects $project, $order = null)
    {
        $invoiceType = Factures::TYPE_COMMISSION_REPAYMENT;

        if (null === $order) {
            $invoiceType = Factures::TYPE_COMMISSION_FUNDS;
            $order       = '';
        }

        $invoice = $this->entityManager->getRepository(Factures::class)->findOneBy([
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
        $projectStatusHistoryRepository      = $this->entityManager->getRepository(ProjectsStatusHistory::class);
        $repaymentStatus                     = $this->entityManager->getRepository(ProjectsStatus::class)->findOneBy(['status' => ProjectsStatus::STATUS_CONTRACTS_SIGNED]);
        $projectsStatusHistoryFirstRepayment = $projectStatusHistoryRepository->findStatusFirstOccurrence($invoice->getIdProject(), $repaymentStatus);

        $this->generateInvoice($invoice, $projectsStatusHistoryFirstRepayment->getAdded());
    }

    /**
     * @param Factures $invoice
     */
    public function generateProjectRepaymentCommissionInvoice(Factures $invoice)
    {
        $this->generateInvoice($invoice);
    }

    /**
     * @param Factures       $invoice
     * @param \DateTime|null $paymentDate
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Twig_Error
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

        $filePath   = $this->getBorrowerInvoiceFilePath($invoice);
        $pdfContent = $this->twig->render('/pdf/borrower_invoice.html.twig', [
            'client'         => $invoice->getIdProject()->getIdCompany()->getIdClientOwner(),
            'companyAddress' => $invoice->getIdProject()->getIdCompany()->getIdAddress(),
            'project'        => $invoice->getIdProject(),
            'invoice'        => $invoice,
            'paymentDate'    => null === $paymentDate ? $invoice->getDate() : $paymentDate,
            'vat'            => $this->entityManager->getRepository(TaxType::class)->find(TaxType::TYPE_VAT),
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

        $invoice = new Factures();
        $invoice
            ->setIdProject($project)
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
    }

    /**
     * @param Projects $project
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createFundsInvoice(Projects $project)
    {
        $now               = new \DateTime();
        $commissionInclTax = $this->projectManager->getCommissionFunds($project, true);
        $commissionExclTax = $this->projectManager->getCommissionFunds($project, false);
        $vat               = round(bcsub($commissionInclTax, $commissionExclTax, 4), 2);

        $invoice = new Factures();
        $invoice
            ->setIdProject($project)
            ->setNumFacture($this->getInvoiceNumber($project, $now))
            ->setDate($now)
            ->setIdCompany($project->getIdCompany()->getIdCompany())
            ->setOrdre(0)
            ->setTypeCommission(Factures::TYPE_COMMISSION_FUNDS)
            ->setCommission($project->getCommissionRateFunds())
            ->setMontantTtc(bcmul($commissionInclTax, 100))
            ->setMontantHt(bcmul($commissionExclTax, 100))
            ->setTva(bcmul($vat, 100));

        $this->entityManager->persist($invoice);
        $this->entityManager->flush($invoice);
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
            ->getRepository(CompteurFactures::class)
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
