<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class QueriesBeneficiaryQueryCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setName('unilend:feeds_out:ifu_beneficiary:generate')
            ->setDescription('Extract all lenders who received money in a given year')
            ->addOption('year', null, InputOption::VALUE_REQUIRED, 'year to export');
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

        $yesterday = new \DateTime('yesterday');

        $filePath          = $ifuManager->getStorageRootPath();
        $filename          = 'requete_beneficiaires_' . date('Ymd') . '.csv';
        $yesterdayFilename = 'requete_beneficiaires_' . $yesterday->format('Ymd') . '.csv';

        $file          = $filePath . DIRECTORY_SEPARATOR . $filename;
        $yesterdayFile = $filePath . DIRECTORY_SEPARATOR . $yesterdayFilename;

        if (file_exists($yesterdayFile)) {
            unlink($yesterdayFile);
        }
        if (file_exists($file)) {
            unlink($file);
        }

        $locationManager = $this->getContainer()->get('unilend.service.location_manager');
        $entityManager   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $numberFormatter = $this->getContainer()->get('number_formatter');

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
            'NomMari',
            'Siret',
            'AdISO',
            'Adresse',
            'Voie',
            'CodeCommune',
            'Commune',
            'CodePostal',
            'Ville / nom pays',
            'IdFiscal',
            'PaysISO',
            'Entité',
            'ToRS',
            'Plib',
            'Tél',
            'Banque',
            'IBAN',
            'BIC',
            'EMAIL',
            'Obs',
            ''
        ];

        $fiscalAndLocationData = [];
        $countryRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2');
        $cityRepository        = $entityManager->getRepository('UnilendCoreBusinessBundle:Villes');

        /** @var Wallet $wallet */
        foreach ($walletsWithMovements as $wallet) {
            $clientEntity  = $wallet->getIdClient();
            $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $clientEntity->getIdClient()]);

            if ($clientEntity->isNaturalPerson()) {
                $fiscalAndLocationData = [
                    'address'    => ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL == $clientAddress->getMemeAdresseFiscal() && empty($clientAddress->getAdresseFiscal()) ? trim($clientAddress->getAdresse1()) : trim($clientAddress->getAdresseFiscal()),
                    'zip'        => ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL == $clientAddress->getMemeAdresseFiscal() && empty($clientAddress->getCpFiscal()) ? trim($clientAddress->getCp()) : trim($clientAddress->getCpFiscal()),
                    'city'       => ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL == $clientAddress->getMemeAdresseFiscal() && empty($clientAddress->getVilleFiscal()) ? trim($clientAddress->getVille) : trim($clientAddress->getVilleFiscal()),
                    'id_country' => ClientsAdresses::SAME_ADDRESS_FOR_POSTAL_AND_FISCAL == $clientAddress->getMemeAdresseFiscal() && empty($clientAddress->getIdPaysFiscal()) ? $clientAddress->getIdPays() : $clientAddress->getIdPaysFiscal()
                ];

                if (0 == $fiscalAndLocationData['id_country']) {
                    $fiscalAndLocationData['id_country'] = PaysV2::COUNTRY_FRANCE;
                }

                $clientCountry                      = $countryRepository->find($fiscalAndLocationData['id_country']);
                $fiscalAndLocationData['isoFiscal'] = $clientCountry->getIso();

                if ($fiscalAndLocationData['id_country'] > PaysV2::COUNTRY_FRANCE) {
                    $fiscalAndLocationData['inseeFiscal'] = $fiscalAndLocationData['zip'];
                    $fiscalAndLocationData['location']    = $fiscalAndLocationData['city'];

                    $fiscalAndLocationData['city'] = $clientCountry->getFr();
                    $inseeCountry                  = $entityManager->getRepository('UnilendCoreBusinessBundle:InseePays')->findCountryWithCodeIsoLike(trim($clientCountry->getIso()));
                    $fiscalAndLocationData['zip']  = null !== $inseeCountry ? $inseeCountry->getCog() : '';

                    $taxType                                   = $entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE);
                    $fiscalAndLocationData['deductedAtSource'] = $numberFormatter->format($taxType->getRate()) . '%';
                } else {
                    $city                                 = $cityRepository->findOneBy(['cp' => $fiscalAndLocationData['zip'], 'ville' => $fiscalAndLocationData['city']]);
                    $fiscalAndLocationData['inseeFiscal'] = null !== $city ? $city->getInsee() : '';
                    $fiscalAndLocationData['location']    = ''; //commune fiscal
                }

                $fiscalAndLocationData['birth_country'] = (0 == $clientEntity->getIdPaysNaissance()) ? PaysV2::COUNTRY_FRANCE : $clientEntity->getIdPaysNaissance();
                $birthCountry                           = $countryRepository->find($fiscalAndLocationData['birth_country']);
                $fiscalAndLocationData['isoBirth']      = null !== $birthCountry ? $birthCountry->getIso() : '';

                if (PaysV2::COUNTRY_FRANCE >= $fiscalAndLocationData['birth_country']) {
                    $fiscalAndLocationData['birthPlace'] = $clientEntity->getVilleNaissance();
                    $fiscalAndLocationData['inseeBirth'] = '00000';
                } else {
                    $fiscalAndLocationData['birthPlace'] = $birthCountry->getFr();

                    if (empty($clientEntity->getInseeBirth())) {
                        $cityList = $locationManager->getCities($clientEntity->getVilleNaissance(), true);
                        if (1 < count($cityList)) {
                            $fiscalAndLocationData['inseeBirth'] = 'Doublon ville de naissance';
                        } else {
                            $birthplace                          = $cityRepository->findOneBy(['ville' => $clientEntity->getVilleNaissance()]);
                            $fiscalAndLocationData['inseeBirth'] = null !== $birthplace && false === empty($birthplace->getInsee) ? $birthplace->getInsee : '00000';
                        }
                    }
                }

                $fiscalAndLocationData['deductedAtSource'] = '';
                $data[]                                    = $this->addPersonLineToBeneficiaryQueryData($wallet, $fiscalAndLocationData);
            }

            if (
                false === $clientEntity->isNaturalPerson()
                && null !== $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $clientEntity->getIdClient()])
            ) {
                $idPays         = (0 == $company->getIdPays()) ? PaysV2::COUNTRY_FRANCE : $company->getIdPays();
                $companyCountry = $countryRepository->find($idPays);

                $fiscalAndLocationData['isoFiscal']   = $companyCountry->getIso();
                $fiscalAndLocationData['inseeFiscal'] = $locationManager->getInseeCode($company->getZip(), $company->getCity());
                $data[]                               = $this->addLegalEntityLineToBeneficiaryQueryData($company, $wallet, $fiscalAndLocationData);
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
            $client->getNom(),
            $client->getPrenom(),
            $client->getNaissance()->format('d/m/Y'),
            empty($client->getInseeBirth()) ? substr($fiscalAndLocationData['inseeBirth'], 0, 2) : substr($client->getInseeBirth(), 0, 2),
            empty($client->getInseeBirth()) ? $fiscalAndLocationData['inseeBirth'] : $client->getInseeBirth(),
            $fiscalAndLocationData['birthPlace'],
            '',
            '',
            $fiscalAndLocationData['isoFiscal'],
            '',
            str_replace(';', ',', $fiscalAndLocationData['address']),
            $fiscalAndLocationData['inseeFiscal'],
            $fiscalAndLocationData['location'],//commune fiscal
            $fiscalAndLocationData['zip'],
            $fiscalAndLocationData['city'],
            '',
            $fiscalAndLocationData['isoBirth'],
            'X',
            $fiscalAndLocationData['deductedAtSource'],
            'N',
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
    private function addLegalEntityLineToBeneficiaryQueryData(Companies $company, Wallet $wallet, array $fiscalAndLocationData)
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
            '',
            $company->getSiret(),
            $fiscalAndLocationData['isoFiscal'],
            '',
            str_replace(';', ',', $company->getAdresse1()),
            $fiscalAndLocationData['inseeFiscal'],
            '',
            $company->getZip(),
            $company->getCity(),
            '',
            $fiscalAndLocationData['isoFiscal'],
            'X',
            '',
            'N',
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
            $activeSheet->setCellValueByColumnAndRow($index, 1, $columnName);
        }

        foreach ($data as $rowIndex => $row) {
            $colIndex = 0;
            foreach ($row as $cellValue) {
                $activeSheet->setCellValueByColumnAndRow($colIndex++, $rowIndex + 2, $cellValue);
            }
        }

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'CSV');
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');
        $writer->save(str_replace(__FILE__, $filePath, __FILE__));
    }
}
