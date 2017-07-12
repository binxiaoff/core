<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\ClientsStatusHistoryRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\LendersImpositionHistoryRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\OperationRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletBalanceHistoryRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;

class QueriesCrsDacCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('queries:crd_dac')
            ->setDescription('Extract lender information at the end of a given year')
            ->addArgument(
                'year',
                InputArgument::REQUIRED,
                'year to export'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $year            = $input->getArgument('year');
        $lastDayOfTheYear = new \DateTime('Last day of december ' . $year);
        $filePath        = $this->getContainer()->getParameter('path.protected') . '/' . 'preteurs_crs_dac' . $year . '.xlsx';

        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var ClientsRepository $clientRepository */
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        /** @var WalletRepository $walletRepository */
        $walletRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        /** @var WalletBalanceHistoryRepository $walletBalanceHistoryRepository */
        $walletBalanceHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
        /** @var OperationRepository $operationRepository */
        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        /** @var ClientsStatusHistoryRepository $clientStatusHistoryRepository */
        $clientStatusHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory');
        /** @var LendersImpositionHistoryRepository $lenderImpositionRepository */
        $lenderImpositionRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:LendersImpositionHistory');
        $clientStatusManager        = $this->getContainer()->get('unilend.service.client_status_manager');

        /** @var \PHPExcel $document */
        $document    = new \PHPExcel();
        $activeSheet = $document->setActiveSheetIndex(0);
        $row         = 1;

        $activeSheet->setCellValue('A' . $row, 'id Client');
        $activeSheet->setCellValue('B' . $row, 'Commune de Naissance');
        $activeSheet->setCellValue('C' . $row, 'Date de la première validation');
        $activeSheet->setCellValue('D' . $row, 'Statut client');
        $activeSheet->setCellValue('E' . $row, 'Type');
        $activeSheet->setCellValue('F' . $row, 'Raison Sociale');
        $activeSheet->setCellValue('G' . $row, 'Nom');
        $activeSheet->setCellValue('H' . $row, 'Nom d\'usage');
        $activeSheet->setCellValue('I' . $row, 'Prenom');
        $activeSheet->setCellValue('J' . $row, 'Adresse fiscal');
        $activeSheet->setCellValue('K' . $row, 'Ville');
        $activeSheet->setCellValue('L' . $row, 'CP');
        $activeSheet->setCellValue('M' . $row, 'ISO pays fiscal');
        $activeSheet->setCellValue('N' . $row, 'Solde au 31/12/' . $year);
        $activeSheet->setCellValue('O' . $row, 'Montant investi au 31/12/' . $year);
        $activeSheet->setCellValue('P' . $row, 'CRD au 31/12/' . $year);
        $row ++;

        /** @var Clients $client */
        foreach ($clientRepository->findValidatedClientsUntilYear($year) as $client) {
            /** @var ClientsAdresses $clientAddress */
            $clientAddress           = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $client]);
            $wallet                  = $walletRepository->getWalletByType($client, WalletType::LENDER);
            $endOfYearBalanceHistory = $walletBalanceHistoryRepository->getBalanceOfTheDay($wallet, $lastDayOfTheYear);
            $endOfYearBalance        = null !== $endOfYearBalanceHistory ? bcadd($endOfYearBalanceHistory->getAvailableBalance(), $endOfYearBalanceHistory->getCommittedBalance(), 2) : 0;
            $remainingDuCapital      = $operationRepository->getRemainingDueCapitalAtDate($client->getIdClient(), $lastDayOfTheYear);
            $amountInvested          = $operationRepository->sumDebitOperationsByTypeUntil($wallet, [OperationType::LENDER_LOAN], null, $lastDayOfTheYear);
            $currentClientStatus     = $clientStatusManager->getLastClientStatus($client);
            $firstValidation         = $clientStatusHistoryRepository->getFirstClientValidation($client);
            $fiscalCountryIso        = $lenderImpositionRepository->getFiscalIsoAtDate($wallet, $lastDayOfTheYear);

            if (false === $client->isNaturalPerson()) {
                /** @var Companies $company */
                $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client->getIdClient()]);
            }

            $activeSheet->setCellValue('A' . $row, $client->getIdClient());
            $activeSheet->setCellValue('B' . $row, $client->getNaissance());
            $activeSheet->setCellValue('C' . $row, $firstValidation->getAdded()->format('Y-m-d'));
            $activeSheet->setCellValue('D' . $row, $currentClientStatus);
            $activeSheet->setCellValue('E' . $row, $client->isNaturalPerson() ? 'Physique' : 'Morale');
            $activeSheet->setCellValue('F' . $row, $client->isNaturalPerson() ? '' : $company->getName());
            $activeSheet->setCellValue('G' . $row, $client->getNom());
            $activeSheet->setCellValue('H' . $row, $client->getNomUsage());
            $activeSheet->setCellValue('I' . $row, $client->getPrenom());
            $activeSheet->setCellValue('J' . $row, $clientAddress->getAdresseFiscal());
            $activeSheet->setCellValue('K' . $row, $clientAddress->getVilleFiscal());
            $activeSheet->setCellValue('L' . $row, $clientAddress->getCpFiscal());
            $activeSheet->setCellValue('M' . $row, $fiscalCountryIso['iso']);
            $activeSheet->setCellValueExplicit('N' . $row, $endOfYearBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit('O' . $row, $amountInvested, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit('P' . $row, $remainingDuCapital, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $row += 1;
        }

        $activeSheet->getStyle('P' . 2 . ':' . 'R' . $row)
            ->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'Excel2007');
        $writer->save(str_replace(__FILE__, $filePath ,__FILE__));
    }
}
