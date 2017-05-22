<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;

class statsController extends bootstrap
{
    public function initialize()
    {
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', 1200);

        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('stats');

        $this->menu_admin = 'stats';
    }

    public function _default()
    {
        header('Location: /queries');
        die;
    }

    // Ressort un csv avec les process des users
    public function _etape_inscription()
    {
        // Récup des dates
        if (isset($_POST['date1']) && $_POST['date1'] != '') {
            $d1    = explode('/', $_POST['date1']);
            $date1 = $d1[2] . '-' . $d1[1] . '-' . $d1[0];
        } else {
            $_POST['date1'] = date('d/m/Y', strtotime('first day of this month'));
            $date1          = date('Y-m-d', strtotime('first day of this month'));

        }

        if (isset($_POST['date2']) && $_POST['date2'] != '') {
            $d2    = explode('/', $_POST['date2']);
            $date2 = $d2[2] . '-' . $d2[1] . '-' . $d2[0];
        } else {
            $_POST['date2'] = date('d/m/Y', strtotime('last day of this month'));
            $date2          = date('Y-m-d', strtotime('last day of this month'));

        }

        $sql = 'SELECT
                        c.id_client,
                        c.nom,
                        c.prenom,
                        c.email,
                        c.telephone,
                        c.mobile,
                        c.added,
                        c.etape_inscription_preteur,
                        c.source,
                        c.source2
                    FROM clients c
                      INNER JOIN wallet w ON c.id_client = w.id_client
                      INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = "' . \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType::LENDER . '"
                    WHERE c.etape_inscription_preteur > 0 
                        AND c.status = 1 
                        AND c.added >= "' . $date1 . ' 00:00:00' . '"
                        AND c.added <= "' . $date2 . ' 23:59:59"';

        $result = $this->bdd->query($sql);

        $this->L_clients = array();
        while ($record = $this->bdd->fetch_assoc($result)) {
            $this->L_clients[] = $record;
        }

        if (isset($_POST['recup'])) {
            $this->autoFireView = false;
            $this->hideDecoration();

            header("Content-type: application/vnd.ms-excel");
            header("Content-disposition: attachment; filename=\"Export_etape_inscription.csv\"");

            if ($_POST['spy_date1'] != '') {
                $d1    = explode('/', $_POST['spy_date1']);
                $date1 = $d1[2] . '-' . $d1[1] . '-' . $d1[0];
            } else {
                $date1 = date('Y-m-d', strtotime('first day of this month'));
            }

            if ($_POST['spy_date2'] != '') {
                $d2    = explode('/', $_POST['spy_date2']);
                $date2 = $d2[2] . '-' . $d2[1] . '-' . $d2[0];
            } else {
                $date2 = date('Y-m-d', strtotime('last day of this month'));
            }

            $sql = 'SELECT
                        c.id_client,
                        c.nom,
                        c.prenom,
                        c.email,
                        c.telephone,
                        c.mobile,
                        c.added,
                        c.etape_inscription_preteur,
                        c.source,
                        c.source2
                    FROM clients c
                      INNER JOIN wallet w ON c.id_client = w.id_client
                      INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = "' . \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType::LENDER . '"
                    WHERE c.etape_inscription_preteur > 0 
                        AND c.status = 1 
                        AND c.added >= "' . $date1 . ' 00:00:00' . '"
                        AND c.added <= "' . $date2 . ' 23:59:59"';

            $result = $this->bdd->query($sql);

            $this->L_clients = array();
            while ($record = $this->bdd->fetch_assoc($result)) {
                $this->L_clients[] = $record;
            }

            $csv = "id_client;nom;prenom;email;tel;date_inscription;etape_inscription;Source;Source 2;\n";

            foreach ($this->L_clients as $u) {
                $csv .= utf8_decode($u['id_client']) . ';' . utf8_decode($u['nom']) . ';' . utf8_decode($u['prenom']) . ';' . utf8_decode($u['email']) . ';' . utf8_decode($u['telephone'] . ' ' . $u['mobile']) . ';' . utf8_decode($this->dates->formatDate($u['added'], 'd/m/Y')) . ';' . utf8_decode($u['etape_inscription_preteur']) . ';' . $u['source'] . ';' . $u['source2'] . ';' . "\n";
            }

            print($csv);
        }
    }

