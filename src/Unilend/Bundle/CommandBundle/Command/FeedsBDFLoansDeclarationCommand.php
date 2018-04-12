<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{
    InputArgument, InputInterface, InputOption
};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    CompanyStatus, Echeanciers, UnderlyingContract
};
use Unilend\Bundle\CoreBusinessBundle\Repository\TransmissionSequenceRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\BdfLoansDeclarationManager;
use Unilend\core\Loader;

class FeedsBDFLoansDeclarationCommand extends ContainerAwareCommand
{
    const PAD_LENGTH_RECORD   = 151;
    const PAD_LENGTH_SEQUENCE = 6;
    const PADDING_CHAR        = ' ';
    const PADDING_NUMBER      = '0';

    /** @var int $recordLineNumber */
    private $recordLineNumber = 1;
    /** @var \DateTime $declarationDate */
    private $declarationDate;
    /** @var string $sequentialNumber */
    private $sequentialNumber;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('unilend:feed_out:bdf_loans_declaration:generate')
            ->setDescription('Generate the loans declaration txt file to send to Banque De France')
            ->addOption('debug',
                null,
                InputOption::VALUE_OPTIONAL,
                'Generate the CSV file? (y/n)',
                false
            )
            ->addArgument(
                'year',
                InputArgument::OPTIONAL,
                'Year of the declaration to regenerate(format [0-9]{4})'
            )
            ->addArgument(
                'month',
                InputArgument::OPTIONAL,
                'Month of the declaration to regenerate(format: [0-9]{2})'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('monolog.logger.console');
        /** @var \projects $project */
        $project = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('projects');

        $year  = $input->getArgument('year');
        $month = $input->getArgument('month');
        $debug = $input->getOption('debug');

        if ($year && $month) {
            if (preg_match('/[0-9]{2}/', $month, $matches) && preg_match('/[0-9]{4}/', $year, $matches)) {
                $this->declarationDate = (new \DateTime())->setDate((int) $year, (int) $month, date('d'));
            } else {
                $output->writeln('<error>Wrong date format, expected parameters : YYYY MM)</error>');

                return;
            }
        } else {
            $this->declarationDate = new \DateTime('last month');
        }

        try {
            $output->writeln('Getting data..');
            $ifpData = $project->getDataForBDFDeclaration($this->declarationDate, [UnderlyingContract::CONTRACT_BDC, UnderlyingContract::CONTRACT_IFP]);
            $cipData = $project->getDataForBDFDeclaration($this->declarationDate, [UnderlyingContract::CONTRACT_MINIBON]);
        } catch (\Exception $exception) {
            $logger->error(
                sprintf('Could not get data to generate BDF loan declarations. Error: %s', $exception->getMessage()),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            return;
        }

        if (empty($ifpData)) {
            $logger->info('No data found for IFP contracts', ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        if (empty($cipData)) {
            $logger->info('No data found for CIP contracts', ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        if (empty($cipData + $ifpData)) {
            return;
        }

        $output->writeln('Generating files..');

        $ifpFileName = BdfLoansDeclarationManager::UNILEND_IFP_ID . '_' . $this->declarationDate->format('Ym') . '.txt';
        try {
            $this->writeFile($ifpData, $ifpFileName, BdfLoansDeclarationManager::TYPE_IFP_BDC);
        } catch (\Exception $exception) {
            $logger->error(
                sprintf('Could not generate IFP declaration file. Error: %s', $exception->getMessage()),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        $output->writeln('IFP file processed');

        $this->recordLineNumber = 1;
        $cipFileName            = BdfLoansDeclarationManager::UNILEND_CIP_ID . '_' . $this->declarationDate->format('Ym') . '.txt';
        try {
            $this->writeFile($cipData, $cipFileName, BdfLoansDeclarationManager::TYPE_MINIBON);
        } catch (\Exception $exception) {
            $logger->error(
                sprintf('Could not generate CIP declaration file. Error: %s', $exception->getMessage()),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        $output->writeln('CIP file processed');

        if ('y' === strtolower($debug)) {
            try {
                $output->writeln('Generating CSV Files..');
                $output->writeln('Generating CSV File for IFP contracts...');

                $this->createCSVFile($ifpData, BdfLoansDeclarationManager::UNILEND_IFP_ID);

                $output->writeln(['Done', 'Generating CSV File for CIP contracts...']);

                $this->createCSVFile($cipData, BdfLoansDeclarationManager::UNILEND_CIP_ID);
                $allContractsData = $project->getDataForBDFDeclaration($this->declarationDate, [UnderlyingContract::CONTRACT_IFP, UnderlyingContract::CONTRACT_BDC, UnderlyingContract::CONTRACT_MINIBON]);

                $output->writeln(['Done', 'Generating CSV File for both IFP and CIP contracts...']);

                $this->createCSVFile($allContractsData, 'tout_contrat');

                $output->writeln('Done');
            } catch (\Exception $exception) {
                $logger->warning(sprintf('Could not generate CSV files for debug. Error: %s', $exception->getMessage()),
                    ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
        }
    }

    /**
     * @param array  $data
     * @param string $fileName
     * @param string $type
     *
     * @throws \Exception
     */
    private function writeFile(array $data, string $fileName, string $type): void
    {
        $bdfLoansDeclarationManager = $this->getContainer()->get('unilend.service.bdf_loans_declaration_manager');
        $fileManager                = $this->getContainer()->get('filesystem');

        switch ($type) {
            case BdfLoansDeclarationManager::TYPE_IFP_BDC:
                $declarerId      = BdfLoansDeclarationManager::UNILEND_IFP_ID;
                $filePath        = $bdfLoansDeclarationManager->getIfpPath();
                $fileArchivePath = $bdfLoansDeclarationManager->getIfpArchivePath();
                break;
            case BdfLoansDeclarationManager::TYPE_MINIBON:
                $declarerId      = BdfLoansDeclarationManager::UNILEND_CIP_ID;
                $filePath        = $bdfLoansDeclarationManager->getCipPath();
                $fileArchivePath = $bdfLoansDeclarationManager->getCipArchivePath();
                break;
            default:
                throw new \Exception(sprintf('Unknown declarer type, expected types are: ("%s", "%s")', BdfLoansDeclarationManager::TYPE_IFP_BDC, BdfLoansDeclarationManager::TYPE_MINIBON));
        }

        if (false === $fileManager->exists($filePath)) {
            $fileManager->mkdir($filePath);
        }

        $absoluteFilePath = implode(DIRECTORY_SEPARATOR, [$filePath, $fileName]);
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var TransmissionSequenceRepository $transmissionSequenceRepository */
        $transmissionSequenceRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:TransmissionSequence');
        $sequence                       = $transmissionSequenceRepository->findOneBy(['elementName' => $fileName]);

        if (null !== $sequence && $fileManager->exists($absoluteFilePath)) {
            $currentName = pathinfo($absoluteFilePath, PATHINFO_FILENAME);

            if (false === $fileManager->exists($fileArchivePath)) {
                $fileManager->mkdir($fileArchivePath);
            }

            try {
                $fileManager->rename($absoluteFilePath, implode(DIRECTORY_SEPARATOR, [$fileArchivePath, $currentName . '_' . $sequence->getSequence() . '.txt']), true);
            } catch (\Exception $exception) {
                $fileManager->remove($absoluteFilePath);
                $this->getContainer()->get('monolog.logger.console')->error(
                    sprintf('Could not archive the old file "%s", it will be removed. Error: %s', $absoluteFilePath, $exception->getMessage()),
                    ['method' => __METHOD__, 'file' => $exception->getMessage(), 'line' => $exception->getLine()]
                );
            }
        }

        try {
            $fileManager->appendToFile($absoluteFilePath, $this->getStartSenderRecord($fileName, $declarerId));
            $fileManager->appendToFile($absoluteFilePath, $this->getStartDeclarerRecord($declarerId));

            foreach ($data as $row) {
                $loanRecord = $this->getLoanRecord($row, $declarerId);

                if (null !== $loanRecord) {
                    $fileManager->appendToFile($absoluteFilePath, $loanRecord);
                }

            }
            $fileManager->appendToFile($absoluteFilePath, $this->getEndDeclarerRecord($declarerId));
            $fileManager->appendToFile($absoluteFilePath, $this->getEndSenderRecord($declarerId));
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->error(
                sprintf('An exception occurred when writing the "%s" loan lines into "%s". - Error: %s', $type, $absoluteFilePath, $exception->getMessage()),
                ['class' => __CLASS__, 'function' => __FUNCTION__]
            );
            $transmissionSequenceRepository->rollbackSequence($fileName);
            $fileManager->remove($absoluteFilePath);
        }
    }

    /**
     * Set the line number sequence
     */
    private function setSequentialNumber()
    {
        $this->sequentialNumber = str_pad($this->recordLineNumber, self::PAD_LENGTH_SEQUENCE, self::PADDING_NUMBER, STR_PAD_LEFT);
        $this->recordLineNumber++;
    }

    /**
     * @param string $code
     * @param string $declarerId
     *
     * @return string
     */
    private function getStartingRecord(string $code, string $declarerId): string
    {
        $this->setSequentialNumber();

        return $this->sequentialNumber . $code . $this->declarationDate->format('Ym') . $declarerId;
    }

    /**
     * @param string $fileName
     * @param string $declarerId
     *
     * @return string
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function getStartSenderRecord(string $fileName, string $declarerId): string
    {
        /** @var TransmissionSequenceRepository $transmissionSequenceRepository */
        $transmissionSequenceRepository = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:TransmissionSequence');
        $sequence                       = $transmissionSequenceRepository->getNextSequence($fileName);

        return str_pad($this->getStartingRecord('01', $declarerId) . str_pad($sequence->getSequence(), 2, self::PADDING_NUMBER, STR_PAD_LEFT), self::PAD_LENGTH_RECORD, self::PADDING_CHAR, STR_PAD_RIGHT) . PHP_EOL;
    }

    /**
     * @param string $declarerId
     *
     * @return string
     */
    private function getStartDeclarerRecord(string $declarerId): string
    {
        return str_pad($this->getStartingRecord('02', $declarerId), self::PAD_LENGTH_RECORD, self::PADDING_CHAR, STR_PAD_RIGHT) . PHP_EOL;
    }

    /**
     * @param array  $projectData
     * @param string $declarerId
     *
     * @return string|null
     * @throws \Exception
     */
    private function getLoanRecord(array $projectData, string $declarerId): ?string
    {
        $projectData = $this->getProjectInformation($projectData, $declarerId);

        if (null !== $projectData) {
            return $this->multiBytePad($this->getStartingRecord('11', $declarerId) . $projectData, self::PAD_LENGTH_RECORD, self::PADDING_CHAR, STR_PAD_RIGHT) . PHP_EOL;
        }

        return null;
    }

    /**
     * @param string $declarerId
     *
     * @return string
     */
    public function getEndDeclarerRecord(string $declarerId): string
    {
        $recordCounter = str_pad($this->recordLineNumber - 1, self::PAD_LENGTH_SEQUENCE, self::PADDING_NUMBER, STR_PAD_LEFT);

        return str_pad($this->getStartingRecord('92', $declarerId) . $recordCounter, self::PAD_LENGTH_RECORD, self::PADDING_CHAR, STR_PAD_RIGHT) . PHP_EOL;
    }

    /**
     * @param string $declarerId
     *
     * @return string
     */
    public function getEndSenderRecord(string $declarerId): string
    {
        $recordCounter = str_pad($this->recordLineNumber, self::PAD_LENGTH_SEQUENCE, self::PADDING_NUMBER, STR_PAD_LEFT);

        return str_pad($this->getStartingRecord('93', $declarerId) . $recordCounter, self::PAD_LENGTH_RECORD, self::PADDING_CHAR, STR_PAD_RIGHT);
    }

    /**
     * @param array  $data
     * @param string $declarerId
     *
     * @return string|null
     * @throws \Exception
     */
    private function getProjectInformation(array $data, string $declarerId): ?string
    {
        $amount            = $this->getUnpaidAmountAndComingCapital($data, $declarerId);
        $roundedDueCapital = $this->checkAmounts($amount['owed_capital']);

        if ($roundedDueCapital == 0) {
            return null;
        }

        $projectLineInfo = $this->checkSiren($data['siren']);
        $projectLineInfo .= $this->checkName($data['name']);
        $projectLineInfo .= $this->checkLoanType($data['loan_type']);
        $projectLineInfo .= $this->checkAmounts($data['partial_loan_amount']);
        $projectLineInfo .= \DateTime::createFromFormat('Y-m-d H:i:s', $data['loan_date'])->format('Ymd');
        $projectLineInfo .= $this->checkLoanPeriod($data['loan_duration']);
        $projectLineInfo .= $this->checkLoanAvgRate($data['average_loan_rate']);
        $projectLineInfo .= $data['repayment_frequency'];
        $projectLineInfo .= $roundedDueCapital;
        $projectLineInfo .= $this->checkAmounts($amount['unpaid_amount']);
        $projectLineInfo .= $this->checkUnpaidDate($data['id_project'], $data['close_out_netting_date'], $data['judgement_date'], $data['late_payment_date'], $amount['unpaid_amount']);
        $projectLineInfo .= $this->checkLoanContributorNumber($data['contributor_person_number'], 'person');
        $projectLineInfo .= $this->checkLoanContributorPercentage($data['contributor_person_amount'], $data['partial_loan_amount']);
        $projectLineInfo .= $this->checkLoanContributorNumber($data['contributor_legal_entity_number'], 'legal_entity');
        $projectLineInfo .= $this->checkLoanContributorPercentage($data['contributor_legal_entity_amount'], $data['partial_loan_amount']);
        $projectLineInfo .= $this->checkLoanContributorNumber($data['contributor_credit_institution_number'], 'credit_institution');
        $projectLineInfo .= $this->checkLoanContributorPercentage($data['contributor_credit_institution_amount'], $data['partial_loan_amount']);

        return $projectLineInfo;
    }

    /**
     * @param array  $data
     * @param string $declarerId
     *
     * @return array
     * @throws \Exception
     */
    private function getUnpaidAmountAndComingCapital(array $data, string $declarerId): array
    {
        /** @var \echeanciers $repayment */
        $repayment = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('echeanciers');

        if (false === empty($data['close_out_netting_date'])) {
            /** @var \DateTime $date */
            $date   = \DateTime::createFromFormat('Y-m-d', $data['close_out_netting_date']);
            $amount = bcadd($repayment->getUnpaidAmountAtDate($data['id_project'], $date), $repayment->getTotalComingCapitalByProject($data['id_project'], $date), 2);
            $amount = bcsub($amount, $data['debt_collection_repayment'], 2);
            $return = ['unpaid_amount' => $amount, 'owed_capital' => $amount];
        } elseif (false === empty($data['judgement_date']) && CompanyStatus::STATUS_COMPULSORY_LIQUIDATION === $data['companyStatusLabel']) {
            /** @var \DateTime $date */
            $date   = \DateTime::createFromFormat('Y-m-d', $data['judgement_date']);
            $amount = bcadd($repayment->getUnpaidAmountAtDate($data['id_project'], $date), $repayment->getTotalComingCapitalByProject($data['id_project'], $date), 2);
            $return = ['unpaid_amount' => $amount, 'owed_capital' => $amount];
        } else {
            $date         = \DateTime::createFromFormat('Ymd H:i:s', $this->declarationDate->format('Ymt 23:59:59'));
            $unpaidAmount = $repayment->getUnpaidAmountAtDate($data['id_project'], $date);
            $owedCapital  = $repayment->getTotalComingCapitalByProject($data['id_project'], $date);
            $return       = ['unpaid_amount' => $unpaidAmount, 'owed_capital' => bcadd($unpaidAmount, $owedCapital, 2)];
        }

        /**
         * Calculate the prorata of owed amount based on total loan amount and the partial total amount of aggregated underlying contract types
         */
        $return['owed_capital'] = bcmul($return['owed_capital'], bcdiv($data['partial_loan_amount'], $data['loan_amount'], 6), 2);

        /**
         * The unpaid amount will only be declared in the IFP file to avoid to have smaller prorated amounts, which can
         * induce declaring 0 unpaid amounts after rounding to the thousand
         */
        if (BdfLoansDeclarationManager::UNILEND_CIP_ID === $declarerId) {
            $return['unpaid_amount'] = 0;
        }

        return $return;
    }

    /**
     * @param string $siren
     *
     * @return string
     * @throws \Exception
     */
    private function checkSiren(string $siren): string
    {
        $siren = trim($siren);

        if (strlen($siren) > 9) {
            throw new \Exception('Siren too long: ' . $siren);
        }

        return str_pad($siren, 9, self::PADDING_CHAR, STR_PAD_RIGHT);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function checkName(string $name): string
    {
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        return $this->multiBytePad(strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', ' ', $ficelle->stripAccents($name)))), 60, self::PADDING_CHAR, STR_PAD_RIGHT);
    }

    /**
     * @param string $loanType
     *
     * @return string mixed
     * @throws \Exception
     */
    private function checkLoanType(string $loanType): string
    {
        if (false === in_array($loanType, ['MA', 'IM', 'ST', 'CO', 'CL', 'EX', 'AU'])) {
            throw new \Exception('Unknown loan type: ' . $loanType);
        }

        return $loanType;
    }

    /**
     * @param float|null $amount
     *
     * @return string
     * @throws \Exception
     */
    private function checkAmounts(?float $amount): string
    {
        $amount = round($amount / 1000, 0);
        if (strlen($amount) > 5) {
            throw new \Exception('Amount too big: ' . $amount);
        }

        return str_pad($amount, 5, self::PADDING_NUMBER, STR_PAD_LEFT);
    }

    /**
     * @param int $loanPeriod
     *
     * @return string
     * @throws \Exception
     */
    private function checkLoanPeriod(int $loanPeriod): string
    {
        if (strlen($loanPeriod) > 3) {
            throw new \Exception('Project duration too long: ' . $loanPeriod);
        }

        return str_pad($loanPeriod, 3, self::PADDING_NUMBER, STR_PAD_LEFT);
    }

    /**
     * @param float $loanAvgRate
     *
     * @return string
     * @throws \Exception
     */
    private function checkLoanAvgRate(float $loanAvgRate): string
    {
        /** @var \ficelle $ficelle */
        $ficelle     = Loader::loadLib('ficelle');
        $loanAvgRate = $ficelle->formatNumber($loanAvgRate, 2);

        if (strlen($loanAvgRate) > 5) {
            throw new \Exception('wrong average rate: ' . $loanAvgRate);
        }

        return str_pad($loanAvgRate, 5, self::PADDING_NUMBER, STR_PAD_LEFT);
    }

    /**
     * @param int         $projectId
     * @param string|null $closeOutNettingDate
     * @param string|null $judgementDate
     * @param string|null $latePaymentDate
     * @param string|null $unpaidAmount
     *
     * @return string
     * @throws \Exception
     */
    private function checkUnpaidDate(int $projectId, ?string $closeOutNettingDate, ?string $judgementDate, ?string $latePaymentDate, ?string $unpaidAmount): string
    {
        if (0 == $this->checkAmounts($unpaidAmount)) {
            return '00000000';
        }
        if (false === empty($closeOutNettingDate)) {
            return \DateTime::createFromFormat('Y-m-d', $closeOutNettingDate)->format('Ymd');
        } elseif (false === empty($judgementDate)) {
            return \DateTime::createFromFormat('Y-m-d', $judgementDate)->format('Ymd');
        } elseif (false === empty($latePaymentDate)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $latePaymentDate)->format('Ymd');
        } else {
            try {
                /** @var Echeanciers $repayment */
                $repayment = $this->getContainer()->get('doctrine.orm.entity_manager')
                    ->getRepository('UnilendCoreBusinessBundle:Echeanciers')->findFirstOverdueScheduleByProject($projectId);

                if (null !== $repayment) {
                    return $repayment->getDateEcheance()->format('Ymd');
                }
            } catch (\Exception $exception) {
                $this->getContainer()->get('monolog.logger.console')->error(
                        sprintf('Could not get the first overdue schedule for project %s. Please check the output file. Exception: %s', $projectId, $exception->getMessage()),
                        ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                    );
            }
            return '00000000';
        }
    }

    /**
     * @param int|null $number
     * @param string   $type
     *
     * @return string
     * @throws \Exception
     */
    private function checkLoanContributorNumber(?int $number, string $type): string
    {
        switch ($type) {
            case 'person':
            case 'legal_entity':

                if (strlen($number) > 5) {
                    throw new \Exception('Wrong contributor number, type=' . $type . ' number=' . $number);
                }
                return str_pad($number, 5, self::PADDING_NUMBER, STR_PAD_LEFT);
            case 'credit_institution':
                if (strlen($number) > 2) {
                    throw new \Exception('Wrong contributor number, type=' . $type . ' number=' . $number);
                }
                return str_pad($number, 2, self::PADDING_NUMBER, STR_PAD_LEFT);
            default:
                throw new \Exception('Wrong contributor type, type=' . $type . ' number=' . $number);
        }
    }

    /**
     * @param float|null $contributionAmount
     * @param float|null $partialLoanAmount
     *
     * @return string
     * @throws \Exception
     */
    private function checkLoanContributorPercentage(?float $contributionAmount, ?float $partialLoanAmount): string
    {
        if ($partialLoanAmount === 0) {
            throw new \Exception(sprintf('Wrong loan amount error in method: "%s", at line: %s', __METHOD__, __LINE__));
        }

        $percentage = bcmul(bcdiv($contributionAmount, $partialLoanAmount, 4), 100, 4);

        if ($percentage > 100) {
            throw new \Exception(sprintf('Wrong contributor percentage error in method: "%s", at line: %s. Value: %s ', __METHOD__, __LINE__, $percentage));
        }

        return str_pad(round($percentage, 0), 3, self::PADDING_NUMBER, STR_PAD_LEFT);
    }

    /**
     * @param string $input
     * @param int    $padLength
     * @param string $padString
     * @param int    $padType
     * @param string $encoding
     *
     * @return string
     */
    private function multiBytePad(string $input, int $padLength, string $padString = ' ', int $padType = STR_PAD_RIGHT, string $encoding = 'UTF-8'): string
    {
        $mbDiff = strlen($input) - mb_strlen($input, $encoding);
        return str_pad($input, $padLength + $mbDiff, $padString, $padType);
    }

    /**
     * @param array  $data
     * @param string $declarerId
     *
     * @throws \Exception
     */
    private function createCSVFile(array $data, string $declarerId): void
    {
        $fileManager     = $this->getContainer()->get('filesystem');
        $debugPath       = $this->getContainer()->get('unilend.service.bdf_loans_declaration_manager')->getBaseDir() . '/debug/';
        $header          = [
            'siren', 'name', 'loan type', 'loan amount', 'loan date', 'loan duration', 'average loan rate', 'repayment frequency', 'CRD including interests', 'Unpaid amount',
            'unpaid date', 'contributor person number', 'contributor person percentage', 'contributor legal entity number', 'contributor legal entity percentage', 'contributor Bank number', 'contributor Bank percentage',
        ];
        $csvFileName     = $declarerId . '_' . $this->declarationDate->format('Ym') . '.csv';
        $csvAbsolutePath = $debugPath . $csvFileName;

        if ($fileManager->exists($csvAbsolutePath)) {
            $fileManager->remove($csvAbsolutePath);
        }

        $fileManager->appendToFile($csvAbsolutePath, implode(';', $header) . PHP_EOL);
        foreach ($data as $row) {
            $fileManager->appendToFile($csvAbsolutePath, $this->getProjectInformationCsv($row, $declarerId));
        }
    }

    /**
     * @param array  $data
     * @param string $declarerId
     *
     * @return string|null
     * @throws \Exception
     */
    private function getProjectInformationCsv(array $data, string $declarerId): ?string
    {
        $amount            = $this->getUnpaidAmountAndComingCapital($data, $declarerId);
        $roundedDueCapital = $this->checkAmounts($amount['owed_capital']);

        if ($roundedDueCapital == 0) {
            return null;
        }

        $projectLineInfo[] = $this->checkSiren($data['siren']);
        $projectLineInfo[] = $this->checkName($data['name']);
        $projectLineInfo[] = $this->checkLoanType($data['loan_type']);
        $projectLineInfo[] = $this->checkAmounts($data['partial_loan_amount']);
        $projectLineInfo[] = \DateTime::createFromFormat('Y-m-d H:i:s', $data['loan_date'])->format('Y-m-d');
        $projectLineInfo[] = $this->checkLoanPeriod($data['loan_duration']);
        $projectLineInfo[] = $this->checkLoanAvgRate($data['average_loan_rate']);
        $projectLineInfo[] = $data['repayment_frequency'];
        $projectLineInfo[] = $roundedDueCapital;
        $projectLineInfo[] = $this->checkAmounts($amount['unpaid_amount']);
        $projectLineInfo[] = $this->checkUnpaidDate($data['id_project'], $data['close_out_netting_date'], $data['judgement_date'], $data['late_payment_date'], $amount['unpaid_amount']);
        $projectLineInfo[] = $this->checkLoanContributorNumber($data['contributor_person_number'], 'person');
        $projectLineInfo[] = $this->checkLoanContributorPercentage($data['contributor_person_amount'], $data['partial_loan_amount']);
        $projectLineInfo[] = $this->checkLoanContributorNumber($data['contributor_legal_entity_number'], 'legal_entity');
        $projectLineInfo[] = $this->checkLoanContributorPercentage($data['contributor_legal_entity_amount'], $data['partial_loan_amount']);
        $projectLineInfo[] = $this->checkLoanContributorNumber($data['contributor_credit_institution_number'], 'credit_institution');
        $projectLineInfo[] = $this->checkLoanContributorPercentage($data['contributor_credit_institution_amount'], $data['partial_loan_amount']);

        return implode(';', $projectLineInfo) . PHP_EOL;
    }
}
