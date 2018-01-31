<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\PreScoring;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSetMember;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator\CompanyValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\ExternalDataManager;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerHermesCompanyRating;
use Unilend\Bundle\WSClientBundle\Service\AltaresManager;

class CheckCompaniesEligibilityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('check:company_eligibility')
            ->setDescription('Takes an Excel file with a list of SIREN, and for each one execute all current version risk rules');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $companyValidator        = $this->getContainer()->get('unilend.service.eligibility.company_validator');
        $externalDataManager     = $this->getContainer()->get('unilend.service.external_data_manager');
        $fileSystem              = $this->getContainer()->get('filesystem');
        $bulkCompanyCheckManager = $this->getContainer()->get('unilend.service.eligibility.bulk_company_check_manager');
        $slackManager            = $this->getContainer()->get('unilend.service.slack_manager');
        $messageProvider         = $this->getContainer()->get('unilend.swiftmailer.message_provider');
        $logger                  = $this->getContainer()->get('monolog.logger.console');
        $altaresManager          = $this->getContainer()->get('unilend.service.ws_client.altares_manager');

        $currentRiskPolicy = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSet')
            ->findOneBy(['status' => ProjectEligibilityRuleSet::STATUS_ACTIVE]);
        $ruleSet           = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectEligibilityRuleSetMember')
            ->findBy(['idRuleSet' => $currentRiskPolicy]);
        $now               = new \DateTime();
        $outputFilePath    = $bulkCompanyCheckManager->getEligibilityOutputDir() . $now->format('Y-m') . DIRECTORY_SEPARATOR;

        $validStyle   = [
            'font' => [
                'color' => ['rgb' => '407205']
            ]
        ];
        $warningStyle = [
            'fill' => [
                'type'  => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => 'EA9F00']
            ]
        ];
        $errorStyle   = [
            'font' => [
                'color' => ['rgb' => 'FF0707']
            ]
        ];

        foreach ($bulkCompanyCheckManager->getSirenListForEligibilityCheck() as $fileName => $sirenList) {
            $user = $bulkCompanyCheckManager->getUploadUser($fileName);

            if (null === $user) {
                $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_CRON);
            }

            try {
                $excel       = new \PHPExcel();
                $activeSheet = $excel->setActiveSheetIndex(0);

                $sirenColumn               = 'A';
                $preScoreColumn            = 'B';
                $companyCreationDateColumn = 'C';
                $riskRuleStartColumn       = 'D';
                $rowIndex                  = 1;

                $activeSheet->setCellValue($sirenColumn . $rowIndex, 'SIREN');
                $activeSheet->setCellValue($preScoreColumn . $rowIndex, 'Pre-Score');
                $activeSheet->setCellValue($companyCreationDateColumn . $rowIndex, 'Date création');

                foreach ($ruleSet as $rule) {
                    $activeSheet->setCellValue($riskRuleStartColumn . $rowIndex, 'Règle ' . $rule->getIdRule()->getLabel());
                    $riskRuleStartColumn++;
                }
                $activeSheet->setCellValue($riskRuleStartColumn . $rowIndex, 'Résultat d\'éligibilité');
                $rowIndex++;

                foreach ($sirenList as $inputRow) {
                    $notEligibleRuleCount = 0;
                    $unavailableProvider  = 0;
                    $siren                = isset($inputRow[0]) ? $inputRow[0] : null;

                    if (1 !== preg_match('/^([0-9]{9})$/', $siren)) {
                        continue;
                    }
                    $activeSheet->setCellValue($sirenColumn . $rowIndex, $siren);
                    /** @var  $rule ProjectEligibilityRuleSetMember */
                    $riskRuleStartColumn = 'D';

                    foreach ($ruleSet as $rule) {
                        try {
                            $output->writeln('Checking rule ' . $rule->getIdRule()->getLabel() . ' on SIREN ' . $siren);

                            $result = $companyValidator->checkRule($rule->getIdRule()->getLabel(), $siren);
                            if (empty($result)) {
                                $style = $validStyle;
                                $value = $this->getCellValue($externalDataManager, $rule->getIdRule()->getLabel(), $siren);
                                $value = '' === $value ? 'OK' : $value;
                            } elseif (strstr(ProjectsStatus::UNEXPECTED_RESPONSE, $result[0])) {
                                $unavailableProvider++;
                                $style = $warningStyle;
                                $value = 'Service indisponible';
                            } else {
                                $notEligibleRuleCount++;
                                $style = $errorStyle;
                                $value = $this->getCellValue($externalDataManager, $rule->getIdRule()->getLabel(), $siren);
                                $value = '' === $value ? 'KO' : $value;
                            }
                            $activeSheet->setCellValue($riskRuleStartColumn . $rowIndex, $value);
                            $activeSheet->getStyle($riskRuleStartColumn . $rowIndex)->applyFromArray($style);

                            $riskRuleStartColumn++;
                        } catch (\Exception $exception) {
                            $activeSheet->setCellValue($riskRuleStartColumn . $rowIndex, 'Erreur technique: ' . $exception->getMessage());
                            $activeSheet->getStyle($activeSheet->getActiveCell())->applyFromArray($errorStyle);
                            $riskRuleStartColumn++;
                        }
                    }
                    if ($notEligibleRuleCount > 0) {
                        $eligibilityResult = 'NON';
                    } elseif ($unavailableProvider > 0) {
                        $eligibilityResult = 'Service indisponible';
                    } else {
                        $eligibilityResult = 'OUI';
                    }
                    $activeSheet->setCellValue($companyCreationDateColumn . $rowIndex, $this->getCompanyCreationDate($siren, $altaresManager, $logger));
                    $activeSheet->setCellValue($preScoreColumn . $rowIndex, $this->getPreScoreValue($siren));
                    $activeSheet->setCellValue($riskRuleStartColumn . $rowIndex, $eligibilityResult);
                    $rowIndex++;
                }

                $fileInfo       = pathinfo($fileName);
                $outputFileName = $fileInfo['filename'] . '_output_' . $now->getTimestamp() . '.xlsx';

                if (false === is_dir($outputFilePath)) {
                    $fileSystem->mkdir($outputFilePath);
                }

                /** @var \PHPExcel_Writer_Excel2007 $writer */
                $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                $writer->save($outputFilePath . $outputFileName);
                $message = 'Le fichier: *' . $fileName . '* a bien été traité. Vous trouverez le détail dans le fichier de sortie: *' . $outputFileName . '*';
            } catch (\Exception $exception) {
                $logger->warning(
                    'Error while processing siren list of file : ' . $fileName . ' Error: ' . $exception->getMessage(),
                    ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
                $message = 'Une erreur s\'est produite lors du traitement du fichier : *' . $fileName . '*. Le fichier résultat n\'a pas été créé. Vous pouvez le déposer à nouveau pour le réévaluer';
            }
            if (false === empty($user->getSlack())) {
                $slackManager->sendMessage($message, $user->getSlack());
            }
            if (false === empty($user->getEmail())) {
                try {
                    $templateMessage = $messageProvider->newMessage('resultat-test-eligibilite-liste', ['details' => $message]);
                    $templateMessage->setTo($user->getEmail());
                    if (isset($outputFileName)) {
                        $templateMessage->attach(\Swift_Attachment::fromPath($outputFilePath . $outputFileName));
                    }
                    $mailer = $this->getContainer()->get('mailer');
                    $mailer->send($templateMessage);
                } catch (\Exception $exception) {
                    $logger->warning(
                        'Could not send email : resultat-test-eligibilite-liste - Exception: ' . $exception->getMessage(),
                        ['method' => __METHOD__, 'email address' => $user->getEmail(), 'user_email' => $user->getEmail(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                    );
                }
            }
        }
    }

    /**
     * @param ExternalDataManager $externalDataManager
     * @param string              $ruleLabel
     * @param string              $siren
     *
     * @return mixed
     */
    private function getCellValue(ExternalDataManager $externalDataManager, $ruleLabel, $siren)
    {
        switch (CompanyValidator::CHECK_RULE_METHODS[$ruleLabel]) {
            case 'checkAltaresScore':
                $score = $externalDataManager->getAltaresScore($siren);
                return null !== $score ? $score->getScore20() : '';
            case 'checkEulerHermesTrafficLight':
                $trafficLight = $externalDataManager->getEulerHermesTrafficLight($siren);
                return null !== $trafficLight ? $trafficLight->getColor() : '';
            case 'checkInfolegaleScore':
                $score = $externalDataManager->getInfolegaleScore($siren);
                return null !== $score ? $score->getScore() : '';
            case 'checkEulerHermesGrade':
                $grade = $externalDataManager->getEulerHermesGrade($siren);
                return null !== $grade ? $grade->getGrade() : '';
            default:
                return '';
        }
    }

    /**
     * @param string $siren
     *
     * @return int|null
     */
    private function getPreScoreValue(string $siren)
    {
        $externalDataManager = $this->getContainer()->get('unilend.service.external_data_manager');
        $entityManager       = $this->getContainer()->get('doctrine.orm.entity_manager');

        $preScoring       = null;
        $altaresScore     = $externalDataManager->getAltaresScore($siren);
        $infolegaleScore  = $externalDataManager->getInfolegaleScore($siren);
        $eulerHermesGrade = $externalDataManager->getEulerHermesGrade($siren);

        if (null === $eulerHermesGrade && EulerHermesCompanyRating::COLOR_WHITE === $externalDataManager->getEulerHermesTrafficLight($siren)) {
            $eulerHermesGrade = new EulerHermesCompanyRating();
            $eulerHermesGrade->setGrade(EulerHermesCompanyRating::GRADE_UNKNOWN);
        }

        if (false === in_array(null, [$altaresScore, $infolegaleScore, $eulerHermesGrade], true)) {
            /** @var PreScoring $preScoringEntity */
            $preScoringEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:PreScoring')->findOneBy([
                'altares'          => $altaresScore->getScore20(),
                'infolegale'       => $infolegaleScore->getScore(),
                'eulerHermesGrade' => $eulerHermesGrade->getGrade()
            ]);

            if (null !== $preScoringEntity) {
                $preScoring = $preScoringEntity->getNote();
            }
        }

        return $preScoring;
    }

    /**
     * @param string          $siren
     * @param AltaresManager  $altaresManager
     * @param LoggerInterface $logger
     *
     * @return string
     */
    private function getCompanyCreationDate(string $siren, AltaresManager $altaresManager, LoggerInterface $logger) : string
    {
        try {
            $companyIdentity = $altaresManager->getCompanyIdentity($siren);
            if (null !== $companyIdentity && $companyIdentity->getCreationDate() instanceof \DateTime) {
                return $companyIdentity->getCreationDate()->format('d/m/Y');
            }
        } catch (\Exception $exception) {
            $logger->warning(
                'Could not get company creation date from Altares Error: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        return '';
    }
}