    public function _requete_beneficiaires_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->loadData('clients_adresses');
        /** @var \companies $company */
        $company = $this->loadData('companies');
        /** @var \pays_v2 $countries */
        $countries = $this->loadData('pays_v2');
        /** @var \villes $cities */
        $cities = $this->loadData('villes');
        /** @var \insee_pays $inseeCountries */
        $inseeCountries = $this->loadData('insee_pays');
        /** @var \tax_type $taxTypes */
        $taxTypes = $this->loadData('tax_type');

        if (in_array(date('m'), ['01', '02', '03'])) {
            $year = (date('Y')-1);
        } else {
            $year = date('Y');
        }

        $operationTypes       = [
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT
        ];
        /** @var WalletRepository $walletRepository */
        $walletRepository     = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletsWithMovements = $walletRepository->getLenderWalletsWithOperationsInYear($operationTypes, $year);

        $filename = 'requete_beneficiaires' . date('Ymd');
        $headers = ['id_client', 'Cbene', 'Nom', 'Qualité', 'NomJFille', 'Prénom', 'DateNaissance', 'DépNaissance', 'ComNaissance', 'LieuNaissance', 'NomMari', 'Siret', 'AdISO', 'Adresse', 'Voie', 'CodeCommune', 'Commune', 'CodePostal', 'Ville / nom pays', 'IdFiscal', 'PaysISO', 'Entité', 'ToRS', 'Plib', 'Tél', 'Banque', 'IBAN', 'BIC', 'EMAIL', 'Obs', ''];

