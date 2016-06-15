<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class SalesforceManager
{
    /**
     * constant to specify path for extract
     */
    const PATH_EXTRACT = '/dataloader/extract/';

    /**
     * constant to specify path dataloader conf
     */
    const PATH_DATALOADER_CONF = '/dataloader/conf/';

    /**
     * constant to specify path for status log csv send by the dataloader
     */
    const PATH_SUCCESS_LOG = '/dataloader/status';

    /**
     * constant to specify dataloader version
     */
    const DATALOADER_VERSION = '26.0.0';

    /**
     * constant to specify the name of prospect's file for check if become client or not
     */
    const FILE_PROSPECTS_ONLY = 'tempProspect.csv';

    /**  @var LoggerInterface */
    private $oLogger;
    /**  @var array with character to replace */
    private $aSearchCharacter;
    /**@var array with character of replacement */
    private $aReplaceCharacter;
    /** @var EntityManager */
    private $oEntityManager;
    /** @var  string */
    private $sRootDir;

    /**
     * SalesForceManager constructor.
     * @param EntityManager $oEntityManager
     * @param $sRootDir
     * @param LoggerInterface $oLogger
     */
    public function __construct(EntityManager $oEntityManager, $sRootDir, LoggerInterface $oLogger)
    {
        $this->oEntityManager = $oEntityManager;
        $this->sRootDir = $sRootDir;
        $this->oLogger = $oLogger;
        $this->aSearchCharacter = array("\r\n", "\n", "\t", "'", ';', '"');
        $this->aReplaceCharacter = array('/', '/', '', '', '', '');
        $this->deleteStatusLog();
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
                'Error on query company : ' . $oException->getMessage(),
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
                'Error on query borrower : ' . $oException->getMessage(),
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
                'Error on query project : ' . $oException->getMessage(),
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
                'Error on query lender : ' . $oException->getMessage(),
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
            $oStatement = $this->oEntityManager->getRepository('prospects')->getProspectsSalesForce();
            if ($oStatement) {
                $iTimeStartCsv = microtime(true);
                $rCsvFile = fopen($this->sRootDir . self::PATH_EXTRACT . 'preteurs.csv', 'a');
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
                    'Generation of csv prospects in ' . round($iTimeEndCsv, 2),
                    array(__FILE__ . ' on line ' . __LINE__)
                );
            }
        } catch (\Exception $oException) {
            $this->oLogger->error(
                'Error on query prospects : ' . $oException->getMessage(),
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
            $rCsvFile = fopen($this->sRootDir . self::PATH_EXTRACT . $sNameFile, 'w');
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
                'Generation of csv ' . $sNameFile . ' in ' . round($iTimeEndCsv, 2),
                array(__FILE__ . ' on line ' . __LINE__)
            );
            return true;
        }

        return false;
    }

    private function createExtractDir()
    {
        if (false === is_dir($this->sRootDir . self::PATH_EXTRACT)) {
            if (false === mkdir($this->sRootDir . self::PATH_EXTRACT, 0777, true)) {
                $this->oLogger->error(
                    'Error on create dir ' . $this->sRootDir . self::PATH_EXTRACT,
                    array(__FILE__ . ' on line ' . __LINE__)
                );
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $sPath path of directory to unlink. If null, path of success log.
     */
    private function deleteStatusLog($sPath = null)
    {
        $sPath = (true === is_null($sPath)) ? $this->sRootDir . self::PATH_SUCCESS_LOG : $sPath;
        if (true === is_dir($sPath)) {
            $bUnlinkSuccessLog = $this->delTree($sPath);
            $sTextLog = (true === $bUnlinkSuccessLog) ? 'success.' : 'error.';
            $this->oLogger->info(
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

    private function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}