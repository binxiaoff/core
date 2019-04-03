<?php

namespace Unilend\Command;

use Box\Spout\{Common\Type, Writer\CSV\Writer, Writer\WriterFactory};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};
use Symfony\Component\Filesystem\Filesystem;
use Unilend\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\{BulkCompanyCheckManager};

class RetrieveCompanyExternalDataCommand extends ContainerAwareCommand
{
    const CODINF_PAYMENT_INCIDENT_RULE = 'TC-RISK-005';
    const CSV_HEADER                   = [
        'SIREN',
        'Etat entreprise',
        'Procédure collective',
        'Date de création',
        'Date dernier bilan publié',
        'Chiffre d\'affaire',
        'Code NAF',
        'Libellé Code NAF',
        'Tranche d\'effectif entreprise',
        'Score altares 20',
        'Date du score',
        'Impayés Codinf'
    ];

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('unilend:external_risk_data:retrieve_company_data')
            ->setDescription('Takes an Excel file with a list of SIREN, and for each one retrieves external data from selected WS Providers');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileSystem              = $this->getContainer()->get('filesystem');
        $bulkCompanyCheckManager = $this->getContainer()->get('unilend.service.eligibility.bulk_company_check_manager');
        $outputFilePath          = $bulkCompanyCheckManager->getCompanyDataOutputDir() . date('Y-m') . DIRECTORY_SEPARATOR;

        foreach ($bulkCompanyCheckManager->getSirenListForCompanyDataRetrieval() as $fileName => $sirenList) {
            $outputFileName = $this->createFile($fileName, $sirenList, $outputFilePath, $fileSystem);
            $this->sendNotifications($fileName, $outputFileName, $outputFilePath, $bulkCompanyCheckManager, $fileSystem);
        }
    }

    /**
     * @param string     $fileName
     * @param array      $sirenList
     * @param string     $outputFilePath
     * @param Filesystem $fileSystem
     *
     * @return string|null
     */
    private function createFile(string $fileName, array $sirenList, string $outputFilePath, Filesystem $fileSystem): ?string
    {
        $rowData               = [];
        $projectRequestManager = $this->getContainer()->get('unilend.service.project_request_manager');
        $altaresManager        = $this->getContainer()->get('unilend.service.ws_client.altares_manager');
        $companyValidator      = $this->getContainer()->get('unilend.service.eligibility.company_validator');

        try {
            foreach ($sirenList as $inputRow) {
                $siren = empty($inputRow[0]) ? '' : $inputRow[0];

                if (false === $projectRequestManager->validateSiren($siren)) {
                    continue;
                }

                $altaresIdentity = $altaresManager->getCompanyIdentity($siren);

                if (null !== $altaresIdentity) {
                    $altaresScore = $altaresManager->getScore($siren);
                    $codinfCheck  = $companyValidator->checkRule(self::CODINF_PAYMENT_INCIDENT_RULE, $siren);

                    if (empty($codinfCheck)) {
                        $codinfIncident = 'OK';
                    } elseif (strstr(ProjectsStatus::UNEXPECTED_RESPONSE, $codinfCheck[0])) {
                        $codinfIncident = 'Service indisponible';
                    } else {
                        $codinfIncident = 'KO';
                    }

                    $rowData[] = [
                        $siren,
                        $altaresIdentity->getCompanyStatusLabel(),
                        $altaresIdentity->getCollectiveProcedure() ? 'OUI' : 'NON',
                        $altaresIdentity->getCreationDate() instanceof \DateTime ? $altaresIdentity->getCreationDate()->format('d/m/Y') : '',
                        $altaresIdentity->getLastPublishedBalanceDate() instanceof \DateTime ? $altaresIdentity->getLastPublishedBalanceDate()->format('d/m/Y') : '',
                        $altaresIdentity->getTurnover(),
                        $altaresIdentity->getNAFCode(),
                        $altaresIdentity->getNAFCodeLabel(),
                        $altaresIdentity->getWorkforceSlice(),
                        $altaresScore ? $altaresScore->getScore20() : '',
                        $altaresScore && $altaresScore->getScoreDate() instanceof \DateTime ? $altaresScore->getScoreDate()->format('d/m/Y') : '',
                        $codinfIncident
                    ];
                }
            }

            $outputFileName = 'Donnees-externes-siren_' . date('Ymd_His') . '.csv';

            if (false === is_dir($outputFilePath)) {
                $fileSystem->mkdir($outputFilePath);
            }

            /** @var Writer $writer */
            $writer = WriterFactory::create(Type::CSV);
            $writer
                ->setFieldDelimiter(';')
                ->openToFile($outputFilePath . $outputFileName)
                ->addRow(self::CSV_HEADER)
                ->addRows($rowData)
                ->close();

            return $outputFileName;
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->warning(
                'Error while processing SIREN list of file "' . $fileName . '". Error: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);

            return null;
        }
    }

    /**
     * @param string                  $inputFileName
     * @param string|null             $outputFileName
     * @param string                  $outputFilePath
     * @param BulkCompanyCheckManager $bulkCompanyCheckManager
     * @param Filesystem              $fileSystem
     */
    private function sendNotifications(string $inputFileName, ?string $outputFileName, string $outputFilePath, BulkCompanyCheckManager $bulkCompanyCheckManager, Filesystem $fileSystem): void
    {
        if ($outputFileName) {
            $message = 'Le fichier "' . $inputFileName . '" a bien été traité. Vous trouverez le détail dans le fichier de sortie "' . $outputFileName . '"';
        } else {
            $message = 'Une erreur s\'est produite lors du traitement du fichier : ' . $inputFileName . '. Le fichier résultat n\'a pas été créé.';
        }

        $user = $bulkCompanyCheckManager->getUploadUser($inputFileName);

        if ($user) {
            if (false === empty($user->getSlack())) {
                $slackManager = $this->getContainer()->get('unilend.service.slack_manager');
                $slackManager->sendMessage($message, $user->getSlack());
            }

            if (false === empty($user->getEmail())) {
                try {
                    $messageProvider = $this->getContainer()->get('unilend.swiftmailer.message_provider');
                    $templateMessage = $messageProvider->newMessage('recuperation-donnees-externes-entreprise', ['details' => $message]);
                    $templateMessage->setTo($user->getEmail());

                    if ($outputFileName && $fileSystem->exists($outputFilePath . $outputFileName)) {
                        $attachment = \Swift_Attachment::fromPath($outputFilePath . $outputFileName);
                        $templateMessage->attach($attachment);
                    } elseif ($outputFileName) {
                        throw new \Exception('Could not attach the result file. Output file name was not set. Original file name: ' . $inputFileName);
                    }

                    $mailer = $this->getContainer()->get('mailer');
                    $mailer->send($templateMessage);
                } catch (\Exception $exception) {
                    $this->getContainer()->get('monolog.logger.console')->warning(
                        'Could not send email "recuperation-donnees-externes-entreprise". Exception: ' . $exception->getMessage(), [
                        'input_file_name' => $inputFileName,
                        'email_address'   => $user->getEmail(),
                        'user_email'      => $user->getEmail(),
                        'class'           => __CLASS__,
                        'function'        => __FUNCTION__,
                        'file'            => $exception->getFile(),
                        'line'            => $exception->getLine()
                    ]);
                }
            }
        }
    }
}
