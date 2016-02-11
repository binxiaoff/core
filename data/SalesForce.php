<?php

namespace Unilend\data;

use Unilend\core\Bootstrap;
use Unilend\librairies\ULogger;

class SalesForce
{

    /**
     * constant to specify path for extract
     */
    const PATH_EXTRACT = 'dataloader/extract/';

    /**
     * constant to specify path dataloader conf
     */
    const PATH_DATALOADER_CONF = 'dataloader/conf/';

    /**
     * constant to specify path for status log csv send by the dataloader
     */
    const PATH_SUCCESS_LOG = 'dataloader/status';

    /**
     * constant to specify dataloader version
     */
    const DATALOADER_VERSION = '26.0.0';

    /**
     * constant to specify the name of prospect's file for check if become client or not
     */
    const FILE_PROSPECTS_ONLY = 'tempProspect.csv';

    /**
     * @var Boostrap
     */
    private $oBoostrap;

    /**
     * @var \bdd
     */
    private $oDatabase;

    /**
     * @var ULogger
     */
    private $oLogger;

    /**
     * @var array with character to replace
     */
    public $aSearchCharacter;

    /**
     * @var array with character of replacement
     */
    public $aReplaceCharacter;

    /**
     * @var array with types authorized for dataloader
     */
    private static $aTypeDataloader;

    /**
     * @param object $oBootstrap Unilend\core\Bootstrap
     */
    public function __construct(Bootstrap $oBootstrap)
    {
        $this->oBoostrap = $oBootstrap;
        $this->oBoostrap->setDatabase();
        $this->oDatabase = $this->oBoostrap->getDatabase();
        $this->oLogger   = $this->oBoostrap->setLogger('SalesForce', 'salesforce.log')->getLogger();
        $this->setSearchAndReplace()
             ->setTypeDataloader()
             ->deleteStatusLog();
    }

    public function setTypeDataloader()
    {
        self::$aTypeDataloader = array('preteurs', 'emprunteurs', 'companies', 'projects');

        return $this;
    }

    public function setSearchAndReplace()
    {
        $this->aSearchCharacter  = array("\r\n", "\n", "\t", "'", ';', '"');
        $this->aReplaceCharacter = array('/', '/', '', '', '', '');

        return $this;
    }

    public function extractCompanies()
    {
        $sQuery = "SELECT
                    co.id_company AS 'IDCompany',
                    REPLACE(co.siren,'\t','') AS 'Siren',
                    CASE REPLACE(co.name,',','')
                      WHEN '' THEN 'A renseigner'
                      ELSE REPLACE(co.name,',','')
                    END AS 'RaisonSociale',
                    REPLACE(co.adresse1,',','') AS 'Adresse1',
                    REPLACE(co.zip,',','') AS 'CP',
                    REPLACE(co.city,',','') AS 'Ville',
                    acountry.fr AS 'Pays',
                    REPLACE(co.email_facture,',','') AS 'EmailFacturation',
                    co.id_client_owner AS 'IDClient',
                    co.forme as 'FormeSociale',
                    '012240000002G4U' as 'Sfcompte'
                  FROM
                    companies co
                  LEFT JOIN
                    pays_v2 acountry ON (co.id_pays = acountry.id_pays)";

        $this->tryIt($sQuery, 'companies');
    }

