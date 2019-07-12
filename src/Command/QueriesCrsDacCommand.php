<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{
    InputArgument, InputInterface
};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{AddressType, ClientAddress, Clients, ClientsStatusHistory, Companies, CompanyAddress, LendersImpositionHistory, Nationalites, Operation, OperationType, Wallet,
    WalletBalanceHistory, WalletType};

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

        $filePath         = $this->getContainer()->getParameter('directory.protected') . '/queries/' . 'preteurs_crs_dac' . $year . '.xlsx';
        $logger           = $this->getContainer()->get('logger');
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $clientRepository = $entityManager->getRepository(Clients::class);

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
            try {
                $this->writeLineForClient($client, $row, $activeSheet, $lastDayOfTheYear);
            } catch (\Exception $exception) {
                $logger->error('An exception occurred while adding line for client ' . $client->getIdClient() . '. Message: ' . $exception->getMessage(), [
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => __FILE__,
                    'line'      => __LINE__,
                    'id_client' => $client->getIdClient()
                ]);
            }

            $row += 1;
        }

        $activeSheet->getStyle('P' . 2 . ':' . 'S' . $row)
            ->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'Excel2007');
        $writer->save(str_replace(__FILE__, $filePath, __FILE__));
    }

    /**
     * @param Clients             $client
     * @param int                 $row
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \DateTime           $lastDayOfTheYear
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    private function writeLineForClient(Clients $client, int $row, \PHPExcel_Worksheet $activeSheet, \DateTime $lastDayOfTheYear)
    {
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $walletRepository               = $entityManager->getRepository(Wallet::class);
        $walletBalanceHistoryRepository = $entityManager->getRepository(WalletBalanceHistory::class);
        $operationRepository            = $entityManager->getRepository(Operation::class);
        $clientStatusHistoryRepository  = $entityManager->getRepository(ClientsStatusHistory::class);
        $lenderImpositionRepository     = $entityManager->getRepository(LendersImpositionHistory::class);

        $clientAddress               = $this->getMostRecentMainAddress($client);
        $wallet                      = $walletRepository->getWalletByType($client, WalletType::LENDER);
        $endOfYearBalanceHistory     = $walletBalanceHistoryRepository->getBalanceOfTheDay($wallet, $lastDayOfTheYear);
        $endOfYearBalance            = null !== $endOfYearBalanceHistory ? bcadd($endOfYearBalanceHistory->getAvailableBalance(), $endOfYearBalanceHistory->getCommittedBalance(), 2) : 0;
        $remainingDuCapital          = $operationRepository->getRemainingDueCapitalAtDate($client->getIdClient(), $lastDayOfTheYear);
        $amountInvested              = $operationRepository->sumDebitOperationsByTypeUntil($wallet, [OperationType::LENDER_LOAN], null, $lastDayOfTheYear);
        $firstValidation             = $clientStatusHistoryRepository->getFirstClientValidation($client);
        $fiscalCountryIso            = $lenderImpositionRepository->getFiscalIsoAtDate($wallet, $lastDayOfTheYear);
        $nationalityCountry          = $entityManager->getRepository(Nationalites::class)->find($client->getIdNationality());
        $grossInterest               = $operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::GROSS_INTEREST_REPAYMENT], null, $lastDayOfTheYear->format('Y'));
        $grossInterestRegularization = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION], null, $lastDayOfTheYear->format('Y'));
        $yearlyGrossInterest         = round(bcsub($grossInterest, $grossInterestRegularization, 4), 2);

        if (false === $client->isNaturalPerson()) {
            /** @var Companies $company */
            $company = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
        }

        $activeSheet->setCellValue('A' . $row, $client->getIdClient());
        $activeSheet->setCellValue('B' . $row, $client->getDateOfBirth()->format('Y-m-d'));
        $activeSheet->setCellValue('C' . $row, $client->getBirthCity());
        $activeSheet->setCellValue('D' . $row, null !== $nationalityCountry ? $nationalityCountry->get : '');
        $activeSheet->setCellValue('E' . $row, $firstValidation instanceof \DateTime ? $firstValidation->getAdded()->format('Y-m-d') : '');
        $activeSheet->setCellValue('F' . $row, $client->getIdClientStatusHistory()->getIdStatus()->getId());
        $activeSheet->setCellValue('G' . $row, $client->isNaturalPerson() ? 'Physique' : 'Morale');
        $activeSheet->setCellValue('H' . $row, $client->isNaturalPerson() ? '' : $company->getName());
        $activeSheet->setCellValue('I' . $row, $client->getLastName());
        $activeSheet->setCellValue('J' . $row, $client->getPreferredName());
        $activeSheet->setCellValue('K' . $row, $client->getFirstName());
        $activeSheet->setCellValue('L' . $row, null === $clientAddress ? '' : $clientAddress->getAddress());
        $activeSheet->setCellValue('M' . $row, null === $clientAddress ? '' : $clientAddress->getCity());
        $activeSheet->setCellValue('N' . $row, null === $clientAddress ? '' : $clientAddress->getZip());
        $activeSheet->setCellValue('O' . $row, $fiscalCountryIso['iso']);
        $activeSheet->setCellValueExplicit('P' . $row, $endOfYearBalance, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit('Q' . $row, $amountInvested, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit('R' . $row, $remainingDuCapital, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $activeSheet->setCellValueExplicit('S' . $row, $yearlyGrossInterest, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
    }

    /**
     * @param Clients $client
     *
     * @return ClientAddress|CompanyAddress|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    private function getMostRecentMainAddress(Clients $client)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger        = $this->getContainer()->get('logger');

        if ($client->isNaturalPerson()) {
            $clientAddressRepository = $entityManager->getRepository(ClientAddress::class);
            /** @var ClientAddress $mostRecentAddress */
            $mostRecentAddress = $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
            if (null === $mostRecentAddress) {
                $logger->error('Client ' . $client->getIdClient() . ' has no main address' . [
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'id_client' => $client->getIdClient()
                    ]);
            }
        } else {
            /** @var Companies $company */
            $company = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
            if (null === $company) {
                throw new \Exception('Client' . $client->getIdClient() . ' of type legal entity has no company');
            }

            $companyAddressRepository = $entityManager->getRepository(CompanyAddress::class);
            /** @var CompanyAddress $mostRecentAddress */
            $mostRecentAddress = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            if (null === $mostRecentAddress) {
                $logger->error('Company ' . $company->getIdCompany() . ' has no main address' . [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_company' => $company->getIdCompany()
                    ]);
            }
        }

        return $mostRecentAddress;
    }
}
