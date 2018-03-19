<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{
    InputArgument, InputInterface
};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, Companies, OperationType, WalletType
};

class QueriesCrsDacCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('queries:crs_dac')
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
        $year = $input->getArgument('year');

        if (preg_match("/^[0-9]{4}$/", $year)) {
            $lastDayOfTheYear = new \DateTime('Last day of december ' . $year);
            if (null === $lastDayOfTheYear) {
                $output->writeln('<error>Wrong date format ("Y" expected)</error>');
                return;
            }
        } else {
            $output->writeln('<error>Wrong date format ("Y" expected)</error>');
            return;
        }

        $filePath                       = $this->getContainer()->getParameter('path.protected') . '/queries/' . 'preteurs_crs_dac' . $year . '.xlsx';
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $clientRepository               = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $walletRepository               = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletBalanceHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
        $operationRepository            = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $clientStatusHistoryRepository  = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory');
        $lenderImpositionRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:LendersImpositionHistory');

        /** @var \PHPExcel $document */
        $document    = new \PHPExcel();
        $activeSheet = $document->setActiveSheetIndex(0);
        $row         = 1;

        $activeSheet->setCellValue('A' . $row, 'id Client');
        $activeSheet->setCellValue('B' . $row, 'Date de Naissance');
        $activeSheet->setCellValue('C' . $row, 'Commune de Naissance');
        $activeSheet->setCellValue('D' . $row, 'ISO Nationalité');
        $activeSheet->setCellValue('E' . $row, 'Date de la première validation');
        $activeSheet->setCellValue('F' . $row, 'Statut client');
        $activeSheet->setCellValue('G' . $row, 'Type');
        $activeSheet->setCellValue('H' . $row, 'Raison Sociale');
        $activeSheet->setCellValue('I' . $row, 'Nom');
        $activeSheet->setCellValue('J' . $row, 'Nom d\'usage');
        $activeSheet->setCellValue('K' . $row, 'Prenom');
        $activeSheet->setCellValue('L' . $row, 'Adresse fiscal');
        $activeSheet->setCellValue('M' . $row, 'Ville');
        $activeSheet->setCellValue('N' . $row, 'CP');
        $activeSheet->setCellValue('O' . $row, 'ISO pays fiscal');
        $activeSheet->setCellValue('P' . $row, 'Solde au 31/12/' . $year);
        $activeSheet->setCellValue('Q' . $row, 'Montant investi au 31/12/' . $year);
        $activeSheet->setCellValue('R' . $row, 'CRD au 31/12/' . $year);
        $activeSheet->setCellValue('S' . $row, 'Intérêts bruts versés jusqu\'au 31/12/' . $year);
        $row++;

        /** @var Clients $client */
        foreach ($clientRepository->findValidatedClientsUntilYear($year) as $client) {
            $clientAddress               = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $client]);
            $wallet                      = $walletRepository->getWalletByType($client, WalletType::LENDER);
            $endOfYearBalanceHistory     = $walletBalanceHistoryRepository->getBalanceOfTheDay($wallet, $lastDayOfTheYear);
            $endOfYearBalance            = null !== $endOfYearBalanceHistory ? bcadd($endOfYearBalanceHistory->getAvailableBalance(), $endOfYearBalanceHistory->getCommittedBalance(), 2) : 0;
            $remainingDuCapital          = $operationRepository->getRemainingDueCapitalAtDate($client->getIdClient(), $lastDayOfTheYear);
            $amountInvested              = $operationRepository->sumDebitOperationsByTypeUntil($wallet, [OperationType::LENDER_LOAN], null, $lastDayOfTheYear);
            $firstValidation             = $clientStatusHistoryRepository->getFirstClientValidation($client);
            $fiscalCountryIso            = $lenderImpositionRepository->getFiscalIsoAtDate($wallet, $lastDayOfTheYear);
            $nationalityCountry          = $entityManager->getRepository('UnilendCoreBusinessBundle:Nationalites')->find($client->getIdNationalite());
            $grossInterest               = $operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::GROSS_INTEREST_REPAYMENT], null, $year);
            $grossInterestRegularization = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION], null, $year);
            $yearlyGrossInterest         = round(bcsub($grossInterest, $grossInterestRegularization, 4), 2);

            if (false === $client->isNaturalPerson()) {
                /** @var Companies $company */
                $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
            }

            $activeSheet->setCellValue('A' . $row, $client->getIdClient());
            $activeSheet->setCellValue('B' . $row, $client->getNaissance()->format('Y-m-d'));
            $activeSheet->setCellValue('C' . $row, $client->getVilleNaissance());
            $activeSheet->setCellValue('D' . $row, null !== $nationalityCountry ? $nationalityCountry->getCodePays() : '');
            $activeSheet->setCellValue('E' . $row, $firstValidation->getAdded()->format('Y-m-d'));
            $activeSheet->setCellValue('F' . $row, $client->getIdClientStatusHistory()->getIdStatus()->getId());
            $activeSheet->setCellValue('G' . $row, $client->isNaturalPerson() ? 'Physique' : 'Morale');
            $activeSheet->setCellValue('H' . $row, $client->isNaturalPerson() ? '' : $company->getName());
            $activeSheet->setCellValue('I' . $row, $client->getNom());
            $activeSheet->setCellValue('J' . $row, $client->getNomUsage());
            $activeSheet->setCellValue('K' . $row, $client->getPrenom());
            $activeSheet->setCellValue('L' . $row, $clientAddress->getAdresseFiscal());
            $activeSheet->setCellValue('M' . $row, $clientAddress->getVilleFiscal());
            $activeSheet->setCellValue('N' . $row, $clientAddress->getCpFiscal());
            $activeSheet->setCellValue('O' . $row, $fiscalCountryIso['iso']);
            $activeSheet->setCellValueExplicit('P' . $row, $endOfYearBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit('Q' . $row, $amountInvested, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit('R' . $row, $remainingDuCapital, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit('S' . $row, $yearlyGrossInterest, \PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $row += 1;
        }

        $activeSheet->getStyle('P' . 2 . ':' . 'S' . $row)
            ->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'Excel2007');
        $writer->save(str_replace(__FILE__, $filePath, __FILE__));
    }
}