        /** @var Wallet $wallet */
        foreach ($walletsWithMovements as $wallet) {
            $clientEntity = $wallet->getIdClient();
            $clientAddress->get($clientEntity->getIdClient(), 'id_client');
            $fiscalAndLocationData = [];

            if (in_array($clientEntity->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
                $fiscalAndLocationData = [
                    'address'    => $clientAddress->meme_adresse_fiscal == 1 && empty($clientAddress->adresse_fiscal) ? trim($clientAddress->adresse1) : trim($clientAddress->adresse_fiscal),
                    'zip'        => $clientAddress->meme_adresse_fiscal == 1 && empty($clientAddress->cp_fiscal) ? trim($clientAddress->cp) : trim($clientAddress->cp_fiscal),
                    'city'       => $clientAddress->meme_adresse_fiscal == 1 && empty($clientAddress->ville_fiscal) ? trim($clientAddress->ville) : trim($clientAddress->ville_fiscal),
                    'id_country' => $clientAddress->meme_adresse_fiscal == 1 && empty($clientAddress->id_pays_fiscal) ? $clientAddress->id_pays : $clientAddress->id_pays_fiscal
                ];

                if (0 == $fiscalAndLocationData['id_country']) {
                    $fiscalAndLocationData['id_country'] = 1;
                }

                $countries->get($fiscalAndLocationData['id_country'], 'id_pays');
                $fiscalAndLocationData['isoFiscal'] = $countries->iso;
                $countries->unsetData();

                if ($fiscalAndLocationData['id_country'] > PaysV2::COUNTRY_FRANCE) {
                    $fiscalAndLocationData['inseeFiscal'] = $fiscalAndLocationData['zip'];
                    $fiscalAndLocationData['location']    = $fiscalAndLocationData['city'];

                    $countries->get($fiscalAndLocationData['id_country'], 'id_pays');
                    $fiscalAndLocationData['city'] = $countries->fr;
                    $inseeCountries->getByCountryIso(trim($countries->iso));
                    $fiscalAndLocationData['zip'] = $inseeCountries->COG;
                    $countries->unsetData();
                    $inseeCountries->unsetData();

                    $taxTypes->get(TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE);
                    $fiscalAndLocationData['deductedAtSource'] = $this->ficelle->formatNumber($taxTypes->rate) . '%';
                } else {
                    $fiscalAndLocationData['inseeFiscal'] = $cities->getInseeCode($fiscalAndLocationData['zip'], $fiscalAndLocationData['city']);
                    $fiscalAndLocationData['location']  = ''; //commune fiscal
                }

                $fiscalAndLocationData['birth_country'] = (0 == $clientEntity->getIdPaysNaissance()) ? PaysV2::COUNTRY_FRANCE : $clientEntity->getIdPaysNaissance();
                $countries->get($fiscalAndLocationData['birth_country'], 'id_pays');
                $fiscalAndLocationData['isoBirth'] = $countries->iso;
                $countries->unsetData();

                if (PaysV2::COUNTRY_FRANCE >= $fiscalAndLocationData['birth_country']) {
                    $fiscalAndLocationData['birthPlace'] = $clientEntity->getVilleNaissance();
                    $fiscalAndLocationData['inseeBirth'] = '00000';
                } else {
                    $countries->get($clientEntity->getIdPaysNaissance(), 'id_pays');
                    $fiscalAndLocationData['birthPlace'] = $countries->fr;
                    $countries->unsetData();

                    if (empty($clientEntity->getInseeBirth())) {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LocationManager $locationManager */
                        $locationManager = $this->get('unilend.service.location_manager');
                        $cityList = $locationManager->getCities($clientEntity->getVilleNaissance(), true);
                        if (1 < count($cityList)) {
                            $fiscalAndLocationData['inseeBirth'] = 'Doublon ville de naissance';
                        } else {
                            $cities->get($clientEntity->getVilleNaissance(), 'ville');
                            $fiscalAndLocationData['inseeBirth'] = empty($cities->insee) ? '00000': $cities->insee;
                        }
                        $cities->unsetData();
                    }
                }

                $fiscalAndLocationData['deductedAtSource'] = '';

                unset($fiscalAndLocationData['birth_country']);
                $this->addPersonLineToBeneficiaryQueryData($data, $wallet, $fiscalAndLocationData);
            }

            if ($company->get($clientEntity->getIdClient(), 'id_client_owner') && in_array($clientEntity->getType(), [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $company->id_pays = (0 == $company->id_pays) ? 1 : $company->id_pays;
                $countries->get($company->id_pays, 'id_pays');
                $fiscalAndLocationData['isoFiscal']   = $countries->iso;
                $fiscalAndLocationData['inseeFiscal'] = $cities->getInseeCode($company->zip, $company->city);
                $this->addLegalEntityLineToBeneficiaryQueryData($data, $company, $wallet, $fiscalAndLocationData);
            }
        }

        $this->exportCSV($data, $filename, $headers);
    }

    private function addPersonLineToBeneficiaryQueryData(&$data, Wallet $wallet, $fiscalAndLocationData)
    {
        $client      = $wallet->getIdClient();
        /** @var BankAccount $bankAccount */
        $bankAccount = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client->getIdClient());

        $data[] = [
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
            $bankAccount->getIban(),
            $bankAccount->getBic(),
            $client->getEmail(),
            ''
        ];
    }

    private function addLegalEntityLineToBeneficiaryQueryData(&$data, \companies $company, Wallet $wallet, $fiscalAndLocationData)
    {
        $client      = $wallet->getIdClient();
        /** @var BankAccount $bankAccount */
        $bankAccount = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client->getIdClient());

        $data[] = [
            $client->getIdClient(),
            $wallet->getWireTransferPattern(),
            $company->name,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $company->siret,
            $fiscalAndLocationData['isoFiscal'],
            '',
            str_replace(';', ',', $company->adresse1),
            $fiscalAndLocationData['inseeFiscal'],
            '',
            $company->zip,
            $company->city,
            '',
            $fiscalAndLocationData['isoFiscal'],
            'X',
            '',
            'N',
            $company->phone,
            '',
            $bankAccount->getIban(),
            $bankAccount->getBic(),
            $client->getEmail(),
            ''
        ];
    }

    /**
     * Also an IFU query, should contain the same clients as the beneficiary and revenue queries
     */
    public function _requete_infosben()
    {
        $year = date('Y');
        if (isset($this->params[0]) && is_numeric($this->params[0])) {
            $year = (int) $this->params[0];
        }

        $operationTypes       = [
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT
        ];

        /** @var WalletRepository $walletRepository */
        $walletRepository           = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        $this->walletsWithMovements = $walletRepository->getLenderWalletsWithOperationsInYear($operationTypes, $year);
    }

    public function _requete_infosben_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $year = date('Y');
        if (isset($this->params[0]) && is_numeric($this->params[0])) {
            $year = (int) $this->params[0];
        }

        $operationTypes       = [
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT
        ];

        /** @var WalletRepository $walletRepository */
        $walletRepository     = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletsWithMovements = $walletRepository->getLenderWalletsWithOperationsInYear($operationTypes, $year);

        $header = "Cdos;Cbéné;CEtabl;CGuichet;RéfCompte;NatCompte;TypCompte;CDRC;";

        $csv = "";
        $csv .= $header . " \n";

        /** @var Wallet $wallet */
        foreach ($walletsWithMovements as $wallet) {
            $csv .= "1;" . $wallet->getWireTransferPattern() . ";14378;;" . $wallet->getIdClient()->getIdClient() . ";4;6;P;";
            $csv .= " \n";
        }

