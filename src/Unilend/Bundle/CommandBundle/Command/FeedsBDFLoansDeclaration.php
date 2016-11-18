<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\core\Loader;

class FeedsBDFLoansDeclaration extends ContainerAwareCommand
{
    const DECLARATION_FILE_PATH = 'bdf/emissions/declarations_mensuelles/';
    const UNILEND_IFP_ID        = 'IF010';
    const RECORD_PAD_LENGTH     = 151;
    const SEQUENCE_PAD_LENGTH   = 6;
    const PADDING_CHAR          = ' ';
    const PADDING_NUMBER        = '0';

    /** @var  int $recordLineNumber */
    private $recordLineNumber = 1;
    /** @var  int $recordCode */
    private $code;
    /** @var  \DateTime $declarationDate */
    private $declarationDate;
    /** @var string $sequentialNumber */
    private $sequentialNumber;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('feeds:bdf_loans_declaration')
            ->setDescription('Generate the loans declaration txt file to send to Banque De France')
            ->addArgument(
                'year',
                InputArgument::OPTIONAL,
                'Year of the declaration to regenerate(format [0-9]{4})'
            )
            ->addArgument(
                'month',
                InputArgument::OPTIONAL,
                'Month of the declaration to regenerate(format: [0-2]{2})'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \transmission_sequence $transmissionSequence */
        $transmissionSequence = $entityManager->getRepository('transmission_sequence');

        $year  = $input->getArgument('year');
        $month = $input->getArgument('month');


        if ($year && $month) {
            if (preg_match('/[0-2]{2}/', $month, $matches) && preg_match('/[0-9]{4}/', $year, $matches)) {
                $this->declarationDate = (new \DateTime())->setDate((int) $year, (int) $month, date('d'));
            } else {
                $output->writeln('<error>Wrong date format)</error>');
                return;
            }
        } else {
            $this->declarationDate = (new \DateTime())->setTimestamp(time());
        }

        /** @var array */
        $data = $project->getDataForBDFDeclaration((new \DateTime())->setDate($this->declarationDate->format('Y'), $this->declarationDate->format('m'), 1));

        if (true === empty($data)) {
            $logger->debug('no data found', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            return;
        }
        $fileName = self::UNILEND_IFP_ID . '_' . $this->declarationDate->format('Ym') . '.txt';

        if ($transmissionSequence->get($fileName, 'element_name') && file_exists($this->getContainer()->getParameter('path.sftp') . self::DECLARATION_FILE_PATH . $fileName)) {
            $currentName = pathinfo($this->getContainer()->getParameter('path.sftp') . self::DECLARATION_FILE_PATH . $fileName, PATHINFO_FILENAME);
            rename($this->getContainer()->getParameter('path.sftp') . self::DECLARATION_FILE_PATH . $fileName,
                $this->getContainer()->getParameter('path.sftp') . self::DECLARATION_FILE_PATH . 'archives/' . $currentName . '_' . $transmissionSequence->sequence . '.txt');
        }

        /** @var resource $file */
        if ($file = fopen($this->getContainer()->getParameter('path.sftp') . self::DECLARATION_FILE_PATH . $fileName, 'a')) {
            try {
                fwrite($file, $this->getStartSenderRecord($transmissionSequence, $fileName));
                fwrite($file, $this->getStartDeclarerRecord());

                foreach ($data as $row) {
                    fwrite($file, $this->getLoanRecord($row));
                }
                fwrite($file, $this->getEndDeclarerRecord());
                fwrite($file, $this->getEndSenderRecord());
                fclose($file);
            } catch (\Exception $exception) {
                $logger->error('An exception occured when writing the loan lines with message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
                $transmissionSequence->resetToPreviousSequence($fileName);
                fclose($file);
                unlink($this->getContainer()->getParameter('path.sftp') . self::DECLARATION_FILE_PATH . $fileName);
            }
        } else {
            $logger->error('Could not create the file ' . self::DECLARATION_FILE_PATH . $fileName, ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }
    }

    /**
     * Set the line number sequence
     */
    private function setSequentialNumber()
    {
        $this->sequentialNumber = str_pad($this->recordLineNumber, self::SEQUENCE_PAD_LENGTH, self::PADDING_NUMBER, STR_PAD_LEFT);
        $this->recordLineNumber++;
    }

    /**
     * @return string
     */
    public function getStartingRecord()
    {
        $this->setSequentialNumber();

        return $this->sequentialNumber . $this->code . $this->declarationDate->format('Ym') . self::UNILEND_IFP_ID;
    }

    /**
     * @param \transmission_sequence $transmissionSequence
     * @param string $fileName
     * @return string
     */
    private function getStartSenderRecord(\transmission_sequence $transmissionSequence, $fileName)
    {
        $this->code = '01';
        $sequence   = $transmissionSequence->getNextSequence($fileName);
        return str_pad($this->getStartingRecord() . str_pad($sequence, 2, self::PADDING_NUMBER, STR_PAD_LEFT), self::RECORD_PAD_LENGTH, self::PADDING_CHAR, STR_PAD_RIGHT) . PHP_EOL;
    }

    /**
     * @return string
     */
    private function getStartDeclarerRecord()
    {
        $this->code = '02';
        return str_pad($this->getStartingRecord(), self::RECORD_PAD_LENGTH, self::PADDING_CHAR, STR_PAD_RIGHT) . PHP_EOL;
    }

    /**
     * @param array $projectData
     * @return string
     */
    private function getLoanRecord(array $projectData)
    {

        $this->code = '11';
        return $this->multiBytePad($this->getStartingRecord() . $this->getProjectInformation($projectData), self::RECORD_PAD_LENGTH, self::PADDING_CHAR, STR_PAD_RIGHT) . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getEndDeclarerRecord()
    {
        $this->code    = '92';
        $recordCounter = str_pad($this->recordLineNumber - 1, self::SEQUENCE_PAD_LENGTH, self::PADDING_NUMBER, STR_PAD_LEFT);
        return str_pad($this->getStartingRecord() . $recordCounter, self::RECORD_PAD_LENGTH, self::PADDING_CHAR, STR_PAD_RIGHT) . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getEndSenderRecord()
    {
        $this->code    = '93';
        $recordCounter = str_pad($this->recordLineNumber, self::SEQUENCE_PAD_LENGTH, self::PADDING_NUMBER, STR_PAD_LEFT);
        return str_pad($this->getStartingRecord() . $recordCounter, self::RECORD_PAD_LENGTH, self::PADDING_CHAR, STR_PAD_RIGHT);
    }

    /**
     * @param array $data
     * @return string
     */
    private function getProjectInformation(array $data)
    {
        $projectLineInfo = $this->checkSiren($data['siren']);
        $projectLineInfo .= $this->checkName($data['name']);
        $projectLineInfo .= $this->checkLoanType($data['loan_type']);
        $projectLineInfo .= $this->checkAmounts($data['loan_amount']);
        $projectLineInfo .= \DateTime::createFromFormat('Y-m-d H:i:s', $data['loan_date'])->format('Ymd');
        $projectLineInfo .= $this->checkLoanPeriod($data['loan_duration']);
        $projectLineInfo .= $this->checkLoanAvgRate($data['average_loan_rate']);
        $projectLineInfo .= $data['repayment_frequency'];
        $projectLineInfo .= $this->checkAmounts($data['owed_capital']);
        $projectLineInfo .= $this->checkAmounts($data['unpaid_amount']);
        $projectLineInfo .= $this->checkUnpaidDate($data['unpaid_date']);
        $projectLineInfo .= $this->checkLoanContributorNumber($data['contributor_person_number'], 'person');
        $projectLineInfo .= $this->checkLoanContributorPercentage($data['contributor_person_percentage']);
        $projectLineInfo .= $this->checkLoanContributorNumber($data['contributor_legal_entity_number'], 'legal_entity');
        $projectLineInfo .= $this->checkLoanContributorPercentage($data['contributor_legal_entity_percentage']);
        $projectLineInfo .= $this->checkLoanContributorNumber($data['contributor_credit_institution_number'], 'credit_institution');
        $projectLineInfo .= $this->checkLoanContributorPercentage($data['contributor_credit_institution_percentage']);

        return $projectLineInfo;
    }

    /**
     * @param string $siren
     * @return string
     * @throws \Exception
     */
    private function checkSiren($siren)
    {
        $siren = trim($siren);

        if (strlen($siren) > 9) {
            throw new \Exception('Siren too long: ' . $siren);
        }
        return str_pad($siren, 9, self::PADDING_CHAR, STR_PAD_RIGHT);
    }

    /**
     * @param string $name
     * @return string
     */
    private function checkName($name)
    {
        return $this->multiBytePad(trim($name), 60, self::PADDING_CHAR, STR_PAD_RIGHT);
    }

    /**
     * @param string $loanType
     * @return string mixed
     * @throws \Exception
     */
    private function checkLoanType($loanType)
    {
        if (false === in_array($loanType, ['MA', 'IM', 'ST', 'CO', 'CL', 'EX', 'AU'])) {
            throw new \Exception('Unknown loan type: ' . $loanType);
        }
        return $loanType;
    }

    /**
     * @param float $amount
     * @return string
     * @throws \Exception
     */
    private function checkAmounts($amount)
    {
        $amount = round($amount / 1000, 0);
        if (strlen($amount) > 5) {
            throw new \Exception('Amount too big: ' . $amount);
        }
        return str_pad($amount, 5, self::PADDING_NUMBER, STR_PAD_LEFT);
    }

    /**
     * @param int $loanPeriod
     * @return string
     * @throws \Exception
     */
    private function checkLoanPeriod($loanPeriod)
    {
        if (strlen($loanPeriod) > 3) {
            throw new \Exception('Project duration too long: ' . $loanPeriod);
        }
        return str_pad($loanPeriod, 3, self::PADDING_NUMBER, STR_PAD_LEFT);
    }

    /**
     * @param float $loanAvgRate
     * @return string
     * @throws \Exception
     */
    private function checkLoanAvgRate($loanAvgRate)
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
     * @param string $date
     * @return string
     */
    private function checkUnpaidDate($date)
    {
        if (true === empty($date)) {
            return '00000000';
        } else {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $date)->format('Ymd');
        }
    }

    /**
     * @param int $number
     * @param string $type
     * @return string
     * @throws \Exception
     */
    private function checkLoanContributorNumber($number, $type)
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
     * @param float $percentage
     * @return string
     * @throws \Exception
     */
    private function checkLoanContributorPercentage($percentage)
    {
        if ($percentage > 100) {
            throw new \Exception('wrong contributor percentage : ' . $percentage);
        }
        return str_pad(round($percentage, 0), 3, self::PADDING_NUMBER, STR_PAD_LEFT);
    }

    /**
     * @param string $input
     * @param int $padLength
     * @param string $padString
     * @param int $padType
     * @param string $encoding
     * @return string
     */
    function multiBytePad($input, $padLength, $padString = ' ', $padType = STR_PAD_RIGHT, $encoding = 'UTF-8')
    {
        $mbDiff = strlen($input) - mb_strlen($input, $encoding);
        return str_pad($input, $padLength + $mbDiff, $padString, $padType);
    }
}
