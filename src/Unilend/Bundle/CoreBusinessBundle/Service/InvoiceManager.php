<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Knp\Snappy\GeneratorInterface;
use Twig_Environment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
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
     *
     * @throws \Exception
     */
    public function generateProjectFundsCommissionInvoice(Projects $project)
    {
        $invoice = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Factures')->findOneBy([
            'typeCommission' => Factures::TYPE_COMMISSION_FUNDS,
            'idCompany'      => $project->getIdCompany()->getIdCompany(),
            'idProject'      => $project->getIdProject()
        ]);

        if (null === $invoice) {
            throw new \Exception('The requested invoice does not exist in database');
        }

        $projectStatusHistoryRepository      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
        $repaymentStatus                     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => \projects_status::REMBOURSEMENT]);
        $projectsStatusHistoryFirstRepayment = $projectStatusHistoryRepository->findStatusFirstOccurrence($project, $repaymentStatus);

        $this->generateInvoice($invoice, $projectsStatusHistoryFirstRepayment->getAdded());
    }

    /**
     * @param Projects $project
     * @param int      $order
     *
     * @throws \Exception
     */
    public function generateProjectRepaymentCommissionInvoice(Projects $project, $order)
    {
        $invoice = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Factures')->findOneBy([
            'typeCommission' => Factures::TYPE_COMMISSION_REPAYMENT,
            'idCompany'      => $project->getIdCompany()->getIdCompany(),
            'idProject'      => $project,
            'ordre'          => $order
        ]);

        if (null === $invoice) {
            throw new \Exception('The requested invoice does not exist in database');
        }

        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy([
            'idProject' => $project->getIdProject(),
            'ordre'     => $order,
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

        $project = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($invoice->getIdProject());
        $client  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());

        if ($invoice->getOrdre() >= 1) {
            $filePath = $this->protectedPath . '/pdf/facture/facture_ER-' . $client->getHash() . '-' . $project->getIdProject() . '-' . $invoice->getOrdre() . '.pdf';
        } else {
            $filePath = $this->protectedPath . '/pdf/facture/facture_EF-' . $client->getHash() . '-' . $project->getIdProject() . '.pdf';
        }

        $pdfContent = $this->twig->render('/pdf/commission_invoice.html.twig', [
            'client'      => $client,
            'project'     => $project,
            'invoice'     => $invoice,
            'paymentDate' => $paymentDate,
            'vat'         => $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT),
            'footer'      => $this->getInvoiceFooterData()
        ]);

        $this->snappy->generateFromHtml($pdfContent, $filePath, $options, true);
    }

    /**
     * @return array
     */
    private function getInvoiceFooterData()
    {
        $settingsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');

        $footerInvoiceData = [
            'unilendTitle'      => mb_strtoupper($settingsRepository->findOneBy(['type' => 'titulaire du compte'])->getValue(), 'UTF-8'),
            'corporateName'     => mb_strtoupper($settingsRepository->findOneBy(['type' => 'Declaration contrat pret - raison sociale'])->getValue(), 'UTF-8'),
            'unilend'           => mb_strtoupper($settingsRepository->findOneBy(['type' => 'Facture - Unilend'])->getValue(), 'UTF-8'),
            'capital'           => mb_strtoupper($settingsRepository->findOneBy(['type' => 'Facture - capital'])->getValue(), 'UTF-8'),
            'address'           => mb_strtoupper($settingsRepository->findOneBy(['type' => 'Declaration contrat pret - adresse'])->getValue(), 'UTF-8'),
            'phone'             => mb_strtoupper($settingsRepository->findOneBy(['type' => 'Facture - telephone'])->getValue(), 'UTF-8'),
            'rcs'               => mb_strtoupper($settingsRepository->findOneBy(['type' => 'Facture - RCS'])->getValue(), 'UTF-8'),
            'intraCommunityVAT' => mb_strtoupper($settingsRepository->findOneBy(['type' => 'Facture - TVA INTRACOMMUNAUTAIRE'])->getValue(), 'UTF-8')
        ];

        return $footerInvoiceData;
    }
}
