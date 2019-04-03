<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{AddressType, ClientAddress, Clients, Companies, CompanyAddress, InseePays, Pays, TaxType, Wallet};
use Unilend\Bundle\CoreBusinessBundle\Service\IfuManager;

class QueriesBeneficiaryQueryCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setName('unilend:feeds_out:ifu_beneficiary:generate')
            ->setDescription('Generate the lenders details for those who received money in a given year')
            ->addOption('year', null, InputOption::VALUE_REQUIRED, 'Optional. Define the year to export in format YYYY')
            ->setHelp(<<<EOF
The <info>unilend:feeds_out:ifu_beneficiary:generate</info> command generate a csv which contains the lenders details for those who received money in a given year.
Usage <info>bin/console unilend:feeds_out:ifu_beneficiary:generate [-year=2017]</info>
The <info>year</info> is optional. By default, it generates the file of the last year if we are on January, February or March, otherwise it generates the file for the current year.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ifuManager = $this->getContainer()->get('unilend.service.ifu_manager');

        $year = $input->getOption('year');
        if (empty($year)) {
            $year = $ifuManager->getYear();
        }

        $filePath = $ifuManager->getStorageRootPath();
        $filename = IfuManager::FILE_NAME_BENEFICIARY;
        $file     = $filePath . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($file)) {
            unlink($file);
        }

        $locationManager          = $this->getContainer()->get('unilend.service.location_manager');
        $entityManager            = $this->getContainer()->get('doctrine.orm.entity_manager');
        $numberFormatter          = $this->getContainer()->get('number_formatter');
        $logger                   = $this->getContainer()->get('logger');
        $clientAddressRepository  = $entityManager->getRepository(ClientAddress::class);
        $companyAddressRepository = $entityManager->getRepository(CompanyAddress::class);
        $countryRepository        = $entityManager->getRepository(Pays::class);

        $walletsWithMovements = $ifuManager->getWallets($year);

        $data = [];
        /*
         * Headers contain still Bank information, however as it is not mandatory information we leave the fields empty
         * when this file is modified the next time, check if the fields can not be simply deleted as well as other non mandatory fields
         */
        $headers = [
            'id_client',
            'Cbene',
            'Nom',
            'Qualité',
            'NomJFille',
            'Prénom',
            'DateNaissance',
            'DépNaissance',
            'ComNaissance',
            'LieuNaissance',
            'Siret',
            'AdISO',
            'Adresse',
            'Voie',
            'CodeCommune',
            'Commune',
            'CodePostal',
            'Ville',
            'PaysISO',
            'ToRS',
            'Tél',
            'Banque',
            'IBAN',
            'BIC',
            'EMAIL',
            'Obs'
        ];

        /** @var Wallet $wallet */
        foreach ($walletsWithMovements as $wallet) {
            $client  = $wallet->getIdClient();

            if ($client->isNaturalPerson()) {
                /** @var ClientAddress $mostRecentAddress */
                $mostRecentAddress = $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
                if (null === $mostRecentAddress) {
                    $logger->error('Client ' . $client->getIdClient() . ' has no main address.', [
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'id_client' => $client->getIdClient()
                        ]);

                    $mostRecentAddress = $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_POSTAL_ADDRESS);
                    if (null === $mostRecentAddress) {
                        $logger->error('Client ' . $client->getIdClient() . ' has no postal address.', [
                                'class'     => __CLASS__,
                                'function'  => __FUNCTION__,
                                'id_client' => $client->getIdClient()
                            ]);
                    }
                }

                $fiscalAndLocationData = [
                    'address'          => null === $mostRecentAddress ? '' : $mostRecentAddress->getAddress(),
                    'zip'              => null === $mostRecentAddress ? '' : $mostRecentAddress->getZip(),
                    'city'             => null === $mostRecentAddress ? '' : $mostRecentAddress->getCity(),
                    'id_country'       => null === $mostRecentAddress ? '' : $mostRecentAddress->getIdCountry()->getIdPays(),
                    'isoFiscal'        => null === $mostRecentAddress ? '' : $mostRecentAddress->getIdCountry()->getIso(),
                    'location'         => '',
                    'inseeFiscal'      => null === $mostRecentAddress ? '' : $mostRecentAddress->getCog(),
                    'deductedAtSource' => ''
                ];

                if (null !== $mostRecentAddress) {
                    if (
                        $fiscalAndLocationData['id_country'] !== Pays::COUNTRY_FRANCE
                        && false === in_array($fiscalAndLocationData['id_country'], Pays::FRANCE_DOM_TOM)
                    ) {
                        $fiscalAndLocationData['inseeFiscal'] = $fiscalAndLocationData['zip'];
                        $fiscalAndLocationData['location']    = $fiscalAndLocationData['city'];

                        $fiscalAndLocationData['city'] = $mostRecentAddress->getIdCountry()->getFr();
                        $inseeCountry                  = $entityManager->getRepository(InseePays::class)->findCountryWithCodeIsoLike($mostRecentAddress->getIdCountry()->getIso());
                        $fiscalAndLocationData['zip']  = null !== $inseeCountry ? $inseeCountry->getCog() : '';

                        // The tax rate is change in 2018. We need to use TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE_PERSON instead.
                        // But as we don't have the history of tax rate, we leave it unchanged till March 2018.
                        $taxType                                   = $entityManager->getRepository(TaxType::class)->find(TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE);
                        $fiscalAndLocationData['deductedAtSource'] = $numberFormatter->format($taxType->getRate()) . '%';
                    }
                }

                $fiscalAndLocationData['birth_country'] = (0 == $client->getIdPaysNaissance()) ? Pays::COUNTRY_FRANCE : $client->getIdPaysNaissance();
                $birthCountry                           = $countryRepository->find($fiscalAndLocationData['birth_country']);
                $fiscalAndLocationData['isoBirth']      = null !== $birthCountry ? $birthCountry->getIso() : '';

                if (
                    Pays::COUNTRY_FRANCE < $fiscalAndLocationData['birth_country']
                    && false === in_array($fiscalAndLocationData['birth_country'], Pays::FRANCE_DOM_TOM)
                ) {
                    $fiscalAndLocationData['birthPlace'] = $birthCountry->getFr();
                    if (empty($client->getInseeBirth()) && $fiscalAndLocationData['isoBirth']) {
                        $inseeBirthCountry                   = $entityManager->getRepository(InseePays::class)->findCountryWithCodeIsoLike($fiscalAndLocationData['isoBirth']);
                        $fiscalAndLocationData['inseeBirth'] = $inseeBirthCountry ? $inseeBirthCountry->getCog() : '00000';
                    } else {
                        $fiscalAndLocationData['inseeBirth'] = '00000';
                    }
                } else {
                    $fiscalAndLocationData['birthPlace'] = $client->getVilleNaissance();
                    if (empty($client->getInseeBirth())) {
                        $cityList = $locationManager->getCities($client->getVilleNaissance(), true);
                        if (1 < count($cityList)) {
                            $fiscalAndLocationData['inseeBirth'] = 'Doublon ville de naissance';
                        } elseif (1 === count($cityList)) {
                            $fiscalAndLocationData['inseeBirth'] = $cityList[0]['value'];
                        } else {
                            $fiscalAndLocationData['inseeBirth'] = '00000';
                        }
                    }
                }

                $data[] = $this->addPersonLineToBeneficiaryQueryData($wallet, $fiscalAndLocationData);
            }

            if (
                false === $client->isNaturalPerson()
                && null !== $company = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client])
            ) {
                /** @var CompanyAddress $mostRecentAddress */
                $mostRecentAddress = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
                if (null === $mostRecentAddress) {
                    $logger->error('Company ' . $company->getIdCompany() . ' has no main address.', [
                            'class'      => __CLASS__,
                            'function'   => __FUNCTION__,
                            'id_company' => $company->getIdCompany()
                        ]);

                    $mostRecentAddress = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_POSTAL_ADDRESS);
                    if (null === $mostRecentAddress) {
                        $logger->error('Company ' . $company->getIdCompany() . ' has no postal address.', [
                                'class'      => __CLASS__,
                                'function'   => __FUNCTION__,
                                'id_company' => $company->getIdCompany()
                            ]);
                    }
                }

                $fiscalAndLocationData = [
                    'address'     => null === $mostRecentAddress ? '' : $mostRecentAddress->getAddress(),
                    'zip'         => null === $mostRecentAddress ? '' : $mostRecentAddress->getZip(),
                    'city'        => null === $mostRecentAddress ? '' : $mostRecentAddress->getCity(),
                    'id_country'  => null === $mostRecentAddress ? '' : $mostRecentAddress->getIdCountry()->getIdPays(),
                    'isoFiscal'   => null === $mostRecentAddress ? '' : $mostRecentAddress->getIdCountry()->getIso(),
                    'location'    => '',
                    'inseeFiscal' => null === $mostRecentAddress ? '' : $mostRecentAddress->getCog()
                ];

                $data[] = $this->addLegalEntityLineToBeneficiaryQueryData($company, $wallet, $fiscalAndLocationData);
            }
        }

        $this->exportCSV($data, $file, $headers);
    }

    /**
     * @param Wallet $wallet
     * @param array  $fiscalAndLocationData
     *
     * @return array
     */
    private function addPersonLineToBeneficiaryQueryData(Wallet $wallet, array $fiscalAndLocationData)
    {
        $client = $wallet->getIdClient();

        return [
            $client->getIdClient(),
            $wallet->getWireTransferPattern(),
            $client->getNom(),
            $client->getCivilite(),
            ($client->getCivilite() === Clients::TITLE_MISS) ? $client->getNom() : '',
            $client->getPrenom(),
            $client->getNaissance()->format('d/m/Y'),
            empty($client->getInseeBirth()) ? substr($fiscalAndLocationData['inseeBirth'], 0, 2) : substr($client->getInseeBirth(), 0, 2),
            empty($client->getInseeBirth()) ? $fiscalAndLocationData['inseeBirth'] : $client->getInseeBirth(),
            $fiscalAndLocationData['birthPlace'],
            '',
            $fiscalAndLocationData['isoFiscal'],
            '',
            str_replace(';', ',', $fiscalAndLocationData['address']),
            $fiscalAndLocationData['inseeFiscal'],
            $fiscalAndLocationData['location'],//commune fiscal
            $fiscalAndLocationData['zip'],
            $fiscalAndLocationData['city'],
            $fiscalAndLocationData['isoBirth'],
            $fiscalAndLocationData['deductedAtSource'],
            $client->getTelephone(),
            '',
            '',
            '',
            $client->getEmail(),
            ''
        ];
    }

    /**
     * @param Companies $company
     * @param Wallet    $wallet
     * @param array     $fiscalAndLocationData
     *
     * @return array
     */
    private function addLegalEntityLineToBeneficiaryQueryData(Companies $company, Wallet $wallet, array $fiscalAndLocationData): array
    {
        $client = $wallet->getIdClient();

        return [
            $client->getIdClient(),
            $wallet->getWireTransferPattern(),
            $company->getName(),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $company->getSiret(),
            $fiscalAndLocationData['isoFiscal'],
            '',
            str_replace(';', ',', $fiscalAndLocationData['address']),
            $fiscalAndLocationData['inseeFiscal'],
            '',
            $fiscalAndLocationData['zip'],
            $fiscalAndLocationData['city'],
            $fiscalAndLocationData['isoFiscal'],
            '',
            $company->getPhone(),
            '',
            '',
            '',
            $client->getEmail(),
            ''
        ];
    }

    /**
     * @param       $data
     * @param       $filePath
     * @param array $headers
     *
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function exportCSV($data, $filePath, array $headers)
    {
        \PHPExcel_Settings::setCacheStorageMethod(
            \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            ['memoryCacheSize' => '2048MB', 'cacheTime' => 1200]
        );

        $document    = new \PHPExcel();
        $activeSheet = $document->setActiveSheetIndex(0);

        foreach ($headers as $index => $columnName) {
            $activeSheet->setCellValueExplicitByColumnAndRow($index, 1, $columnName);
        }

        foreach ($data as $rowIndex => $row) {
            $colIndex = 0;
            foreach ($row as $cellValue) {
                $activeSheet->setCellValueExplicitByColumnAndRow($colIndex++, $rowIndex + 2, $cellValue);
            }
        }

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'CSV');
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');
        $writer->save(str_replace(__FILE__, $filePath, __FILE__));
    }
}
