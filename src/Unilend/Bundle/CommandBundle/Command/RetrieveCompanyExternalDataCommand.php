<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Box\Spout\{
    Common\Type, Writer\CSV\Writer, Writer\WriterFactory
};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputInterface, Output\OutputInterface
};
use Symfony\Component\Filesystem\Filesystem;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\{
    BulkCompanyCheckManager, Eligibility\Validator\CompanyValidator, ProjectRequestManager, SlackManager
};
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager;

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $companyValidator        = $this->getContainer()->get('unilend.service.eligibility.company_validator');
        $fileSystem              = $this->getContainer()->get('filesystem');
        $bulkCompanyCheckManager = $this->getContainer()->get('unilend.service.eligibility.bulk_company_check_manager');
        $projectRequestManager   = $this->getContainer()->get('unilend.service.project_request_manager');
        $slackManager            = $this->getContainer()->get('unilend.service.slack_manager');
        $messageProvider         = $this->getContainer()->get('unilend.swiftmailer.message_provider');
        $altaresManager          = $this->getContainer()->get('unilend.service.ws_client.altares_manager');

        $now            = new \DateTime();
        $outputFilePath = $bulkCompanyCheckManager->getCompanyDataOutputDir() . $now->format('Y-m') . DIRECTORY_SEPARATOR;

        foreach ($bulkCompanyCheckManager->getSirenListForCompanyDataRetrieval() as $fileName => $sirenList) {
            $outputFileName = $this->createFile($fileName, $sirenList, $outputFilePath, $fileSystem, $projectRequestManager, $altaresManager, $companyValidator);
            $this->sendNotifications($fileName, $outputFileName, $outputFilePath, $bulkCompanyCheckManager, $fileSystem, $slackManager, $messageProvider);
        }
    }

    /**
     * @param string                $fileName
     * @param array                 $sirenList
     * @param string                $outputFilePath
     * @param Filesystem            $fileSystem
     * @param ProjectRequestManager $projectRequestManager
     * @param AltaresManager        $altaresManager
     * @param CompanyValidator      $companyValidator
     *
     * @return string
     */
    private function createFile(
        string $fileName,
        array $sirenList,
        string $outputFilePath,
        Filesystem $fileSystem,
        ProjectRequestManager $projectRequestManager,
        AltaresManager $altaresManager,
        CompanyValidator $companyValidator
    ): string
    {
        $outputFileName = '';
        $rowData        = [];

        try {
            foreach ($sirenList as $inputRow) {
                $siren = empty($inputRow[0]) ? '' : $inputRow[0];

                if (false === $projectRequestManager->validateSiren($siren)) {
                    continue;
                }

                $altaresIdentity = $altaresManager->getCompanyIdentity($siren);

                if (null !== $altaresIdentity) {
                    $altaresScore = $altaresManager->getScore($siren);
                    $codinfResult = $companyValidator->checkRule(self::CODINF_PAYMENT_INCIDENT_RULE, $siren);

                    if (empty($codinfResult)) {
                        $codinfIncident = 'OK';
                    } elseif (strstr(ProjectsStatus::UNEXPECTED_RESPONSE, $codinfResult[0])) {
                        $codinfIncident = 'Service indisponible';
                    } else {
                        $codinfIncident = 'KO';
                    }
                    $rowData[] = [
                        $siren,
                        $altaresIdentity->getCompanyStatusLabel(),
                        true === $altaresIdentity->getCollectiveProcedure() ? 'OUI' : 'NON',
                        ($altaresIdentity->getCreationDate() instanceof \DateTime) ? $altaresIdentity->getCreationDate()->format('d/m/Y') : '',
                        ($altaresIdentity->getLastPublishedBalanceDate() instanceof \DateTime) ? $altaresIdentity->getLastPublishedBalanceDate()->format('d/m/Y') : '',
                        $altaresIdentity->getTurnover(),
                        $altaresIdentity->getNAFCode(),
                        $altaresIdentity->getNAFCodeLabel(),
                        $altaresIdentity->getWorkforceSlice(),
                        $altaresScore ? $altaresScore->getScore20() : '',
                        ($altaresScore && $altaresScore->getScoreDate() instanceof \DateTime) ? $altaresScore->getScoreDate()->format('d/m/Y') : '',
                        $codinfIncident
                    ];
                }
            }

            $fileInfo       = pathinfo($fileName);
            $outputFileName = $fileInfo['filename'] . '_output_' . time() . '.csv';

            if (false === is_dir($outputFilePath)) {
                $fileSystem->mkdir($outputFilePath);
            }

            /** @var Writer $writer */
            $writer = WriterFactory::create(Type::CSV);
            $writer->setFieldDelimiter(';')
                ->openToFile($outputFilePath . $outputFileName)
                ->addRow(self::CSV_HEADER)
                ->addRows($rowData)
                ->close();
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->warning(
                'Error while processing siren list of file : ' . $fileName . ' Error: ' . $exception->getMessage(), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                    'file'     => $exception->getFile(),
                    'line'     => $exception->getLine()
                ]
            );
        }

        return $outputFileName;
    }

    /**
     * @param string                  $inputFileName
     * @param string                  $outputFileName
     * @param string                  $outputFilePath
     * @param BulkCompanyCheckManager $bulkCompanyCheckManager
     * @param Filesystem              $fileSystem
     * @param SlackManager            $slackManager
     * @param TemplateMessageProvider $messageProvider
     */
    private function sendNotifications(
        string $inputFileName,
        string $outputFileName,
        string $outputFilePath,
        BulkCompanyCheckManager $bulkCompanyCheckManager,
        Filesystem $fileSystem,
        SlackManager $slackManager,
        TemplateMessageProvider $messageProvider
    ): void
    {
        if ('' !== $outputFileName) {
            $message = 'Le fichier: ' . $inputFileName . ' a bien été traité. Vous trouverez le détail dans le fichier de sortie: ' . $outputFileName;
        } else {
            $message = 'Une erreur s\'est produite lors du traitement du fichier : ' . $inputFileName . '. Le fichier résultat n\'a pas été créé. Vous pouvez le déposer à nouveau pour le réessayer';
        }

        $user = $bulkCompanyCheckManager->getUploadUser($inputFileName);

        if ($user && false === empty($user->getSlack())) {
            $slackManager->sendMessage($message, $user->getSlack());
        }
        if ($user && false === empty($user->getEmail())) {
            try {
                $templateMessage = $messageProvider->newMessage('recuperation-donnees-externes-entreprise', ['details' => $message]);
                $templateMessage->setTo($user->getEmail());

                if (isset($outputFileName) && $fileSystem->exists($outputFilePath . $outputFileName)) {
                    $attachment = \Swift_Attachment::fromPath($outputFilePath . $outputFileName);
                    $templateMessage->attach($attachment);
                } else {
                    throw new \Exception('Could not attach the result file. Output file name was not set. Original file name: ' . $inputFileName);
                }
                $mailer = $this->getContainer()->get('mailer');
                $mailer->send($templateMessage);
            } catch (\Exception $exception) {
                $this->getContainer()->get('monolog.logger.console')->warning(
                    'Could not send email : recuperation-donnees-externes-entreprise - Exception: ' . $exception->getMessage(), [
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
