<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class SalesforceManager
{
    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /** @var  EntityManager */
    private $entityManager;

    /** @var string */
    private $extractionDir;

    /** @var string */
    private $configDir;

    /**
     * SalesForceManager constructor.
     *
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManager          $entityManager
     * @param                        $extractionDir
     * @param                        $configDir
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerSimulator $entityManagerSimulator, EntityManager $entityManager, $extractionDir, $configDir, LoggerInterface $logger)
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->extractionDir          = $extractionDir;
        $this->configDir              = $configDir;
        $this->logger                 = $logger;
    }

    public function extractCompanies()
    {
        try {
            $statement = $this->entityManagerSimulator->getRepository('companies')->getCompaniesSalesForce();
            if ($statement) {
                $this->createFileFromQuery($statement, 'companies.csv');
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error on company Salesforces query: ' . $exception->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    public function extractBorrowers()
    {
        try {
            $statement = $this->entityManagerSimulator->getRepository('clients')->getBorrowersSalesForce();
            if ($statement) {
                $this->createFileFromQuery($statement, 'emprunteurs.csv');
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error on borrower Salesforces query: ' . $exception->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    public function extractProjects()
    {
        try {
            $statement = $this->entityManagerSimulator->getRepository('projects')->getProjectsSalesForce();
            if ($statement) {
                $this->createFileFromQuery($statement, 'projects.csv');
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error on project Salesforces query: ' . $exception->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    public function extractLenders()
    {
        try {
            $statement = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->getLendersSalesForce();
            if ($statement) {
                $this->createFileFromQuery($statement, 'preteurs.csv');
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error on lender Salesforces query: ' . $exception->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
        $this->extractProspects();
    }

    /**
     * Query sql for prospects and treatment specific because prospect haven't same number columns
     */
    private function extractProspects()
    {
        try {
            /** @var Statement $statement */
            $statement = $this->entityManagerSimulator->getRepository('prospects')->getProspectsSalesForce();
            if ($statement) {
                $timeStartCsv = microtime(true);
                $csvFile      = fopen($this->extractionDir . '/' . 'preteurs.csv', 'a');
                $name         = $firstName = $email = '';
                while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                    $row = array_map(array($this, 'cleanValue'), $row);
                    if ($row['nom'] != $name && $row['prenom'] != $firstName && $row['email'] != $email) {
                        //Array adding in file preteur.csv
                        $csvProspect = array(
                            'P' . $row['id_prospect'],// We add the letter P to avoid error in the dataloader on duplicate key with lenders.
                            '',
                            $row['id_langue'],
                            $row['source'],
                            $row['source2'],
                            $row['source3'],
                            '',
                            $row['nom'],
                            '',
                            $row['prenom'],
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $row['email'],
                            '',
                            'Prospect',
                            'Prospect',
                            '',
                            $row['added'],
                            $row['updated'],
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
                        fputs($csvFile, '"' . implode('","', $csvProspect) . '"' . "\r\n");
                    }
                    $name      = $row['nom'];
                    $firstName = $row['prenom'];
                    $email     = $row['email'];
                }
                $statement->closeCursor();
                fclose($csvFile);
                $timeEndCsv = microtime(true) - $timeStartCsv;
                $this->logger->info(
                    'Prospects Salesforce CSV generated in ' . round($timeEndCsv, 2) . ' seconds',
                    array(__FILE__ . ' on line ' . __LINE__)
                );
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error on prospects Salesforce query: ' . $exception->getMessage(),
                array(__FILE__ . ' on line ' . __LINE__)
            );
        }
    }

    /**
     * @param Statement $statement resource of query sql
     * @param string    $fileName  name of csv file to write
     *
     * @return mixed
     */
    private function createFileFromQuery($statement, $fileName)
    {
        if (true === $this->createExtractDir()) {
            $timeStartCsv = microtime(true);
            $fileName     .= (1 !== preg_match('/(\.csv)$/i', $fileName)) ? '.csv' : '';
            $csvFile      = fopen($this->extractionDir . '/' . $fileName, 'w');
            $countLine    = 0;
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $row = array_map(array($this, 'cleanValue'), $row);
                switch ($fileName) {
                    case 'preteurs.csv':
                        $row['Valide'] = ('Valide' == $row['StatusCompletude']) ? 'Oui' : 'Non';
                        break;
                }
                if (0 === $countLine) {
                    fputs($csvFile, '"' . implode('","', array_keys($row)) . '"' . "\r\n");
                }

                fputs($csvFile, '"' . implode('","', $row) . '"' . "\r\n");
                $countLine++;
            }
            $statement->closeCursor();
            fclose($csvFile);

            $timeEndCsv = microtime(true) - $timeStartCsv;
            $this->logger->info(
                $fileName . ' CSV generated in ' . round($timeEndCsv, 2) . ' seconds',
                array(__FILE__ . ' on line ' . __LINE__)
            );
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function createExtractDir()
    {
        if (false === is_dir($this->extractionDir)) {
            if (false === mkdir($this->extractionDir, 0775, true)) {
                $this->logger->error(
                    'Error on creating directory ' . $this->extractionDir,
                    array(__FILE__ . ' on line ' . __LINE__)
                );
                return false;
            }
        }
        return true;
    }

    /**
     * @param $value
     *
     * @return mixed|string
     */
    private function cleanValue($value)
    {
        $searchCharacter  = array("\r\n", "\n", "\t", "'", ';', '"');
        $replaceCharacter = array('/', '/', '', '', '', '');
        $value            = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value            = str_replace($searchCharacter, $replaceCharacter, $value);
        return $value;
    }
}
