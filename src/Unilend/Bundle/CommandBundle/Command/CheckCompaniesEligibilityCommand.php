<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSetMember;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\Eligibility\Validator\CompanyValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\ExternalDataManager;

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

                $columnIndex = 0;
                $rowIndex    = 1;
                $activeSheet->setCellValueExplicitByColumnAndRow($columnIndex, $rowIndex, 'SIREN');
                $columnIndex++;
                foreach ($ruleSet as $rule) {
                    $activeSheet->setCellValueExplicitByColumnAndRow($columnIndex, $rowIndex, 'Règle ' . $rule->getIdRule()->getLabel());
                    $columnIndex++;
                }
                $activeSheet->setCellValueExplicitByColumnAndRow($columnIndex, $rowIndex, 'Résultat d\'éligibilité');
                $rowIndex++;

                foreach ($sirenList as $inputRow) {
                    $notEligibleRuleCount = 0;
                    $unavailableProvider  = 0;
                    $siren                = isset($inputRow[0]) ? $inputRow[0] : null;
                    if (1 !== preg_match('/^([0-9]{9})$/', $siren)) {
                        continue;
                    }

                    $columnIndex = 'A';
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $siren);
                    $columnIndex++;
                    /** @var  $rule ProjectEligibilityRuleSetMember */
                    foreach ($ruleSet as $rule) {
                        try {
                            $output->writeln('Checking rule ' . $rule->getIdRule()->getLabel() . ' on SIREN ' . $siren);

                            $result = $companyValidator->checkRule($rule->getIdRule()->getLabel(), $siren);
                            if (empty($result)) {
                                $style = $validStyle;
                                $value = $this->getCellValue($externalDataManager, $rule->getIdRule()->getLabel(), $siren);
                                $value = empty($value) ? 'OK' : $value;
                            } elseif (strstr(ProjectsStatus::UNEXPECTED_RESPONSE, $result[0])) {
                                $unavailableProvider++;
                                $style = $warningStyle;
                                $value = empty($value) ? 'Service indisponible' : $value;
                            } else {
                                $notEligibleRuleCount++;
                                $style = $errorStyle;
                                $value = $this->getCellValue($externalDataManager, $rule->getIdRule()->getLabel(), $siren);
                                $value = empty($value) ? 'KO' : $value;
                            }
                            $activeSheet->setCellValue($columnIndex . $rowIndex, $value);
                            $activeSheet->getStyle($columnIndex . $rowIndex)->applyFromArray($style);

                            $columnIndex++;
                        } catch (\Exception $exception) {
                            $activeSheet->setCellValue($columnIndex . $rowIndex, 'Erreur technique: ' . $exception->getMessage());
                            $activeSheet->getStyle($activeSheet->getActiveCell())->applyFromArray($errorStyle);
                            $columnIndex++;
                        }
                    }
                    if ($notEligibleRuleCount > 0) {
                        $eligibilityResult = 'NON';
                    } elseif ($unavailableProvider > 0) {
                        $eligibilityResult = 'Service indisponible';
                    } else {
                        $eligibilityResult = 'OUI';
                    }

                    $activeSheet->setCellValue($columnIndex . $rowIndex, $eligibilityResult);
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
}
