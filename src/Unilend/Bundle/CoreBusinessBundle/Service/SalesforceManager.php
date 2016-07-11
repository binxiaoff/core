<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class SalesforceManager
{
    /** @var LoggerInterface */
    private $oLogger;

    /** @var array with character to replace */
    private $aSearchCharacter;

    /** @var array with character of replacement */
    private $aReplaceCharacter;

    /** @var EntityManager */
    private $oEntityManager;

    /** @var string */
    private $sExtractionDir;

    /** @var string */
    private $configDir;

    /**
     * SalesForceManager constructor.
     * @param EntityManager $oEntityManager
     * @param $sExtractionDir
     * @param $configDir
     * @param LoggerInterface $oLogger
     */
    public function __construct(EntityManager $oEntityManager, $sExtractionDir, $configDir, LoggerInterface $oLogger)
    {
        $this->oEntityManager = $oEntityManager;
        $this->sExtractionDir = $sExtractionDir;
        $this->configDir = $configDir;
        $this->oLogger = $oLogger;
        $this->aSearchCharacter = array("\r\n", "\n", "\t", "'", ';', '"');
        $this->aReplaceCharacter = array('/', '/', '', '', '', '');
    }

    public function extractCompanies()
    {
        try {
            $oStatement = $this->oEntityManager->getRepository('companies')->getCompaniesSalesForce();
            if ($oStatement) {
                $this->createFileFromQuery($oStatement, 'companies.csv');
            }
        } catch (\Exception $oException) {
            $this->oLogger->error(
                'Error on company Salesforces query: ' . $oException->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    public function extractBorrowers()
    {
        try {
            $oStatement = $this->oEntityManager->getRepository('clients')->getBorrowersSalesForce();
            if ($oStatement) {
                $this->createFileFromQuery($oStatement, 'emprunteurs.csv');
            }
        } catch (\Exception $oException) {
            $this->oLogger->error(
                'Error on borrower Salesforces query: ' . $oException->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    public function extractProjects()
    {
        try {
            $oStatement = $this->oEntityManager->getRepository('projects')->getProjectsSalesForce();
            if ($oStatement) {
                $this->createFileFromQuery($oStatement, 'projects.csv');
            }
        } catch (\Exception $oException) {
            $this->oLogger->error(
                'Error on project Salesforces query: ' . $oException->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    public function extractLenders()
    {
        try {
            $oStatement = $this->oEntityManager->getRepository('lenders_accounts')->getLendersSalesForce();
            if ($oStatement) {
                $this->createFileFromQuery($oStatement, 'preteurs.csv');
            }
        } catch (\Exception $oException) {
            $this->oLogger->error(
                'Error on lender Salesforces query: ' . $oException->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
        $this->extractProspects();
    }

    /**
     * Query sql for prospects and treatment specific because prospect haven't same number columns
     */
    public function extractProspects()
    {
        try {
            /** @var Statement $oStatement */
            $oStatement = $this->oEntityManager->getRepository('prospects')->getProspectsSalesForce();
            if ($oStatement) {
                $iTimeStartCsv = microtime(true);
                $rCsvFile = fopen($this->sExtractionDir . '/' . 'preteurs.csv', 'a');
                $sNom = $sPrenom = $sEmail = '';
                while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
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
                    $sNom = $aRow['nom'];
                    $sPrenom = $aRow['prenom'];
                    $sEmail = $aRow['email'];
                }
                $oStatement->closeCursor();
                fclose($rCsvFile);
                $iTimeEndCsv = microtime(true) - $iTimeStartCsv;
                $this->oLogger->info(
                    'Prospects Salesforce CSV generated in ' . round($iTimeEndCsv, 2) . ' seconds',
                    array(__FILE__ . ' on line ' . __LINE__)
                );
            }
        } catch (\Exception $oException) {
            $this->oLogger->error(
                'Error on prospects Salesforce query: ' . $oException->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    /**
     * @param Statement $oStatement resource of query sql
     * @param string $sNameFile name of csv file to write
     * @return mixed
     */
    private function createFileFromQuery($oStatement, $sNameFile)
    {
        if (true === $this->createExtractDir()) {
            $iTimeStartCsv = microtime(true);
            $sNameFile .= (1 !== preg_match('/(\.csv)$/i', $sNameFile)) ? '.csv' : '';
            $rCsvFile = fopen($this->sExtractionDir . '/' . $sNameFile, 'w');
            $iCountLine = 0;
            while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
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
            $oStatement->closeCursor();
            fclose($rCsvFile);

            $iTimeEndCsv = microtime(true) - $iTimeStartCsv;
            $this->oLogger->info(
                $sNameFile . ' CSV generated in ' . round($iTimeEndCsv, 2) . ' seconds',
                array(__FILE__ . ' on line ' . __LINE__)
            );
            return true;
        }

        return false;
    }

    private function createExtractDir()
    {
        if (false === is_dir($this->sExtractionDir)) {
            if (false === mkdir($this->sExtractionDir, 0775, true)) {
                $this->oLogger->error(
                    'Error on creating directory ' . $this->sExtractionDir,
                    array(__FILE__ . ' on line ' . __LINE__)
                );
                return false;
            }
        }
        return true;
    }

    private function cleanValue($sValue)
    {
        $sValue = html_entity_decode($sValue, ENT_QUOTES, 'UTF-8');
        $sValue = str_replace($this->aSearchCharacter, $this->aReplaceCharacter, $sValue);
        return $sValue;
    }
}
