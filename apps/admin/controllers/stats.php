<?php

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
        // Chargement de la lib google
        $this->ga = $this->loadLib('gapi', array($this->google_mail, $this->google_password, (isset($_SESSION['ga_auth_token']) ? $_SESSION['ga_auth_token'] : null)));

        // Mise en session de la connexion GA
        $_SESSION['ga_auth_token'] = $this->ga->getAuthToken();

        // Recuperation de l'ID
        $this->ga->requestAccountData();

        foreach ($this->ga->getResults() as $result) {
            $this->id_profile = $result->getProfileId();
        }

        // Traitement des dates du formulaire
        if (isset($_POST['next'])) {
            $this->mois  = intval($_POST['mois']);
            $this->annee = intval($_POST['annee']);

            $this->mois++;

            if ($this->mois > 12) {
                $this->mois = 1;
                $this->annee++;
            }

            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = $this->mois;
            $this->deb_annee = $this->fin_annee = $this->annee;
            $this->fin_jour  = $this->dates->nb_jour_dans_mois($this->mois, $this->annee);

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->deb_mois < 10) {
                $this->deb_mois = '0' . $this->deb_mois;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
            if ($this->fin_mois < 10) {
                $this->fin_mois = '0' . $this->fin_mois;
            }
        } elseif (isset($_POST['prev'])) {
            $this->mois  = intval($_POST['mois']);
            $this->annee = intval($_POST['annee']);

            $this->mois--;

            if ($this->mois < 1) {
                $this->mois = 12;
                $this->annee--;
            }

            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = $this->mois;
            $this->deb_annee = $this->fin_annee = $this->annee;
            $this->fin_jour  = $this->dates->nb_jour_dans_mois($this->mois, $this->annee);

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->deb_mois < 10) {
                $this->deb_mois = '0' . $this->deb_mois;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
            if ($this->fin_mois < 10) {
                $this->fin_mois = '0' . $this->fin_mois;
            }
        } elseif (isset($_POST['voir'])) {
            $this->mois  = intval($_POST['mois']);
            $this->annee = intval($_POST['annee']);

            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = $this->mois;
            $this->deb_annee = $this->fin_annee = $this->annee;
            $this->fin_jour  = $this->dates->nb_jour_dans_mois($this->mois, $this->annee);

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->deb_mois < 10) {
                $this->deb_mois = '0' . $this->deb_mois;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
            if ($this->fin_mois < 10) {
                $this->fin_mois = '0' . $this->fin_mois;
            }
        } elseif (isset($_POST['intervalle'])) {
            $this->deb_jour  = $_POST['du-jour'];
            $this->deb_mois  = $_POST['du-mois'];
            $this->deb_annee = $_POST['du-annee'];
            $this->fin_jour  = $_POST['au-jour'];
            $this->fin_mois  = $_POST['au-mois'];
            $this->fin_annee = $_POST['au-annee'];

            $this->mois  = $this->deb_mois;
            $this->annee = $this->deb_annee;

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->deb_mois < 10) {
                $this->deb_mois = '0' . $this->deb_mois;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
            if ($this->fin_mois < 10) {
                $this->fin_mois = '0' . $this->fin_mois;
            }
        } else {
            // Attribution jour, mois, année par défaut
            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = date('m');
            $this->deb_annee = $this->fin_annee = date('Y');
            $this->fin_jour  = $this->dates->nb_jour_dans_mois(date('m'), date('Y'));

            $_POST['du-jour']  = $this->deb_jour;
            $_POST['du-mois']  = $this->deb_mois;
            $_POST['du-annee'] = $this->deb_annee;
            $_POST['au-jour']  = $this->fin_jour;
            $_POST['au-mois']  = $this->fin_mois;
            $_POST['au-annee'] = $this->fin_annee;

            $this->mois  = $this->deb_mois;
            $this->annee = $this->deb_annee;

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }
        }

        // Recuperation du nombre de jours
        $this->nb_jours = $this->dates->intervalleJours($this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);

        if ($this->nb_jours == 0) {
            $this->deb_jour  = 1;
            $this->deb_mois  = $this->fin_mois = date('m');
            $this->deb_annee = $this->fin_annee = date('Y');
            $this->fin_jour  = $this->dates->nb_jour_dans_mois(date('m'), date('Y'));

            $_POST['du-jour']  = $this->deb_jour;
            $_POST['du-mois']  = $this->deb_mois;
            $_POST['du-annee'] = $this->deb_annee;
            $_POST['au-jour']  = $this->fin_jour;
            $_POST['au-mois']  = $this->fin_mois;
            $_POST['au-annee'] = $this->fin_annee;

            $this->mois  = $this->deb_mois;
            $this->annee = $this->deb_annee;

            if ($this->deb_jour < 10) {
                $this->deb_jour = '0' . $this->deb_jour;
            }
            if ($this->fin_jour < 10) {
                $this->fin_jour = '0' . $this->fin_jour;
            }

            $this->nb_jours = $this->dates->intervalleJours($this->deb_jour, $this->deb_mois, $this->deb_annee, $this->fin_jour, $this->fin_mois, $this->fin_annee);
        }

        // Recupearation d'un rapport GA
        $this->ga->requestReportData($this->id_profile, array('visitCount'), array('pageviews', 'visits', 'newVisits'), null, null, $this->deb_annee . '-' . $this->deb_mois . '-' . $this->deb_jour, $this->fin_annee . '-' . $this->fin_mois . '-' . $this->fin_jour);
    }


    // Ressort un csv avec les process des users
    public function _etape_inscription()
    {
        // Récup des dates
        if (isset($_POST['date1']) && $_POST['date1'] != '') {
            $d1    = explode('/', $_POST['date1']);
            $date1 = $d1[2] . '-' . $d1[1] . '-' . $d1[0];
        } else {
            $_POST['date1'] = date('d/m/Y', strtotime('first day of this month')); //"01/08/2014";
            $date1          = date('Y-m-d', strtotime('first day of this month')); //"2014-08-01";

        }

        if (isset($_POST['date2']) && $_POST['date2'] != '') {
            $d2    = explode('/', $_POST['date2']);
            $date2 = $d2[2] . '-' . $d2[1] . '-' . $d2[0];
        } else {
            $_POST['date2'] = date('d/m/Y', strtotime('last day of this month')); //"31/08/2014";
            $date2          = date('Y-m-d', strtotime('last day of this month')); //"2014-08-31";

        }

        // récup de tous les clients créés depuis le 1 aout
        $sql = 'SELECT
                        c.id_client,
                        c.nom,
                        c.prenom,
                        c.email,
                        c.telephone,
                        c.mobile,
                        c.added,
                            IF (
                                c.etape_inscription_preteur = 3,
                                IF (
                                    la.type_transfert = 1, "3. Virement","3. CB"),
                                    c.etape_inscription_preteur
                                    ) as etape_inscription_preteur2,
                        c.source,
                        c.source2
                            FROM clients c
                            LEFT JOIN lenders_accounts la ON (la.id_client_owner = c.id_client)
                            WHERE c.etape_inscription_preteur > 0 AND c.status = 1 AND c.added >= "' . $date1 . ' 00:00:00' . '" AND c.added <= "' . $date2 . ' 23:59:59";';

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

            // Récup des dates
            if ($_POST['spy_date1'] != '') {
                $d1    = explode('/', $_POST['spy_date1']);
                $date1 = $d1[2] . '-' . $d1[1] . '-' . $d1[0];
            } else {
                $date1 = date('Y-m-d', strtotime('first day of this month')); //"2014-08-01";
            }

            if ($_POST['spy_date2'] != '') {
                $d2    = explode('/', $_POST['spy_date2']);
                $date2 = $d2[2] . '-' . $d2[1] . '-' . $d2[0];
            } else {
                $date2 = date('Y-m-d', strtotime('last day of this month')); //"2014-08-31";
            }

            $sql = 'SELECT
                        c.id_client,
                        c.nom,
                        c.prenom,
                        c.email,
                        c.telephone,
                        c.mobile,
                        c.added,
                            IF (
                                c.etape_inscription_preteur = 3,
                                IF (
                                    la.type_transfert = 1, "3. Virement","3. CB"),
                                    c.etape_inscription_preteur
                                    ) as etape_inscription_preteur2,
                        c.source,
                        c.source2
                            FROM clients c
                            LEFT JOIN lenders_accounts la ON (la.id_client_owner = c.id_client)
                            WHERE c.etape_inscription_preteur > 0 AND c.status = 1 AND c.added >= "' . $date1 . ' 00:00:00' . '" AND c.added <= "' . $date2 . ' 23:59:59";';

            $result = $this->bdd->query($sql);

            $this->L_clients = array();
            while ($record = $this->bdd->fetch_assoc($result)) {
                $this->L_clients[] = $record;
            }

            $csv = "id_client;nom;prenom;email;tel;date_inscription;etape_inscription;Source;Source 2;\n";
            // construction de chaque ligne
            foreach ($this->L_clients as $u) {
                // on concatene a $csv
                $csv .= utf8_decode($u['id_client']) . ';' . utf8_decode($u['nom']) . ';' . utf8_decode($u['prenom']) . ';' . utf8_decode($u['email']) . ';' . utf8_decode($u['telephone'] . ' ' . $u['mobile']) . ';' . utf8_decode($this->dates->formatDate($u['added'], 'd/m/Y')) . ';' . utf8_decode($u['etape_inscription_preteur2']) . ';' . $u['source'] . ';' . $u['source2'] . ';' . "\n";
            }

            print($csv);
        }
    }

    public function _requete_beneficiaires_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        /** @var \clients $clients */
        $clients = $this->loadData('clients');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $this->loadData('clients_adresses');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->loadData('lenders_accounts');
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
        /** @var \transactions $transactions */
        $transactions = $this->loadData('transactions');

        $clientList = $transactions->getClientsWithLoanRelatedTransactions(date('Y'));
        $data = [];

        $filename = 'requete_beneficiaires' . date('Ymd');
        $headers = ['id_client', 'Cbene', 'Nom', 'Qualité', 'NomJFille', 'Prénom', 'DateNaissance', 'DépNaissance', 'ComNaissance', 'LieuNaissance', 'NomMari', 'Siret', 'AdISO', 'Adresse', 'Voie', 'CodeCommune', 'Commune', 'CodePostal', 'Ville / nom pays', 'IdFiscal', 'PaysISO', 'Entité', 'ToRS', 'Plib', 'Tél', 'Banque', 'IBAN', 'BIC', 'EMAIL', 'Obs', ''];

        foreach ($clientList as $client) {
            $clients->get($client['id_client']);
            $clientAddress->get($clients->id_client, 'id_client');
            $lenderAccount->get($clients->id_client, 'id_client_owner');
            $fiscalAndLocationData = [];

            if (in_array($clients->type, [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER])) {
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

                if ($fiscalAndLocationData['id_country'] > \pays_v2::COUNTRY_FRANCE) {
                    $fiscalAndLocationData['inseeFiscal'] = $fiscalAndLocationData['zip'];
                    $fiscalAndLocationData['location']    = $fiscalAndLocationData['city'];

                    $countries->get($fiscalAndLocationData['id_country'], 'id_pays');
                    $fiscalAndLocationData['city'] = $countries->fr;
                    $inseeCountries->getByCountryIso(trim($countries->iso));
                    $fiscalAndLocationData['zip'] = $inseeCountries->COG;
                    $countries->unsetData();
                    $inseeCountries->unsetData();

                    $taxTypes->get(\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE);
                    $fiscalAndLocationData['deductedAtSource'] = $this->ficelle->formatNumber($taxTypes->rate) . '%';
                } else {
                    $fiscalAndLocationData['inseeFiscal'] = $cities->getInseeCode($fiscalAndLocationData['zip'], $fiscalAndLocationData['city']);
                    $fiscalAndLocationData['location']  = ''; //commune fiscal
                }

                $fiscalAndLocationData['birth_country'] = (0 == $clients->id_pays_naissance) ? 1 : $clients->id_pays_naissance;
                $countries->get($fiscalAndLocationData['birth_country'], 'id_pays');
                $fiscalAndLocationData['isoBirth'] = $countries->iso;
                $countries->unsetData();

                if (\pays_v2::COUNTRY_FRANCE >= $fiscalAndLocationData['birth_country']) {
                    $fiscalAndLocationData['birthPlace'] = $clients->ville_naissance;
                    $fiscalAndLocationData['inseeBirth'] = '00000';
                } else {
                    $countries->get($clients->id_pays_naissance, 'id_pays');
                    $fiscalAndLocationData['birthPlace'] = $countries->fr;
                    $countries->unsetData();

                    if (empty($clients->insee_birth)) {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LocationManager $locationManager */
                        $locationManager = $this->get('unilend.service.location_manager');
                        $cityList = $locationManager->getCities($clients->ville_naissance, true);
                        if (1 < count($cityList)) {
                            $fiscalAndLocationData['inseeBirth'] = 'Doublon ville de naissance';
                        } else {
                            $cities->get($clients->ville_naissance, 'ville');
                            $fiscalAndLocationData['inseeBirth'] = empty($cities->insee) ? '00000': $cities->insee;
                        }
                        $cities->unsetData();
                    }
                }

                $fiscalAndLocationData['deductedAtSource'] = '';

                unset($fiscalAndLocationData['birth_country']);
                $this->addPersonLineToBeneficiaryQueryData($data, $lenderAccount, $clients, $fiscalAndLocationData);
            }

            if ($company->get($clients->id_client, 'id_client_owner') && in_array($clients->type, [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $company->id_pays = (0 == $company->id_pays) ? 1 : $company->id_pays;
                $countries->get($company->id_pays, 'id_pays');
                $fiscalAndLocationData['isoFiscal']   = $countries->iso;
                $fiscalAndLocationData['inseeFiscal'] = $cities->getInseeCode($company->zip, $company->city);
                $this->addLegalEntityLineToBeneficiaryQueryData($data, $company, $lenderAccount, $clients, $fiscalAndLocationData);
            }
        }

        $this->exportCSV($data, $filename, $headers);
    }

    private function addPersonLineToBeneficiaryQueryData(&$data, \lenders_accounts $lenderAccount, \clients $clients, $fiscalAndLocationData)
    {
        /** @var \DateTime $birthDate */
        $birthDate = \DateTime::createFromFormat('Y-m-d', $clients->naissance);

        $data[] = [
            $clients->id_client,
            $clients->getLenderPattern($clients->id_client),
            $clients->nom,
            $clients->civilite,
            $clients->nom,
            $clients->prenom,
            $birthDate->format('d/m/Y'),
            empty($clients->insee_birth) ? substr($fiscalAndLocationData['inseeBirth'], 0, 2) : substr($clients->insee_birth, 0, 2),
            empty($clients->insee_birth) ? $fiscalAndLocationData['inseeBirth'] : $clients->insee_birth,
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
            $clients->telephone,
            '',
            $lenderAccount->iban,
            $lenderAccount->bic,
            $clients->email,
            ''
        ];
    }

    private function addLegalEntityLineToBeneficiaryQueryData(&$data, \companies $company, \lenders_accounts $lenderAccount, \clients $clients, $fiscalAndLocationData)
    {
        $data[] = [
            $clients->id_client,
            $clients->getLenderPattern($clients->id_client),
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
            $lenderAccount->iban,
            $lenderAccount->bic,
            $clients->email,
            ''
        ];
    }

    public function _requete_infosben()
    {
        $oLendersAccounts = $this->loadData('lenders_accounts');

        $iYear = date('Y');
        if (isset($this->params[0]) && is_numeric($this->params[0])) {
            $iYear = (int) $this->params[0];
        }

        $this->aLenders = $oLendersAccounts->getInfosben($iYear);
    }

    public function _requete_infosben_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $oLendersAccounts = $this->loadData('lenders_accounts');

        $iYear = date('Y');
        if (isset($this->params[0]) && is_numeric($this->params[0])) {
            $iYear = (int) $this->params[0];
        }

        $this->aLenders = $oLendersAccounts->getInfosben($iYear);

        $header = "Cdos;Cbéné;CEtabl;CGuichet;RéfCompte;NatCompte;TypCompte;CDRC;";

        $csv = "";
        $csv .= $header . " \n";

        foreach ($this->aLenders as $aLender) {
            // Motif
            $sPrenom   = substr($this->ficelle->stripAccents(trim($aLender['prenom'])), 0, 1);
            $sNom      = $this->ficelle->stripAccents(trim($aLender['nom']));
            $motif     = mb_strtoupper($aLender['id_client'] . $sPrenom . $sNom, 'UTF-8');
            $motif     = substr($motif, 0, 10);

            $csv .= "1;" . $motif . ";14378;;" . $aLender['id_client'] . ";4;6;P;";
            $csv .= " \n";
        }

        $titre = 'requete_infosben' . date('Ymd');
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: attachment; filename=\"" . $titre . ".csv\"");

        print(utf8_decode($csv));
    }

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
            echo "Le fichier n'a pas été généré cette nuit";
        }
    }

    public function _requete_revenus_csv()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        /** @var \clients $clients */
        $clients = $this->loadData('clients');

        /** @var \pays_v2 $countriesEntity */
        $countriesEntity    = $this->loadData('pays_v2');
        $countries          = $countriesEntity->getZoneB040Countries();
        $zoneB040CountryIds = [];

        foreach ($countries as $country) {
            $zoneB040CountryIds[] = $country['id_pays'];
        }

        $year = in_array(date('m'), ['01', '02', '03']) ? (date('Y') - 1) : date('Y');

        $commonValues = [
            'CodeEntreprise' => 1, //official code of SFPMEI
            'Date'           => '31/12/' . $year,
            'Monnaie'        => 'EURO'
        ];

        $row = 1;
        /** @var \PHPExcel $csvFile */
        $csvFile     = new \PHPExcel();
        $activeSheet = $csvFile->setActiveSheetIndex(0);
        $activeSheet->setCellValueByColumnAndRow(0, $row, 'Code Entreprise');
        $activeSheet->setCellValueByColumnAndRow(1, $row, 'CodeBénéficiaire');
        $activeSheet->setCellValueByColumnAndRow(2, $row, 'CodeV');
        $activeSheet->setCellValueByColumnAndRow(3, $row, 'Date');
        $activeSheet->setCellValueByColumnAndRow(4, $row, 'Montant');
        $activeSheet->setCellValueByColumnAndRow(5, $row, 'Monnaie');
        $row += 1;

        $sql = '
              SELECT
                c.id_client,
                SUM(ROUND(t.montant/100, 2)) AS interests,
                SUM(ROUND(retenues_source.amount / 100, 2)) AS retenues_source,
                SUM(ROUND(prelevements_obligatoires.amount / 100, 2)) AS prlv_obligatoire
              FROM clients c
                LEFT JOIN transactions t ON c.id_client = t.id_client AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
                LEFT JOIN tax retenues_source ON retenues_source.id_transaction = t.id_transaction AND retenues_source.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE . '
                LEFT JOIN tax prelevements_obligatoires ON prelevements_obligatoires.id_transaction = t.id_transaction AND prelevements_obligatoires.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX . '
              WHERE YEAR(t.date_transaction) = ' . $year . '
              GROUP BY c.id_client';
        $resultat = $this->bdd->query($sql);

        while ($record = $this->bdd->fetch_array($resultat)) {
            $commonValues['lenderPattern'] = $clients->getLenderPattern($record['id_client']);

            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '53');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($record['interests'], 2, ',', ''));
            $row += 1;

            if ($record['retenues_source'] > 0) {
                $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '53');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($record['retenues_source'], 2, ',', ''));
                $row += 1;
            }

            if ($record['prlv_obligatoire'] > 0) {
                $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
                $activeSheet->setCellValueByColumnAndRow(2, $row, '54');
                $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($record['prlv_obligatoire'], 2, ',', ''));
                $row += 1;
            }

            $this->addRepaymentBasedLinesToRevenuesQueryCSV($activeSheet, $record['id_client'], $year, $zoneB040CountryIds, $row, $commonValues);
            unset($commonValues['lenderPattern']);
        }

        $this->addLoansToRevenueQueryCSV($activeSheet, $row, $commonValues, $year, $clients);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=requete_revenus' . date('Ymd') . '.csv');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = PHPExcel_IOFactory::createWriter($csvFile, 'CSV');
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');
        $writer->save('php://output');
    }

    /**
     * @param PHPExcel_Worksheet $activeSheet
     * @param int $row
     * @param array $commonValues
     */
    private function addCommonCellValuesToRevenueQueryCSV(\PHPExcel_Worksheet &$activeSheet, $row, $commonValues)
    {
        $activeSheet->setCellValueByColumnAndRow(0, $row, $commonValues['CodeEntreprise']);
        $activeSheet->setCellValueByColumnAndRow(1, $row, $commonValues['lenderPattern']);
        $activeSheet->setCellValueByColumnAndRow(3, $row, $commonValues['Date']);
        $activeSheet->setCellValueByColumnAndRow(5, $row, $commonValues['Monnaie']);
    }

    /**
     * @param PHPExcel_Worksheet $activeSheet
     * @param int $row
     * @param array $commonValues
     * @param int $year
     * @param clients $clients
     */
    private function addLoansToRevenueQueryCSV(\PHPExcel_Worksheet &$activeSheet, &$row, $commonValues, $year, \clients $clients)
    {
        $sql = '
          SELECT
            c.id_client,
            SUM(lo.amount) AS montant
          FROM loans lo
            INNER JOIN
            (
              SELECT psh.id_project, MIN(psh.added) as first_added
              FROM projects_status_history psh
                INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
              WHERE ps.status = ' . \projects_status::REMBOURSEMENT . '
              GROUP BY psh.id_project
              HAVING YEAR(first_added) = ' . $year . '
            ) p ON p.id_project = lo.id_project
            INNER JOIN lenders_accounts la ON la.id_lender_account = lo.id_lender
            INNER JOIN clients c ON la.id_client_owner = c.id_client
            GROUP BY c.id_client';

        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $commonValues['lenderPattern'] = $clients->getLenderPattern($record['id_client']);
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '117');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format(($record['montant'] / 100), 2, ',', ''));
            $row += 1;
        }
    }

    /**
     * @param PHPExcel_Worksheet $activeSheet
     * @param int $clientId
     * @param int $year
     * @param array $zoneB040CountryIds
     * @param int $row
     * @param array $commonValues
     */
    private function addRepaymentBasedLinesToRevenuesQueryCSV(\PHPExcel_Worksheet &$activeSheet, $clientId, $year, $zoneB040CountryIds, &$row, $commonValues)
    {
        $sum66  = 0;
        $sum81  = 0;
        $sum82  = 0;
        $sum118 = 0;

        $sql = 'SELECT
                  la.id_lender_account,
                  ROUND(t_interets.montant/100, 2) AS net_interest,
                  IFNULL((SELECT SUM(ROUND(tax.amount / 100, 2)) FROM tax WHERE id_transaction = t_interets.id_transaction) , 0) AS tax_on_interest,
                  ROUND(t_capital.montant/100, 2) as capital,
                  t_interets.date_transaction,
                  c.type,
                  t_interets.id_echeancier
                FROM lenders_accounts la
                INNER JOIN clients c ON la.id_client_owner = c.id_client
                LEFT JOIN transactions t_interets ON c.id_client = t_interets.id_client AND t_interets.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
                LEFT JOIN transactions t_capital ON c.id_client = t_capital.id_client AND t_capital.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL . '
                WHERE YEAR(t_interets.date_transaction) = ' . $year . '
                  AND c.id_client =  ' . $clientId;

        $resultat = $this->bdd->query($sql);

        /** @var \transactions $transactions */
        $transactions = $this->loadData('transactions');

        while ($record = $this->bdd->fetch_array($resultat)) {
            if (\clients::TYPE_PERSON == $record['type'] || \clients::TYPE_PERSON_FOREIGNER == $record['type']) {
                /** @var \lenders_imposition_history $lenderImpositionHistory */
                $lenderImpositionHistory = $this->loadData('lenders_imposition_history');

                $foreigner       = false;
                $zoneB040Country = false;
                $situation       = $lenderImpositionHistory->getTaxationSituationAtDate($record['id_lender_account'], $record['date_transaction']);

                if (false === empty($situation)) {
                    $situation = $situation[0];
                    if (0 < $situation['resident_etranger']) {
                        $foreigner = true;
                        if (in_array($situation['id_pays'], $zoneB040CountryIds)) {
                            $zoneB040Country = true;
                        }
                    }
                }

                unset($situation);

                if (false === $foreigner) {
                    $sum66 += $record['net_interest'] + $record['tax_on_interest'];
                } else {
                    if (true === $zoneB040Country) {
                        $sum81 += $record['net_interest'];
                    }
                }

                if (true === $zoneB040Country) {
                    $sum82 += round(bcdiv($record['capital'], 100, 3), 2);
                }
            }
            $sum118 += round(bcdiv($record['capital'], 100, 3), 2);

            unset($record);
        }

        $recoveryPayments = $transactions->sum('id_client = ' . $clientId . ' AND type_transaction = ' . \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT, 'montant');
        $sum118 += round(bcdiv(bcdiv($recoveryPayments, 100, 3), 0.844, 5), 2);

        if ($sum66 > 0) {
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '66');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($sum66, 2, ',', ''));
            $row += 1;
        }

        if ($sum81 > 0) {
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '81');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($sum81, 2, ',', ''));
            $row += 1;
        }

        if ($sum82 > 0) {
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '82');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($sum82, 2, ',', ''));
            $row += 1;
        }

        if ($sum118 > 0) {
            $this->addCommonCellValuesToRevenueQueryCSV($activeSheet, $row, $commonValues);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '118');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($sum118, 2, ',', ''));
            $row += 1;
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

        $sql = 'SELECT id_project, id_bid, (SELECT id_client_owner FROM lenders_accounts la WHERE la.id_lender_account = b.id_lender_account) as id_client, added, (case status when 0 then "En cours" when 1 then "OK" when 2 then "KO" end) as Statut, ROUND((amount/100),0), REPLACE(rate,".",",") as rate FROM bids b';

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
                      ROUND(prelevements_obligatoires.amount / 100, 2) AS prelevements_obligatoires,
                      ROUND(retenues_source.amount / 100, 2) AS retenues_source,
                      ROUND(csg.amount / 100, 2) AS csg,
                      ROUND(prelevements_sociaux.amount / 100, 2) AS prelevements_sociaux,
                      ROUND(contributions_additionnelles.amount / 100, 2) AS contributions_additionnelles,
                      ROUND(prelevements_solidarite.amount / 100, 2) AS prelevements_solidarite,
                      ROUND(crds.amount / 100, 2) AS crds,
                      e.date_echeance,
                      e.date_echeance_reel,
                      e.date_echeance_emprunteur,
                      e.date_echeance_emprunteur_reel,
                      status
                  FROM echeanciers e
                      LEFT JOIN transactions t ON t.id_echeancier = e.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
                      LEFT JOIN tax prelevements_obligatoires ON prelevements_obligatoires.id_transaction = t.id_transaction AND prelevements_obligatoires.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX . '
                      LEFT JOIN tax retenues_source ON retenues_source.id_transaction = t.id_transaction AND retenues_source.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE. '
                      LEFT JOIN tax csg ON csg.id_transaction = t.id_transaction AND csg.id_tax_type = ' . \tax_type::TYPE_CSG . '
                      LEFT JOIN tax prelevements_sociaux ON prelevements_sociaux.id_transaction = t.id_transaction AND prelevements_sociaux.id_tax_type = ' . \tax_type::TYPE_SOCIAL_DEDUCTIONS . '
                      LEFT JOIN tax contributions_additionnelles ON contributions_additionnelles.id_transaction = t.id_transaction AND contributions_additionnelles.id_tax_type = ' . \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS . '
                      LEFT JOIN tax prelevements_solidarite ON prelevements_solidarite.id_transaction = t.id_transaction AND prelevements_solidarite.id_tax_type = ' . \tax_type::TYPE_SOLIDARITY_DEDUCTIONS . '
                      LEFT JOIN tax crds ON crds.id_transaction = t.id_transaction AND crds.id_tax_type = ' . \tax_type::TYPE_CRDS . '
                  WHERE e.id_project=' . $_POST['id_projet'];

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
}