    public function extractBorrowers()
    {
        $sQuery = "SELECT
                      c.id_client as 'IDClient',
                      c.id_client as 'IDClient_2',
                      c.id_langue as 'Langue',
                      REPLACE(c.civilite,',','') as 'Civilite',
                      REPLACE(c.nom,',','') as 'Nom',
                      REPLACE(c.nom_usage,',','') as 'Nom_usage',
                      REPLACE(c.prenom,',','') as 'Prenom',
                      CONVERT(REPLACE(c.fonction,',','') USING utf8) as 'Fonction',
                      CASE c.naissance
                      WHEN '0000-00-00' then '2001-01-01'
                      ELSE
                        CASE SUBSTRING(c.naissance,1,1)
                        WHEN '0' then '2001-01-01'
                        ELSE c.naissance
                        END
                      END as 'DateNaissance',
                      REPLACE(ville_naissance,',','') as 'VilleNaissance',
                      ccountry.fr as 'PaysNaissance',
                      nv2.fr_f as 'Nationalite',
                      REPLACE(c.telephone,'\t','') as 'Telephone',
                      c.mobile as 'Mobile',
                      REPLACE(c.email,',','') as 'Email',
                      c.etape_inscription_preteur as 'EtapeInscriptionPreteur',
                      CASE c.type
                      WHEN 1 THEN 'Physique'
                      WHEN 2 THEN 'Morale'
                      WHEN 3 THEN 'Physique'
                      ELSE 'Morale'
                      END as 'TypeContact',
                      CASE c.status
                      WHEN 1 THEN 'oui'
                      ELSE 'non'
                      END as 'Valide',
                      CASE c.added
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.added
                      END as 'date_inscription',
                      CASE c.updated
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.updated
                      END as 'DateMiseJour',
                      CASE c.lastlogin
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.lastlogin
                      END as 'DateDernierLogin',
                      REPLACE(ca.adresse1,',','') as 'Adresse1',
                      REPLACE(ca.adresse2,',','') as 'Adresse2',
                      REPLACE(ca.adresse3,',','') as 'Adresse3',
                      REPLACE(ca.cp,',','') as 'CP',
                      REPLACE(ca.ville,',','') as 'Ville',
                      acountry.fr as 'Pays',
                      '012240000002G4e' as 'Sfcompte'
                    FROM
                      clients c
                      INNER JOIN companies co on c.id_client = co.id_client_owner
                      INNER JOIN projects p ON p.id_company = co.id_company
                      LEFT JOIN clients_adresses ca on c.id_client = ca.id_client
                      LEFT JOIN pays_v2 ccountry on c.id_pays_naissance = ccountry.id_pays
                      LEFT JOIN pays_v2 acountry on ca.id_pays = acountry.id_pays
                      LEFT JOIN nationalites_v2 nv2 on c.id_nationalite = nv2.id_nationalite
                    group by
                      c.id_client";

        $this->tryIt($sQuery, 'emprunteurs');
    }

    public function extractProjects()
    {
        $sQuery = "SELECT
                        p.id_project AS 'IDProjet',
                        REPLACE(cl.source,',','') AS 'Source1',
                        REPLACE(cl.source2,',','') AS 'Source2',
                        p.id_company AS 'IDCompany',
                        p.amount AS 'Amount',
                        p.period AS 'NbMois',
                        CASE p.date_publication
                          WHEN '0000-00-00' then ''
                          ELSE p.date_publication
                        END AS 'Date_Publication',
						CASE p.date_retrait
                          WHEN '0000-00-00' then ''
                          ELSE p.date_retrait
                        END AS 'Date_Retrait',
                        CASE p.added
                          WHEN '0000-00-00 00:00:00' then ''
                          ELSE p.added
                        END AS 'Date_Ajout',
                        CASE p.updated
                          WHEN '0000-00-00 00:00:00' then ''
                          ELSE p.updated
                        END AS 'Date_Mise_Jour',
                        REPLACE(ps.label,',','') AS 'Status',
                        pn.note AS 'Note',
                        CASE REPLACE(co.name,',','')
                          WHEN '' THEN 'A renseigner'
                          ELSE REPLACE(co.name,',','')
                        END AS 'Nom_Societe',
                        REPLACE(co.forme,',','') AS 'Forme',
                        REPLACE(co.siren,'\t','') AS 'Siren',
                        REPLACE(co.adresse1,',','') as 'Adresse1',
                        REPLACE(co.adresse2,',','') as 'Adresse2',
                        REPLACE(co.zip,',','') AS 'CP',
                        REPLACE(co.city,',','') AS 'Ville',
                        co.id_pays AS 'IdPays',
                        REPLACE(co.phone,'\t','') AS 'Telephone',
                        co.status_client AS 'Status_Client',
                        CASE co.added
                          WHEN '0000-00-00 00:00:00' then ''
                          ELSE co.added
                        END AS 'Date_ajout',
                        CASE co.updated
                          WHEN '0000-00-00 00:00:00' then ''
                          ELSE co.updated
                        END AS 'Date_Mise_A_Jour',
                        co.id_client_owner AS 'IDClient'
                    FROM
                        projects p
                    LEFT JOIN
                        companies co ON (p.id_company = co.id_company)
                    LEFT JOIN
                        clients cl ON (cl.id_client = co.id_client_owner)
                    LEFT JOIN
                        projects_notes pn ON (p.id_project = pn.id_project)
                    LEFT JOIN
                      projects_last_status_history pslh ON pslh.id_project = p.id_project
                    LEFT JOIN
                      projects_status_history psh ON psh.id_project_status_history = pslh.id_project_status_history
                    LEFT JOIN
                      projects_status ps ON ps.id_project_status = psh.id_project_status";

        $this->tryIt($sQuery, 'projects');
    }

