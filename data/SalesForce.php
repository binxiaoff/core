<?php

namespace data;

use Unilend\core\Bootstrap;

class SalesForce
{

    /**
     * constant to specify path for extract
     */
    const PATH_EXTRACT = 'protected/salesforce/';

    /**
     * @object $oBootstrap Unilend\core\Boostrap();
     */
    private $oBoostrap;

    /**
     * @object $oDatabase core\bdd();
     */
    private $oDatabase;

    /**
     * @object $oLogger Monolog\Logger()
     */
    private $oLogger;

    /**
     * @param string $sConfig Configuration of Unilend Site from file config.php
     */
    public function __construct($sConfig)
    {
        $this->setBootstrap($sConfig);
        $this->oBoostrap->setConfig($sConfig)
            ->setDatabase();
        $this->oDatabase = $this->oBoostrap->getDatabase();
        $this->oLogger = $this->oBoostrap->setLogger('SalesForce', 'salesforce.log')->getLogger();
    }

    public function setBootstrap($sConfig)
    {
        $this->oBoostrap = Bootstrap::getInstance($sConfig);
    }

    public function extractCompanies()
    {
        $sQuery = "SELECT
                    co.id_company AS 'Id Company',
                    REPLACE(co.siren,'\t','') AS 'Siren',
                    CONVERT(CAST(REPLACE(co.name,',',';') as BINARY) USING utf8) AS 'Raison Sociale',
                    CONVERT(CAST(REPLACE(co.adresse1,',',';') as BINARY) USING utf8) AS 'Adresse 1',
                    REPLACE(co.zip,',',';') AS 'CP',
                    CONVERT(CAST(REPLACE(co.city,',',';') as BINARY) USING utf8) AS 'Ville',
                    acountry.fr AS 'Pays',
                    REPLACE(co.email_facture,',',';') AS 'Email de facturation',
                    co.id_client_owner AS 'Id Client'
                  FROM
                    companies co
                  LEFT JOIN
                    pays_v2 acountry ON (co.id_pays = acountry.id_pays)";

        $this->tryIt($sQuery, 'companies');
    }

    public function extractBorrowers()
    {
        $sQuery = "SELECT
                    c.id_client as 'Id Client',
                    c.id_langue as 'Langue',
                    CONVERT(CAST(REPLACE(c.civilite,',',';') as BINARY) USING utf8) as 'Civilite',
                    CONVERT(CAST(REPLACE(c.nom,',',';') as BINARY) USING utf8) as 'Nom',
                    CONVERT(CAST(REPLACE(c.nom_usage,',',';') as BINARY) USING utf8) as 'Nom d\'usage',
                    CONVERT(CAST(REPLACE(c.prenom,',',';') as BINARY) USING utf8) as 'Prenom',
                    CONVERT(CAST(REPLACE(c.fonction,',',';') as BINARY) USING utf8) as 'Fonction',
                    CASE c.naissance
                      WHEN '0000-00-00' then ''
                      ELSE c.naissance
                    END as 'Date de naissance',
                    CONVERT(CAST(REPLACE(ville_naissance,',',';') as BINARY) USING utf8) as 'Ville de naissance',
                    ccountry.fr as 'Pays de Naissance',
                    nv2.fr_f as 'Nationalite',
                    REPLACE(c.telephone,'\t','') as 'Telephone',
                    c.mobile as 'Mobile',
                    REPLACE(c.email,',',';') as 'Email',
                    c.etape_inscription_preteur as 'Etape inscription preteur',
                    CASE c.type
                        WHEN 1 THEN 'Physique'
                        WHEN 2 THEN 'Morale'
                        WHEN 3 THEN 'Physique'
                        ELSE 'Morale'
                    END as 'Type de contact',
                    CASE c.status
                        WHEN 1 THEN 'oui'
                        ELSE 'non'
                    END as 'Valide',
                    CASE c.added
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.added
                    END as 'date d\'inscription',
                    CASE c.updated
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.updated
                    END as 'Date de derniere mise a jour',
                    CASE c.lastlogin
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.lastlogin
                    END as 'Date de dernier login',
                    CONVERT(CAST(REPLACE(ca.adresse1,',',';') as BINARY) USING utf8) as 'Adresse 1',
                    CONVERT(CAST(REPLACE(ca.adresse2,',',';') as BINARY) USING utf8) as 'Adresse 2',
                    CONVERT(CAST(REPLACE(ca.adresse3,',',';') as BINARY) USING utf8) as 'Adresse 3',
                    CONVERT(CAST(REPLACE(ca.cp,',',';') as BINARY) USING utf8) as 'CP',
                    CONVERT(CAST(REPLACE(ca.ville,',',';') as BINARY) USING utf8) as 'Ville',
                    acountry.fr as 'Pays'
                  FROM
                    clients c
                    LEFT JOIN clients_adresses ca on (c.id_client = ca.id_client)
                    LEFT JOIN pays_v2 ccountry on (
                        c.id_pays_naissance = ccountry.id_pays
                    )
                    LEFT JOIN pays_v2 acountry on (ca.id_pays = acountry.id_pays)
                    LEFT JOIN nationalites_v2 nv2 on (
                        c.id_nationalite = nv2.id_nationalite
                    )
                    LEFT JOIN companies co on c.id_client = co.id_client_owner
                WHERE
                    c.status_pre_emp = 2
                    group by
                        c.id_client";

        $this->tryIt($sQuery, 'emprunteurs');
    }