        $titre = 'requete_infosben' . date('Ymd');
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

        print(utf8_decode($csv));
    }

    /**
     * File generated by cron QueriesLenderRevenueCommand.php
     * Only November to March
     */
    public function _requete_revenus_download()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $fileName = 'requete_revenus' . date('Ymd') . '.csv';
        $filePath = $this->getParameter('path.protected') . '/' . $fileName;

        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/force-download');
            header("Content-Disposition: attachment; filename=\"" . basename($fileName) . "\";");
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            ob_clean();
            flush();
            readfile($filePath);
            exit;
        } else {
            echo "Le fichier n'a pas été généré cette nuit. Le cron s'execuet que de novembre à mars";
        }
    }

    public function _requete_encheres()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $header = "id_project;id_bid;id_client;added;statut;amount;rate;";
        $header = utf8_encode($header);

        $csv = "";
        $csv .= $header . " \n";

        $sql = 'SELECT 
                  id_project, 
                  id_bid, 
                  (SELECT id_client FROM wallet w WHERE w.id = b.id_lender_account) AS id_client, 
                  added, 
                  (CASE status WHEN ' . Bids::STATUS_BID_PENDING . ' THEN "En cours" WHEN ' . Bids::STATUS_BID_ACCEPTED . ' THEN "OK" WHEN ' . Bids::STATUS_BID_REJECTED . ' THEN "KO" END) AS Statut, 
                  ROUND((amount/100),0), REPLACE(rate,".",",") as rate 
                FROM bids b';

        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($resultat)) {
            for ($i = 0; $i <= 6; $i++) {
                $csv .= $record[$i] . ";";
            }
            $csv .= " \n";
        }

        $titre = 'toutes_les_encheres_' . date('Ymd');
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

        print(utf8_decode($csv));
    }

    public function _tous_echeanciers_pour_projet()
    {
        if (isset($_POST['form_envoi_params']) && $_POST['form_envoi_params'] == "ok" && false == empty($_POST['id_projet'])) {
            $this->autoFireView = false;
            $this->hideDecoration();

            $header = "Id_echeancier;Id_lender;Id_projet;Id_loan;Ordre;Montant;Capital;Capital_restant;Interets;Prelevements_obligatoires;Retenues_source;CSG;Prelevements_sociaux;Contributions_additionnelles;Prelevements_solidarite;CRDS;Date_echeance;Date_echeance_reel;Date_echeance_emprunteur;Date_echeance_emprunteur_reel;Status;";
            $header = utf8_encode($header);

            $csv = "";
            $csv .= $header . " \n";

            $sql = 'SELECT
                      e.id_echeancier,
                      e.id_lender,
                      e.id_project,
                      e.id_loan,
                      e.ordre,
                      e.montant,
                      e.capital,
                      SUM(e.capital - e.capital_rembourse) AS capitalRestant,
                      e.interets,
                      SUM(prelevements_obligatoires.amount) AS prelevements_obligatoires,
                      SUM(retenues_source.amount) AS retenues_source,
                      SUM(csg.amount) AS csg,
                      SUM(prelevements_sociaux.amount) AS prelevements_sociaux,
                      SUM(contributions_additionnelles.amount) AS contributions_additionnelles,
                      SUM(prelevements_solidarite.amount) AS prelevements_solidarite,
                      SUM(crds.amount) AS crds,
                      e.date_echeance,
                      e.date_echeance_reel,
                      e.date_echeance_emprunteur,
                      e.date_echeance_emprunteur_reel,
                      e.status
                    FROM echeanciers e
                      LEFT JOIN operation prelevements_obligatoires ON prelevements_obligatoires.id_repayment_schedule = e.id_echeancier AND prelevements_obligatoires.id_type = (SELECT id FROM operation_type WHERE label = \''.OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS.'\')
                      LEFT JOIN operation retenues_source ON retenues_source.id_repayment_schedule = e.id_echeancier AND retenues_source.id_type = (SELECT id FROM operation_type WHERE label = \''.OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE.'\')
                      LEFT JOIN operation csg ON csg.id_repayment_schedule = e.id_echeancier AND csg.id_type = (SELECT id FROM operation_type WHERE label = \''.OperationType::TAX_FR_CSG.'\')
                      LEFT JOIN operation prelevements_sociaux ON prelevements_sociaux.id_repayment_schedule = e.id_echeancier AND prelevements_sociaux.id_type = (SELECT id FROM operation_type WHERE label = \''.OperationType::TAX_FR_SOCIAL_DEDUCTIONS.'\')
                      LEFT JOIN operation contributions_additionnelles ON contributions_additionnelles.id_repayment_schedule = e.id_echeancier AND contributions_additionnelles.id_type  = (SELECT id FROM operation_type WHERE label = \''.OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS.'\')
                      LEFT JOIN operation prelevements_solidarite ON prelevements_solidarite.id_repayment_schedule = e.id_echeancier AND prelevements_solidarite.id_type  = (SELECT id FROM operation_type WHERE label = \''.OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS.'\')
                      LEFT JOIN operation crds ON crds.id_repayment_schedule = e.id_echeancier AND crds.id_type  = (SELECT id FROM operation_type WHERE label = \''.OperationType::TAX_FR_CRDS.'\')
                    WHERE e.id_project = ' . $_POST['id_projet'] . '
                    GROUP BY e.id_echeancier';

            $resultat = $this->bdd->query($sql);
            while ($record = $this->bdd->fetch_array($resultat)) {
                $csv .= $record['id_echeancier'] . ";" . $record['id_lender'] . ";" . $record['id_project'] . ";" . $record['id_loan'] . ";" . $record['ordre'] . ";" . $record['montant'] . ";" . $record['capital'] . ";" . $record['capitalRestant'] . ";" . $record['interets'] . ";" . $record['prelevements_obligatoires'] . ";" . $record['retenues_source'] . ";" . $record['csg'] . ";" . $record['prelevements_sociaux'] . ";" . $record['contributions_additionnelles'] . ";" . $record['prelevements_solidarite'] . ";" . $record['crds'] . ";" . $record['date_echeance'] . ";" . $record['date_echeance_reel'] . ";" . $record['date_echeance_emprunteur'] . ";" . $record['date_echeance_emprunteur_reel'] . ";" . $record['status'] . ";";
                $csv .= " \n";
            }

            $titre = 'tous_echeanciers_pour_projet_' . date('Ymd');
            header("Content-type: application/vnd.ms-excel");
            header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

            print(utf8_decode($csv));
        }
    }

    private function exportCSV($aData, $sFileName, array $aHeaders = null)
    {
        $this->bdd->close();

        PHPExcel_Settings::setCacheStorageMethod(
            PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            array('memoryCacheSize' => '2048MB', 'cacheTime' => 1200)
        );

        $oDocument    = new PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);

        if (count($aHeaders) > 0) {
            foreach ($aHeaders as $iIndex => $sColumnName) {
                $oActiveSheet->setCellValueByColumnAndRow($iIndex, 1, $sColumnName);
            }
        }

        foreach ($aData as $iRowIndex => $aRow) {
            $iColIndex = 0;
            foreach ($aRow as $sCellValue) {
                $oActiveSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $sCellValue);
            }
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $sFileName . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_CSV $oWriter */
        $oWriter = PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        $oWriter->save('php://output');

        die;
    }

    public function _autobid_statistic()
    {
        $oProject = $this->loadData('projects');

        if (isset($_POST['date_from'], $_POST['date_to']) && false === empty($_POST['date_from']) && false === empty($_POST['date_to'])) {
            $aProjectList = $oProject->getAutoBidProjectStatistic(
                \DateTime::createFromFormat('d/m/Y H:i:s', $_POST['date_from'] . ' 00:00:00'),
                \DateTime::createFromFormat('d/m/Y H:i:s', $_POST['date_to'] . ' 23:59:59')
            );

            $this->aProjectList = [];
            foreach ($aProjectList as $aProject) {
                $fRisk                = constant('\projects::RISK_' . trim($aProject['risk']));
                $this->aProjectList[] = [
                    'id_project'                => $aProject['id_project'],
                    'percentage'                => round(($aProject['amount_total_autobid'] / $aProject['amount_total']) * 100, 2) . ' %',
                    'period'                    => $aProject['period'],
                    'risk'                      => $fRisk,
                    'bids_nb'                   => $aProject['bids_nb'],
                    'avg_amount'                => $aProject['avg_amount'],
                    'weighted_avg_rate'         => round($aProject['weighted_avg_rate'], 1),
                    'avg_amount_autobid'        => $aProject['avg_amount_autobid'],
                    'weighted_avg_rate_autobid' => false === empty($aProject['weighted_avg_rate_autobid']) ? round($aProject['weighted_avg_rate_autobid'], 2) : '',
                    'status_label'              => $aProject['status_label'],
                    'date_fin'                  => $aProject['date_fin']
                ];
            }

            if (isset($_POST['extraction_csv'])) {
                $aHeader = array(
                    'id_project',
                    'pourcentage',
                    'period',
                    'risk',
                    'nombre de bids',
                    'montant moyen',
                    'taux moyen pondéré',
                    'montant moyen autolend',
                    'taux moyen pondéré autolend',
                    'status',
                    'date fin de projet'
                );
                $this->exportCSV($this->aProjectList, 'statistiques_autolends' . date('Ymd'), $aHeader);
            }
        }
    }

    public function _requete_source_emprunteurs()
    {
        /** @var \clients $oClient */
        $oClient          = $this->loadData('clients');
        $this->aBorrowers = array();

        if (isset($_POST['dateStart'], $_POST['dateEnd']) && false === empty($_POST['dateStart']) && false === empty($_POST['dateEnd'])) {
            $oDateTimeStart = \DateTime::createFromFormat('d/m/Y', $_POST['dateStart']);
            $oDateTimeEnd   = \DateTime::createFromFormat('d/m/Y', $_POST['dateEnd']);

            if (isset($_POST['queryOptions']) && 'allLines' == $_POST['queryOptions']) {
                $this->aBorrowers = $oClient->getBorrowersContactDetailsAndSource($oDateTimeStart, $oDateTimeEnd, false);
            }
            if (isset($_POST['queryOptions']) && in_array($_POST['queryOptions'], array(
                    'groupBySirenWithDetails',
                    'groupBySiren'
                ))
            ) {
                $this->aBorrowers = $oClient->getBorrowersContactDetailsAndSource($oDateTimeStart, $oDateTimeEnd, true);

                if ('groupBySirenWithDetails' == $_POST['queryOptions']) {
                    foreach ($this->aBorrowers as $iKey => $aBorrower) {
                        if ($aBorrower['countSiren'] > 1) {
                            $this->aBorrowers[$iKey]['firstEntrySource'] = $oClient->getFirstSourceForSiren($aBorrower['siren'], $oDateTimeStart, $oDateTimeEnd);
                            $this->aBorrowers[$iKey]['lastEntrySource']  = $oClient->getLastSourceForSiren($aBorrower['siren'], $oDateTimeStart, $oDateTimeEnd);
                            $this->aBorrowers[$iKey]['lastLabel']        = $this->aBorrowers[$iKey]['label'];
                            $aHeaderExtended = array_keys(($this->aBorrowers[$iKey]));
                        }
                    }
                }
            }

            if (isset($_POST['extraction_csv'])) {
                $aHeader = isset($aHeaderExtended) ? $aHeaderExtended : array_keys(array_shift($this->aBorrowers));
                $this->exportCSV($this->aBorrowers, 'requete_source_emprunteurs' . date('Ymd'), $aHeader);
            }
        }
    }

    public function _declarations_bdf()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $declarationList       = $entityManager->getRepository('UnilendCoreBusinessBundle:TransmissionSequence')->findAll();
        $declarationPath       = $this->getParameter('path.sftp') . 'bdf/emissions/declarations_mensuelles/';
        $this->declarationList = [];

        if (isset($this->params[0], $this->params[1]) && 'file' === $this->params[0] && is_string($this->params[1])) {
            $this->download($declarationPath . $this->params[1]);
        }
        foreach ($declarationList as $declaration) {
            $absoluteFileName = $declarationPath . $declaration->getElementName();

            if (file_exists($absoluteFileName)) {
                if ('01' === $declaration->getAdded()->format('m')) {
                    $year = $declaration->getAdded()->format('Y') - 1;
                } else {
                    $year = $declaration->getAdded()->format('Y');
                }
                $declarationDate                = \DateTime::createFromFormat('Ym', substr($declaration->getElementName(), 6, 6));
                $this->declarationList[$year][] = [
                    'declarationDate' => strftime('%B %Y', $declarationDate->getTimestamp()),
                    'creationDate'    => $declaration->getAdded()->format('d/m/Y H:i'),
                    'link'            => '/stats/declarations_bdf/file/' . $declaration->getElementName(),
                    'fileName'        => $declaration->getElementName()
                ];
            }
        }
    }

    /**
     * @param string $filePath
     */
    protected function download($filePath)
    {
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '";');
            @readfile($filePath);

            exit;
        }
    }
}