    public function extractLenders()
    {
        $sQuery = "SELECT
                      c.id_client as 'IDClient',
                      la.id_lender_account as 'IDPreteur',
                      c.id_langue as 'Langue',
                      REPLACE(c.source,',','') as 'Source1',
                      REPLACE(c.source2,',','') as 'Source2',
                      REPLACE(c.source3,',','') as 'Source3',
                      REPLACE(c.civilite,',','') as 'Civilite',
                      REPLACE(c.nom,',','') as 'Nom',
                      REPLACE(c.nom_usage,',','') as 'NomUsage',
                      REPLACE(c.prenom,',','') as 'Prenom',
                      REPLACE(c.fonction,',','') as 'Fonction',
                      CASE c.naissance
                      WHEN '0000-00-00' then '2001-01-01'
                      ELSE
                        CASE SUBSTRING(c.naissance,1,1)
                        WHEN '0' then '2001-01-01'
                        ELSE c.naissance
                        END
                      END as 'Datenaissance',
                      REPLACE(ville_naissance,',','') as 'Villenaissance',
                      ccountry.fr as 'PaysNaissance',
                      nv2.fr_f as 'Nationalite',
                      REPLACE(c.telephone,'\t','') as 'Telephone',
                      REPLACE(c.mobile,',','') as 'Mobile',
                      REPLACE(c.email,',','') as 'Email',
                      c.etape_inscription_preteur as 'EtapeInscriptionPreteur',
                      CASE c.type
                      WHEN 1 THEN 'Physique'
                      WHEN 2 THEN 'Morale'
                      WHEN 3 THEN 'Physique'
                      ELSE 'Morale'
                      END as 'TypeContact',
                      CASE cs.status
                      WHEN 6 THEN 'oui'
                      ELSE 'non'
                      END as 'Valide',
                      (
                        SELECT cs.label FROM clients_status_history cshs1
                        INNER JOIN clients_status cs on cshs1.id_client_status =cs.id_client_status
                        WHERE cshs1.id_client=c.id_client
                        ORDER BY cshs1.added DESC LIMIT 1
                      ) AS 'StatusCompletude',
                      CASE c.added
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.added
                      END as 'DateInscription',
                      CASE c.updated
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.updated
                      END as 'DateDerniereMiseaJour',
                      CASE c.lastlogin
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.lastlogin
                      END as 'DateDernierLogin',
                      cs.id_client_status as 'StatutValidation',
                      status_inscription_preteur as 'StatusInscription',
                      count(
                          distinct(l.id_project)
                      ) as 'NbPretsValides',
                      REPLACE(ca.adresse1,',','') as 'Adresse1',
                      REPLACE(ca.adresse2,',','') as 'Adresse2',
                      REPLACE(ca.adresse3,',','') as 'Adresse3',
                      REPLACE(ca.cp,',','') as 'CP',
                      REPLACE(ca.ville,',','') as 'Ville',
                      acountry.fr as 'Pays',
                      SUM(l.amount)/100 as 'TotalPretEur',
                      CASE p.id_prospect WHEN NULL THEN '' ELSE CONCAT('P', p.id_prospect) END AS 'DeletingProspect',
                      '0012400000K0Bxw' as 'Sfcompte'
                    FROM
                      clients c
                      INNER JOIN lenders_accounts la on la.id_client_owner = c.id_client
                      LEFT JOIN clients_adresses ca on c.id_client = ca.id_client
                      LEFT JOIN pays_v2 ccountry on c.id_pays_naissance = ccountry.id_pays
                      LEFT JOIN pays_v2 acountry on ca.id_pays = acountry.id_pays
                      LEFT JOIN nationalites_v2 nv2 on c.id_nationalite = nv2.id_nationalite
                      LEFT JOIN loans l on la.id_lender_account = l.id_lender and l.status = 0
                      LEFT JOIN clients_status cs on c.status = cs.id_client_status
                      LEFT JOIN prospects p ON p.email = c.email
                    WHERE c.status = 1
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
                    REPLACE(source,',','') as 'source',
                    REPLACE(source2,',','') as 'source2',
                    REPLACE(source3,',','') as 'source3',
                    REPLACE(nom,',','') as 'nom',
                    REPLACE(prenom,',','') as 'prenom',
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
                $rCsvFile      = fopen(Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT . 'preteurs.csv', 'a');
                $sNom          = $sPrenom = $sEmail = '';
                while ($aRow = $this->oDatabase->fetch_assoc($rSql)) {
                    $aRow = array_map(array($this, 'cleanValue'), $aRow);
                    if ($aRow['nom'] != $sNom && $aRow['prenom'] != $sPrenom && $aRow['email'] != $sEmail) {
                        //Array adding in file preteur.csv
                        $aCsvProspect = array(
                            'P' . $aRow['id_prospect'],// We add the letter P to avoid error in the dataloader on duplicate key with lenders.
                            '',
                            $aRow['id_langue'],
                            $aRow['source'],
                            $aRow['source2'],
                            $aRow['source3'],
                            '',
                            $aRow['nom'],
                            '',
                            $aRow['prenom'],
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $aRow['email'],
                            '',
                            'Prospect',
                            'Prospect',
                            '',
                            $aRow['added'],
                            $aRow['updated'],
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            0,
                            '',
                            '0012400000K0Bxw'
                        );
                        fputs($rCsvFile, '""' . implode('"", ""', $aCsvProspect) . '""' . "\n");
                    }
                    $sNom    = $aRow['nom'];
                    $sPrenom = $aRow['prenom'];
                    $sEmail  = $aRow['email'];
                }
                fclose($rCsvFile);
                $iTimeEndCsv = microtime(true) - $iTimeStartCsv;
                $this->oLogger->addRecord(
                    ULogger::INFO,
                    'Generation of csv prospects in ' . round($iTimeEndCsv, 2),
                    array(__FILE__ . ' on line ' . __LINE__)
                );

                $this->oDatabase->free_result($rSql);
            } else {
                throw new \Exception(mysql_error($this->oDatabase->connect_id));
            }
        } catch (\Exception $oException) {
            $this->oLogger->addRecord(
                ULogger::ERROR,
                'Error on query prospects : ' . $oException->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    /**
     * @param resource $rSql      resource of query sql
     * @param string   $sNameFile name of csv file to write
     * @return mixed
     */
    private function createFileFromQuery($rSql, $sNameFile)
    {
        if (true === $this->createExtractDir()) {
            $iTimeStartCsv = microtime(true);
            $sNameFile .= (1 !== preg_match('/(\.csv)$/i', $sNameFile)) ? '.csv' : '';
            $rCsvFile   = fopen(Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT . $sNameFile, 'w');
            $iCountLine = 0;
            while ($aRow = $this->oDatabase->fetch_assoc($rSql)) {
                $aRow = array_map(array($this, 'cleanValue'), $aRow);
                switch ($sNameFile) {
                    case 'preteurs.csv':
                        $aRow['Valide'] = ('Valide' == $aRow['StatusCompletude']) ? 'Oui' : 'Non';
                        break;
                }
                if (0 === $iCountLine) {
                    fputs($rCsvFile, '""' . implode('"", ""', array_keys($aRow)) . '""' . "\n");
                }

                fputs($rCsvFile, '""' . implode('"", ""', $aRow) . '""' . "\n");
                $iCountLine++;
            }
            fclose($rCsvFile);

            $iTimeEndCsv = microtime(true) - $iTimeStartCsv;
            $this->oLogger->addRecord(
                ULogger::INFO,
                'Generation of csv ' . $sNameFile . ' in ' . round($iTimeEndCsv, 2),
                array(__FILE__ . ' on line ' . __LINE__)
            );
            return true;
        }

        return false;
    }

    private function createExtractDir()
    {
        if (false === is_dir(Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT)) {
            if (false === mkdir(Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT, 0777, true)) {
                $this->oLogger->addRecord(
                    ULogger::INFO,
                    'Error on create dir ' .
                    Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_EXTRACT,
                    array(__FILE__ . ' on line ' . __LINE__)
                );

                return false;
            }
        }

        return true;
    }

    /**
     * @param string $sQuery    sql query
     * @param string $sNameFile File Name to generate
     */
    private function tryIt($sQuery, $sNameFile)
    {
        try {
            $this->oDatabase->query('SET SQL_BIG_SELECTS=1');
            if ($rSql = $this->oDatabase->query($sQuery)) {
                $this->createFileFromQuery($rSql, $sNameFile . '.csv');
                $this->oDatabase->free_result($rSql);
            } else {
                throw new \Exception(mysql_error($this->oDatabase->connect_id));
            }
        } catch (\Exception $oException) {
            $this->oLogger->addRecord(
                ULogger::ERROR,
                'Error on query ' . $sNameFile . ' : ' . $oException->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    /**
     * @param string $sPath path of directory to unlink. If null, path of success log.
     */
    private function deleteStatusLog($sPath = null)
    {
        $sPath = (true === is_null($sPath)) ?
            Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_SUCCESS_LOG : $sPath;
        if (true === is_dir($sPath)) {
            $aFiles = array_diff(
                scandir(Bootstrap::$aConfig['path'][Bootstrap::$aConfig['env']] . self::PATH_SUCCESS_LOG),
                array('.', '..')
            );
            foreach ($aFiles as $sFile) {
                (is_dir($sPath . '/' . $sFile)) ? delTree($sPath . '/' . $sFile) : unlink($sPath . '/' . $sFile);
            }

            $bUnlinkSuccessLog = rmdir($sPath);
            $sTextLog          = (true === $bUnlinkSuccessLog) ? 'success.' : 'error.';
            $this->oLogger->addRecord(
                ULogger::INFO,
                'Deleting ' . self::PATH_SUCCESS_LOG . ' with message ' . $sTextLog,
                array(__FILE__ . ' at line ' . __LINE__)
            );
        }
    }

    private function cleanValue($sValue)
    {
        $sValue = html_entity_decode($sValue, ENT_QUOTES, 'UTF-8');
        $sValue = str_replace($this->aSearchCharacter, $this->aReplaceCharacter, $sValue);
        return $sValue;
    }
}