    public function extractProjects()
    {
        $sQuery = "SELECT
                        p.id_project AS 'Id Projet',
                        CONVERT(CAST(REPLACE(cl.source,',',';') as BINARY) USING utf8) AS 'Source 1',
                        CONVERT(CAST(REPLACE(cl.source2,',',';') as BINARY) USING utf8) AS 'Source 2',
                        p.id_company AS 'Id Company',
                        p.amount AS 'Amount',
                        p.period AS 'Nb de mois',
                        p.date_publication AS 'Date de publication',
                        p.date_retrait AS 'Date retrait',
                        CASE p.added
                          WHEN '0000-00-00 00:00:00' then ''
                          ELSE p.added
                        END AS 'Date d\'ajout',
                        CASE p.updated
                          WHEN '0000-00-00 00:00:00' then ''
                          ELSE p.updated
                        END AS 'Date de mise a jour',
                        p.status AS 'Status',
                        pn.note AS 'Note',
                        CONVERT(CAST(REPLACE(co.name,',',';') as BINARY) USING utf8) AS 'Nom_Societe',
                        CONVERT(CAST(REPLACE(co.forme,',',';') as BINARY) USING utf8) AS 'Forme',
                        REPLACE(co.siren,'\t','') AS 'Siren',
                        CONVERT(CAST(REPLACE(co.adresse1,',',';') as BINARY) USING utf8) as 'Adresse 1',
                        CONVERT(CAST(REPLACE(co.adresse2,',',';') as BINARY) USING utf8) as 'Adresse 2',
                        REPLACE(co.zip,',',';') AS 'CP',
                        CONVERT(CAST(REPLACE(co.city,',',';') as BINARY) USING utf8) AS 'Ville',
                        co.id_pays AS 'Id Pays',
                        REPLACE(co.phone,'\t','') AS 'Telephone',
                        CONVERT(CAST(co.status_client as BINARY) USING utf8) AS 'Status Client',
                        CASE co.added
                          WHEN '0000-00-00 00:00:00' then ''
                          ELSE co.added
                        END AS 'Date d\'ajout',
                        CASE co.updated
                          WHEN '0000-00-00 00:00:00' then ''
                          ELSE co.updated
                        END AS 'Date de mise a jour',
                        co.id_client_owner AS 'Id Client'
                    FROM
                        projects p
                            LEFT JOIN
                        companies co ON (p.id_company = co.id_company)
                            LEFT JOIN
                        clients cl ON (cl.id_client = co.id_client_owner)
                            LEFT JOIN
                        projects_notes pn ON (p.id_project = pn.id_project)";

        $this->tryIt($sQuery, 'projects', true);
    }

