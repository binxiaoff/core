<?php

// Controller de developpement, aucun accès client autorisé, fonctions en BETA
class devboxController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
    }

    public function _importINSEEPostalCodes()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Encode: UTF-8, new line : LF
        //Source: https://www.data.gouv.fr/fr/datasets/base-officielle-des-codes-postaux/
        if (($rHandle = fopen($this->path . '/protected/import/' . 'codes_postaux.csv', 'r')) === false) {
            return;
        }

        while (($aRow = fgetcsv($rHandle, 0, ';')) !== false) {
            $departement    = substr($aRow[0], 0, 2) !== '97' ? substr($aRow[0], 0, 2) : substr($aRow[0], 0, 3);

            $sql = 'INSERT INTO villes (ville, insee, cp, num_departement, active, added, updated)
                    VALUES("' . $aRow[1] . '", "' . $aRow[0] . '", "' . $aRow[2] . '", "' . $departement . '", 1, NOW(), NOW())';
            $this->bdd->query($sql);
            unset($aRow);
        }

        fclose($rHandle);
    }

    public function _importINSEECities()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Encode: UTF-8, new line : LF
        //Source: http://www.insee.fr/fr/methodes/nomenclatures/cog/telechargement.asp?annee=2015
        if (($rHandle = fopen($this->path . '/protected/import/' . 'insee.txt', 'r')) === false) {
            return;
        }

        /** @var villes $oVille */
        $oVille = $this->loadData('villes');

        $i = 0;
        while (($aRow = fgetcsv($rHandle, 0, "\t")) !== false) {
            $sInsee = $oVille->generateCodeInsee($aRow[5], $aRow[6]);
            if (in_array($aRow[0], array(3, 4))) {
                if ($oVille->exist($sInsee, 'insee')) {
                    $this->bdd->query('UPDATE `villes` SET active = 0, ville = "' . $aRow[13] . '" WHERE insee = "' . $sInsee . '"');
                } else {
                    $departement = str_pad($aRow[5], 2, 0, STR_PAD_LEFT);
                    $sql = '
                        INSERT INTO `villes`(`ville`,`insee`,`cp`,`num_departement`,`active`,`added`,`updated`)
                        VALUES("' . $aRow[13] . '","' . $sInsee . '","","' . $departement . '", 0,NOW(),NOW())';
                    $this->bdd->query($sql);
                }
            } else {
                $this->bdd->query('UPDATE `villes` SET ville = "' . $aRow[13] . '" WHERE insee = "' . $sInsee . '"');
            }
            unset($aRow);
            $i++;
            echo 'done: ' . $i . '/39806' . PHP_EOL;
        }

        fclose($rHandle);
    }

    public function _importINSEEOldCities()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Encode: UTF-8, new line : LF
        //Source: http://www.insee.fr/fr/methodes/nomenclatures/cog/telechargement.asp?annee=2015
        if (($rHandle = fopen($this->path . '/protected/import/' . 'insee.txt', 'r')) === false) {
            return;
        }

        /** @var villes $oVille */
        $oVille = $this->loadData('villes');

        $i = 0;
        while (($aRow = fgetcsv($rHandle, 0, "\t")) !== false) {
            $sInsee = $oVille->generateCodeInsee($aRow[5], $aRow[6]);
            if (in_array($aRow[0], array(2, 9))) {
                if ($oVille->exist($sInsee, 'insee')) {
                    $this->bdd->query('UPDATE `villes` SET active = 0, ville = "' . $aRow[13] . '" WHERE insee = "' . $sInsee . '"');
                } else {
                    $departement = str_pad($aRow[5], 2, 0, STR_PAD_LEFT);
                    $sql = '
                        INSERT INTO `villes`(`ville`,`insee`,`cp`,`num_departement`,`active`,`added`,`updated`)
                        VALUES("' . $aRow[13] . '","' . $sInsee . '","","' . $departement . '", 0,NOW(),NOW())';
                    $this->bdd->query($sql);
                }
                echo 'done: ' . $i . PHP_EOL;
                $i++;
            }
            unset($aRow);
        }
        fclose($rHandle);
    }

    public function _import_pays_insee()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Encode: UTF-8, new line : LF
        //Source: http://www.insee.fr/fr/methodes/nomenclatures/cog/telechargement.asp?annee=2015
        if (($rHandle = fopen($this->path . '/protected/import/' . 'country.txt', 'r')) === false) {
            return;
        }

        /** @var insee_pays $oPays */
        $oPays = $this->loadData('insee_pays');

        while (($aRow = fgetcsv($rHandle, 0, "\t")) !== false) {
            $sql = 'INSERT INTO insee_pays (CODEISO2, COG, ACTUAL, CAPAY, CRPAY, ANI, LIBCOG, LIBENR, ANCNOM)
                    VALUES("' . $aRow[8] . '","' . $aRow[0] . '","' . $aRow[1] . '","' . $aRow[2] . '","' . $aRow[3] . '","' . $aRow[4] . '","' . $aRow[5] . '","' . $aRow[6] . '","' . $aRow[7] . '")';
            $this->bdd->query($sql);
            unset($aRow);
        }

        fclose($rHandle);
    }

    public function _importBirthCity()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Encode: UTF-8, new line : LF
        if (($rHandle = fopen($this->path . '/protected/import/' . 'naissance.csv', 'r')) === false) {
            return;
        }

        /** @var clients $oClient */
        $oClient = $this->loadData('clients');

        while (($aRow = fgetcsv($rHandle, 0, ';')) !== false) {
            $aRow = array_map('trim', $aRow);
            $aRow = array_map(array($this->bdd, 'escape_string'), $aRow);

            preg_match('/^\d+/s', $aRow[0], $matches);
            if (false === isset($matches[0])) {
                continue;
            }
            $iClientId = (int) $matches[0];
            if ('99' === substr($aRow[1], 0, 2)) {
                $sql = "UPDATE clients set insee_birth = '{$aRow[1]}' WHERE id_client = {$iClientId}";
            } else {
                $sql = "UPDATE clients set insee_birth = '{$aRow[1]}', ville_naissance = '{$aRow[2]}' WHERE id_client = {$iClientId}";
            }
            $this->bdd->query($sql);
            unset($aRow);
        }

        fclose($rHandle);
        echo 'done';
    }

    public function _importFiscalCity()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Encode: UTF-8, new line : LF
        if (($rHandle = fopen($this->path . '/protected/import/' . 'fiscal_city.csv', 'r')) === false) {
            return;
        }

        /** @var clients_adresses $oClient */
        $oClient = $this->loadData('clients_adresses');

        while (($aRow = fgetcsv($rHandle, 0, ';')) !== false) {
            $aRow = array_map('trim', $aRow);
            $aRow = array_map(array($this->bdd, 'escape_string'), $aRow);

            preg_match('/^\d+/s', $aRow[0], $matches);
            if (false === isset($matches[0])) {
                continue;
            }
            $iClientId = (int) $matches[0];

            if (empty($aRow[1])) { // company
                if ('99' === substr($aRow[2], 0, 2)) {
                    continue;
                }
                $sql = "UPDATE companies SET zip = '{$aRow[2]}', city = '{$aRow[3]}' WHERE id_client_owner = {$iClientId}";
            } else {
                $sFieldPostCode = 'cp';
                $sFieldCity     = 'ville';
                $sFieldCountry  = 'id_pays';

                $sql = "SELECT meme_adresse_fiscal FROM clients_adresses WHERE id_client = {$iClientId}";
                $oQuery = $this->bdd->query($sql);
                $aClient = $this->bdd->fetch_array($oQuery);

                if($aClient['meme_adresse_fiscal'] === '0') {
                    $sFieldPostCode = 'cp_fiscal';
                    $sFieldCity     = 'ville_fiscal';
                    $sFieldCountry  = 'id_pays_fiscal';
                }

                if ('99' === substr($aRow[2], 0, 2)) {
                    $sql = "SELECT id_pays_fiscal FROM clients_adresses WHERE id_client = {$iClientId}";
                    $oQuery = $this->bdd->query($sql);
                    $aClient = $this->bdd->fetch_array($oQuery);

                    if(isset($aClient['id_pays_fiscal']) && false === empty($aClient['id_pays_fiscal']) &&  $aClient['id_pays_fiscal'] <= 1) {
                        $sql = "SELECT p.id_pays FROM pays_v2 p INNER JOIN insee_pays ip ON ip.CODEISO2 = p.iso WHERE ip.COG = {$aRow[2]}";
                        $oQuery = $this->bdd->query($sql);
                        $aClient = $this->bdd->fetch_array($oQuery);

                        if(isset($aClient[id_pays]) && false === empty($aClient[id_pays])) {
                            $sql = "UPDATE clients_adresses SET $sFieldCountry = '{$aClient[id_pays]}' WHERE id_client = {$iClientId}";
                        }
                    }
                } else {
                    $sql = "UPDATE clients_adresses SET $sFieldPostCode = '{$aRow[2]}', $sFieldCity = '{$aRow[3]}' WHERE id_client = {$iClientId}";
                }
            }
            $this->bdd->query($sql);
            unset($aRow);
        }

        fclose($rHandle);
        echo 'done';
    }

    public function _importResidenceOverseas()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Encode: UTF-8, new line : LF
        if (($rHandle = fopen($this->path . '/protected/import/' . 'etranger.csv', 'r')) === false) {
            return;
        }

        /** @var lenders_imposition_history $oClient */
        $oClient = $this->loadData('lenders_imposition_history');

        while (($aRow = fgetcsv($rHandle, 0, ';')) !== false) {
            $aRow = array_map('trim', $aRow);
            $aRow = array_map(array($this->bdd, 'escape_string'), $aRow);

            preg_match('/^\d+/s', $aRow[0], $matches);
            if (false === isset($matches[0])) {
                continue;
            }
            $iClientId = (int) $matches[0];
            $sql = "UPDATE `lenders_imposition_history`
                    SET `id_pays`= (SELECT p.id_pays FROM pays_v2 p WHERE p.iso = '{$aRow[1]}')
                    WHERE `id_lenders_imposition_history` = (
                    SELECT t.id_lenders_imposition_history FROM (
                        SELECT lih.id_lenders_imposition_history
                            FROM `lenders_imposition_history` lih
                            INNER JOIN lenders_accounts la ON la.id_lender_account = lih.id_lender
                            WHERE la.id_client_owner = $iClientId
                            ORDER BY lih.added DESC LIMIT 1
                        ) t
                    )";
            $this->bdd->query($sql);
            unset($aRow);
        }

        fclose($rHandle);
        echo 'done';
    }

    public function _addWelcomeOffer()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Encode: UTF-8, new line : LF
        if (($rHandle = fopen($this->path . '/protected/import/' . 'welcome.csv', 'r')) === false) {
            return;
        }

        /** @var offres_bienvenues_details $oOffre */
        $oOffre = $this->loadData('offres_bienvenues_details');

        while (($aRow = fgetcsv($rHandle, 0, ',')) !== false) {
            $iClientId = $aRow[0];
            if (false === $oOffre->exist($iClientId, 'id_client')) {
                $sql = "INSERT INTO `offres_bienvenues_details` (`id_offre_bienvenue`, `motif`, `id_client`, `id_bid`, `id_bid_remb`, `montant`, `status`, `type`, `added`, `updated`)
                        VALUES (1, 'Offre de bienvenue', $iClientId, 0, 0, 2000, 0, 0, now(), now())";
                $this->bdd->query($sql);
            }
        }
        fclose($rHandle);
        echo 'done';
    }

    public function _importRecoveryRepayment()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        //Encode: UTF-8, new line : LF
        if (($rHandle = fopen($this->path . '/protected/import/' . 'recouvrement.csv', 'r')) === false) {
            return;
        }

        /** @var \transactions $oTransaction */
        $oTransaction = $this->loadData('transactions');
        /** @var \wallets_lines $oWalletLine */
        $oWalletLine = $this->loadData('wallets_lines');
        /** @var \lenders_accounts $oLender */
        $oLender = $this->loadData('lenders_accounts');

        while (($aRow = fgetcsv($rHandle, 0, ';')) !== false) {
            $oTransaction->unsetData();
            $oWalletLine->unsetData();

            $sClientId  = $aRow[0];
            $sProjectId = $aRow[1];
            $fAmount    = str_replace(',', '.', $aRow[2]) * 100;

            if ($oLender->get($sClientId, 'id_client_owner')) {
                $oTransaction->id_project       = $sProjectId;
                $oTransaction->id_client        = $sClientId;
                $oTransaction->montant          = $fAmount;
                $oTransaction->id_langue        = 'fr';
                $oTransaction->date_transaction = date('Y-m-d H:i:s');
                $oTransaction->status           = transactions::PAYMENT_STATUS_OK;
                $oTransaction->etat             = transactions::STATUS_VALID;
                $oTransaction->ip_client        = $_SERVER['REMOTE_ADDR'];
                $oTransaction->type_transaction = transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT;
                $oTransaction->transaction      = transactions::VIRTUAL;
                $oTransaction->create();

                $oWalletLine->id_lender                = $oLender->id_lender_account;
                $oWalletLine->type_financial_operation = wallets_lines::TYPE_REPAYMENT;
                $oWalletLine->id_transaction           = $oTransaction->id_transaction;
                $oWalletLine->status                   = wallets_lines::STATUS_VALID;
                $oWalletLine->type                     = wallets_lines::VIRTUAL;
                $oWalletLine->amount                   = $fAmount;
                $oWalletLine->create();
            }
        }
        fclose($rHandle);
        echo 'done';
    }

    public function _countInsert()
    {
        $this->autoFireView   = false;
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
        $aTables = array('accept_cookies','acceptations_legal_docs');
        foreach ($aTables as $table) {
            $query = $this->bdd->query('
            SELECT count(*) AS inserted
                FROM ' . $table . '
                WHERE unix_timestamp(added) BETWEEN unix_timestamp()-1800 AND unix_timestamp()
            ');
            if ($query) {
                $result = $this->bdd->fetch_assoc($query);
                echo $table . ' : ' . $result['inserted'] . '<br>';
            }
        }
    }
}
