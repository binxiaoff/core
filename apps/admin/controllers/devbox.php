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
        $this->hideDecoration();
        $this->autoFireView = false;

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
        $this->hideDecoration();
        $this->autoFireView = false;

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
        $this->hideDecoration();
        $this->autoFireView = false;

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
        $this->hideDecoration();
        $this->autoFireView = false;

        //Encode: UTF-8, new line : LF
        //Source: http://www.insee.fr/fr/methodes/nomenclatures/cog/telechargement.asp?annee=2015
        if (($rHandle = fopen($this->path . '/protected/import/' . 'country.txt', 'r')) === false) {
            return;
        }

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
        $this->hideDecoration();
        $this->autoFireView = false;

        //Encode: UTF-8, new line : LF
        if (($rHandle = fopen($this->path . '/protected/import/' . 'naissance.csv', 'r')) === false) {
            return;
        }

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
        $this->hideDecoration();
        $this->autoFireView = false;

        //Encode: UTF-8, new line : LF
        if (($rHandle = fopen($this->path . '/protected/import/' . 'fiscal_city.csv', 'r')) === false) {
            return;
        }


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
}