    public function extractLenders()
    {
        $sQuery = "SELECT
                    c.id_client as 'Id Client',
                    la.id_lender_account as 'Id Preteur',
                    c.id_langue as 'Langue',
                    CONVERT(CAST(REPLACE(c.source,',',';') as BINARY) USING utf8) as 'Source 1',
                    CONVERT(CAST(REPLACE(c.source2,',',';') as BINARY) USING utf8) as 'Source 2',
                    CONVERT(CAST(REPLACE(c.source3,',',';') as BINARY) USING utf8) as 'Source 3',
                    CONVERT(CAST(REPLACE(c.civilite,',',';') as BINARY) USING utf8) as 'Civilite',
                    CONVERT(CAST(REPLACE(c.nom,',',';') as BINARY) USING utf8) as 'Nom',
                    CONVERT(CAST(REPLACE(c.nom_usage,',',';') as BINARY) USING utf8) as 'Nom d\'usage',
                    CONVERT(CAST(REPLACE(c.prenom,',',';') as BINARY) USING utf8) as 'Prenom',
                    CONVERT(CAST(REPLACE(c.fonction,',',';') as BINARY) USING utf8) as 'Fonction',
                    CASE c.naissance
                    	WHEN '0000-00-00' then ''
                    	ELSE c.naissance
                    END as 'Date de naissance',
                    CONVERT(CAST(REPLACE(ville_naissance,',',';') as BINARY) USING utf8) as 'Ville de naissance',
                    ccountry.fr as 'Pays de Naissance',
                    nv2.fr_f as 'Nationalite',
                    REPLACE(c.telephone,'\t','') as 'Telephone',
                    REPLACE(c.mobile,',',';') as 'Mobile',
                    REPLACE(c.email,',',';') as 'Email',
                    c.etape_inscription_preteur as 'Etape inscription preteur',
                    CASE c.type
                        WHEN 1 THEN 'Physique'
                        WHEN 2 THEN 'Morale'
                        WHEN 3 THEN 'Physique'
                        ELSE 'Morale'
                    END as 'Type de contact',
                    CASE cs.status
                        WHEN 6 THEN 'oui'
                        ELSE 'non'
                    END as 'Valide',
                    CASE c.status_pre_emp
                        WHEN 1
                            THEN (SELECT CONVERT(CAST(cs.label as BINARY) USING utf8) FROM clients_status_history cshs1
                                  inner join clients_status cs on cshs1.id_client_status =cs.id_client_status
                                  WHERE cshs1.id_client=c.id_client
                                  ORDER BY cshs1.added DESC LIMIT 1)
                         ELSE 'N/A' END as 'Status_Completude',
                    CASE c.added
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.added
                    END as 'date d\'inscription',
                    CASE c.updated
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.updated
                    END as 'Date de derniere mise a jour',
                    CASE c.lastlogin
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.lastlogin
                    END as 'Date de dernier login',
                    cs.id_client_status as 'Statut de validation',
                    CONVERT(CAST(status_inscription_preteur as BINARY) USING utf8) as 'Status d\inscription',
                    count(
                        distinct(l.id_project)
                    ) as 'Nb de prets valides',
                    CONVERT(CAST(REPLACE(ca.adresse1,',',';') as BINARY) USING utf8) as 'Adresse 1',
                    CONVERT(CAST(REPLACE(ca.adresse2,',',';') as BINARY) USING utf8) as 'Adresse 2',
                    CONVERT(CAST(REPLACE(ca.adresse3,',',';') as BINARY) USING utf8) as 'Adresse 3',
                    CONVERT(CAST(REPLACE(ca.cp,',',';') as BINARY) USING utf8) as 'CP',
                    CONVERT(CAST(REPLACE(ca.ville,',',';') as BINARY) USING utf8) as 'Ville',
                    acountry.fr as 'Pays',
                    SUM(l.amount)/100 as 'Total Pret Eur',
                    '0012400000F6xvT' as 'Sfcompte'
                  FROM
                    clients c
                    LEFT JOIN clients_adresses ca on (c.id_client = ca.id_client)
                    LEFT JOIN pays_v2 ccountry on (
                        c.id_pays_naissance = ccountry.id_pays
                    )
                    LEFT JOIN pays_v2 acountry on (ca.id_pays = acountry.id_pays)
                    LEFT JOIN nationalites_v2 nv2 on (
                        c.id_nationalite = nv2.id_nationalite
                    )
                    LEFT JOIN lenders_accounts la on (la.id_client_owner = c.id_client)
                    LEFT JOIN loans l on (
                        la.id_lender_account = l.id_lender
                        and l.status = 0
                    )
                    LEFT JOIN clients_status cs on c.status = cs.id_client_status
                  WHERE
                    (c.status_pre_emp = 1 or c.status_pre_emp = 2)
                  GROUP BY
                    c.id_client";

        $this->tryIt($sQuery, 'preteurs');
        $this->extractProspects();
    }

    /**
     * Query sql for prospects and treatment specific because prospect haven't same number columns
     */
    public function extractProspects()
    {
        $sQuery = "SELECT id_prospect,
                    id_langue,
                    CONVERT(CAST(source as BINARY) USING utf8) as 'source',
                    CONVERT(CAST(source2 as BINARY) USING utf8) as 'source2',
                    CONVERT(CAST(source3 as BINARY) USING utf8) as 'source3',
                    CONVERT(CAST(nom as BINARY) USING utf8) as 'nom',
                    CONVERT(CAST(prenom as BINARY) USING utf8) as 'prenom',
                    email,
                    CASE added
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE added
                    END as 'added',
                    CASE updated
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE updated
                    END as 'updated'
                  FROM prospects p";

        try {
            if ($rSql = $this->oDatabase->query($sQuery)) {
                $iTimeStartCsv = microtime(true);
                $oCsvFile = fopen(Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT . 'preteurs.csv', 'a');
                $sNom = $sPrenom = $sEmail = '';
                while ($aRow = $this->oDatabase->fetch_assoc($rSql)) {
                    if ($aRow['nom'] != $sNom && $aRow['prenom'] != $sPrenom && $aRow['email'] != $sEmail) {
                        $aCsvProspect = array($aRow['id_prospect'],
                            '',
                            $aRow['id_langue'],
                            $aRow['source'],
                            $aRow['source2'],
                            $aRow['source3'],
                            '',
                            $aRow['nom'],
                            '',
                            $aRow['prenom'],
                            '', '', '', '', '', '', '',
                            $aRow['email'],
                            '', '', 'Prospect',
                            $aRow['added'],
                            $aRow['updated'],
                            '', '', '', '', '', '', '', '', '', '', 0,
                            '0012400000F6xvT');
                        fputs($oCsvFile, '"' . implode('", "', $aCsvProspect) . '"' . "\n");
                    }
                    $sNom = $aRow['nom'];
                    $sPrenom = $aRow['prenom'];
                    $sEmail = $aRow['email'];
                }
                fclose($oCsvFile);
                $iTimeEndCsv = microtime(true) - $iTimeStartCsv;
                $this->oLogger->addInfo('Generation of csv prospects in ' . round($iTimeEndCsv, 2),
                    array(__FILE__ . ' on line ' . __LINE__));

                $this->oDatabase->free_result($rSql);
            } else {
                throw new \Exception(mysql_error($this->oDatabase->connect_id));
            }
        } catch (\Exception $oException) {
            $this->oLogger->addError('Error on query prospects : ' . $oException->getMessage());
        }
    }

    /**
     * @param ressource $rSql ressource of query sql
     * @param string $sNameFile name of csv file to write
     * @param bool|false $bSpecialTreatments boolean for know if a treatment specific exist
     */
    private function createFileFromQuery($rSql, $sNameFile, $bSpecialTreatments = false)
    {
        $aSearchCharacter = array("\r\n", "\n", "\t");
        $aReplaceCharacter = array('/', '/', '');

        if (true === $this->createExtractDir()) {
            $iTimeStartCsv = microtime(true);
            $sNameFile .= (!preg_match('/(\.csv)$/i', $sNameFile)) ? '.csv' : '';
            $oCsvFile = fopen(Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT . $sNameFile, 'w');
            $iCountLine = 0;
            while ($aRow = $this->oDatabase->fetch_assoc($rSql)) {
                switch ($sNameFile) {
                    case 'projects':
                        $aRow['Nom_Societe'] = str_replace($aSearchCharacter, $aReplaceCharacter, $aRow['Nom_Societe']);
                        break;
                    case 'preteurs':
                        $aRow['Valide'] = ('Valide' == $aRow['Status_Completude']) ? 'Oui' : 'Non';
                        break;
                }
                if (0 === $iCountLine) fputs($oCsvFile, '"' . implode('", "', array_keys($aRow)) . '"' . "\n");
                fputs($oCsvFile, '"' . implode('", "', $aRow) . '"' . "\n");
                $iCountLine++;
            }
            fclose($oCsvFile);

            $iTimeEndCsv = microtime(true) - $iTimeStartCsv;
            $this->oLogger->addInfo('Generation of csv ' . $sNameFile . ' in ' . round($iTimeEndCsv, 2),
                array(__FILE__ . ' on line ' . __LINE__));
        }
    }

    private function createExtractDir()
    {
        if (false === is_dir(Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT)) {
            if (false === mkdir(Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT, 0777, true)) {
                $this->oLogger->addError('Error on create dir ' .
                    Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT,
                    array(__FILE__ . ' on line ' . __LINE__));

                return false;
            }
        }

        return true;
    }

    /**
     * @param string $sQuery sql query
     * @param string $sNameFile File Name to generate
     * @param bool|false $bSpecialTreatments true if treat html special content
     */
    private function tryIt($sQuery, $sNameFile, $bSpecialTreatments = false)
    {
        try {
            if ($rSql = $this->oDatabase->query($sQuery)) {
                $this->createFileFromQuery($rSql, $sNameFile . '.csv', $bSpecialTreatments);
                $this->oDatabase->free_result($rSql);
            } else {
                throw new \Exception(mysql_error($this->oDatabase->connect_id));
            }
        } catch (\Exception $oException) {
            $this->oLogger->addError('Error on query ' . $sNameFile . ' : ' . $oException->getMessage());
        }
    }
}
