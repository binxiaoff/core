<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\core\Loader;

class FeedsFiscalStateCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('feeds:fiscal_state')
            ->setDescription('Generate the fiscal state file');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $numberFormatter = $this->getContainer()->get('number_formatter');
        $entityManager   = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        /** @var TaxType[] $frenchTaxes */
        $frenchTaxes = $entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->findBy(['country' => 'fr']);
        $taxRate     = [];
        foreach ($frenchTaxes as $tax) {
            $taxRate[$tax->getIdTaxType()] = $numberFormatter->format($tax->getRate());
        }
        $firstDayOfLastMonth = new \DateTime('first day of last month');
        $lastDayOfLastMonth  = new \DateTime('last day of last month');

        /*****TAX*****/
        $statutoryContributionsByContract            = $operationRepository
            ->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES, $firstDayOfLastMonth, $lastDayOfLastMonth, true);
        $regularisedStatutoryContributionsByContract = $operationRepository
            ->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES, $firstDayOfLastMonth, $lastDayOfLastMonth, true, true);
        $statutoryContributionsByContract            = array_combine(array_column($statutoryContributionsByContract, 'contract_label'), array_values($statutoryContributionsByContract));
        $regularisedStatutoryContributionsByContract = array_combine(array_column($regularisedStatutoryContributionsByContract, 'contract_label'),
            array_values($regularisedStatutoryContributionsByContract));

        $deductionAtSource            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_RETENUES_A_LA_SOURCE, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedDeductionAtSource = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_RETENUES_A_LA_SOURCE, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $csg            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CSG, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedCsg = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CSG, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $socialDeduction            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedSocialDeduction = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $additionalContribution            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedAdditionalContribution = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $solidarityDeduction            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedSolidarityDeduction = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $crds            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CRDS, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedCrds = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_CRDS, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $statutoryContributions            = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES, $firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedStatutoryContributions = $operationRepository->getTaxForFiscalState(OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES, $firstDayOfLastMonth, $lastDayOfLastMonth, false, true);

        $exemptedIncome            = $operationRepository->getExemptedIncomeTax($firstDayOfLastMonth, $lastDayOfLastMonth);
        $regularisedExemptedIncome = $operationRepository->getExemptedIncomeTax($firstDayOfLastMonth, $lastDayOfLastMonth, true);

        $statutoryContributionsTaxBDC     = $this->getTaxFromGroupedRawData($statutoryContributionsByContract, $regularisedStatutoryContributionsByContract,
            UnderlyingContract::CONTRACT_BDC);
        $statutoryContributionsTaxIFP     = $this->getTaxFromGroupedRawData($statutoryContributionsByContract, $regularisedStatutoryContributionsByContract,
            UnderlyingContract::CONTRACT_IFP);
        $statutoryContributionsTaxMiniBon = $this->getTaxFromGroupedRawData($statutoryContributionsByContract, $regularisedStatutoryContributionsByContract,
            UnderlyingContract::CONTRACT_MINIBON);
        $statutoryContributionsTax        = $this->getTaxFromRawData($statutoryContributions, $regularisedStatutoryContributions);
        $deductionAtSourceTax             = $this->getTaxFromRawData($deductionAtSource, $regularisedDeductionAtSource);
        $csgTax                           = $this->getTaxFromRawData($csg, $regularisedCsg);
        $socialDeductionTax               = $this->getTaxFromRawData($socialDeduction, $regularisedSocialDeduction);
        $additionalContributionTax        = $this->getTaxFromRawData($additionalContribution, $regularisedAdditionalContribution);
        $solidarityDeductionTax           = $this->getTaxFromRawData($solidarityDeduction, $regularisedSolidarityDeduction);
        $crdsTax                          = $this->getTaxFromRawData($crds, $regularisedCrds);
        $exemptedIncomeTax                = $this->getTaxFromRawData($exemptedIncome, $regularisedExemptedIncome);

        /***** Interests *****/
        $interestsRawData                       = $operationRepository->getInterestFiscalState($firstDayOfLastMonth, $lastDayOfLastMonth);
        $statutoryContributionsInterestsBDC     = 0;
        $statutoryContributionsInterestsIFP     = 0;
        $statutoryContributionsInterestsMiniBon = 0;
        $exemptedInterests                      = 0;
        $deductionAtSourceInterests             = 0;
        foreach ($interestsRawData as $row) {
            /** @var UnderlyingContract $contract */
            $contract = $entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContract')->find($row['id_type_contract']);
            if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'taxable' == $row['exemption_status']) {
                switch ($contract->getLabel()) {
                    case \underlying_contract::CONTRACT_BDC:
                        $statutoryContributionsInterestsBDC = $row['interests'];
                        break;
                    case \underlying_contract::CONTRACT_IFP:
                        $statutoryContributionsInterestsIFP = $row['interests'];
                        break;
                    case \underlying_contract::CONTRACT_MINIBON:
                        $statutoryContributionsInterestsMiniBon = $row['interests'];
                        break;
                }
            }

            if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'non_taxable' == $row['exemption_status']) {
                $exemptedInterests = round(bcadd($exemptedInterests, $row['interests'], 4), 2);
            }

            if ((('person' == $row['client_type'] && 'ww' == $row['fiscal_residence']) || 'legal_entity' == $row['client_type'])) {
                $deductionAtSourceInterests = round(bcadd($deductionAtSourceInterests, $row['interests'], 4), 2);
            }
        }

        $regularisedInterestsRawData = $operationRepository->getInterestFiscalState($firstDayOfLastMonth, $lastDayOfLastMonth, true);
        foreach ($regularisedInterestsRawData as $row) {
            /** @var UnderlyingContract $contract */
            $contract = $entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContract')->find($row['id_type_contract']);
            if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'taxable' == $row['exemption_status']) {
                switch ($contract->getLabel()) {
                    case \underlying_contract::CONTRACT_BDC:
                        $statutoryContributionsInterestsBDC = round(bcsub($statutoryContributionsInterestsBDC, $row['interests'], 4), 2);
                        break;
                    case \underlying_contract::CONTRACT_IFP:
                        $statutoryContributionsInterestsIFP = round(bcsub($statutoryContributionsInterestsIFP, $row['interests'], 4), 2);
                        break;
                    case \underlying_contract::CONTRACT_MINIBON:
                        $statutoryContributionsInterestsMiniBon = round(bcsub($statutoryContributionsInterestsMiniBon, $row['interests'], 4), 2);
                        break;
                }
            }

            if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'non_taxable' == $row['exemption_status']) {
                $exemptedInterests = round(bcsub($exemptedInterests, $row['interests'], 4), 2);
            }

            if ((('person' == $row['client_type'] && 'ww' == $row['fiscal_residence']) || 'legal_entity' == $row['client_type'])) {
                $deductionAtSourceInterests = round(bcsub($deductionAtSourceInterests, $row['interests'], 4), 2);
            }
        }

        $totalFrPhysicalPersonInterest = round(bcadd(bcadd(bcadd($statutoryContributionsInterestsBDC, $statutoryContributionsInterestsIFP, 4), $statutoryContributionsInterestsMiniBon, 4),
            $exemptedInterests, 4), 2);

        $table = '
                <style>
                    table th,table td{width:80px;height:20px;border:1px solid black;}
                    table td.dates{text-align:center;}
                    .right{text-align:right;}
                    .center{text-align:center;}
                    .boder-top{border-top:1px solid black;}
                    .boder-bottom{border-bottom:1px solid black;}
                    .boder-left{border-left:1px solid black;}
                    .boder-right{border-right:1px solid black;}
                </style>
        
                <table border="1" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 650px;">
                    <tr>
                        <th colspan="4">UNILEND</th>
                    </tr>
                    <tr>
                        <th style="background-color:#C9DAF2;">P&eacute;riode :</th>
                        <th style="background-color:#C9DAF2;">' . $firstDayOfLastMonth->format('d/m/Y') . '</th>
                        <th style="background-color:#C9DAF2;">au</th>
                        <th style="background-color:#C9DAF2;">' . $lastDayOfLastMonth->format('d/m/Y') . '</th>
                    </tr>
                    <tr>
                        <th style="background-color:#ECAEAE;" colspan="4">Pr&eacute;l&egrave;vements obligatoires</th>
                    </tr>
                    <tr>
                        <th>&nbsp;</th>
                        <th style="background-color:#F4F3DA;">Base (Int&eacute;r&ecirc;ts bruts)</th>
                        <th style="background-color:#F4F3DA;">Montant pr&eacute;l&egrave;vements</th>
                        <th style="background-color:#F4F3DA;">Taux</th>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Soumis au pr&eacute;l&egrave;vements (bons de caisse)</th> <!-- Somme des interets bruts pour : Personne physique, résident français, non exonéré, type loan : 1-->
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsInterestsBDC) . '</td>
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsTaxBDC) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS] . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Soumis au pr&eacute;l&egrave;vements (pr&ecirc;t IFP)</th> <!-- Somme des interets bruts pour : Personne physique, résident français, non exonéré, type loan : 2-->
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsInterestsIFP) . '</td>
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsTaxIFP) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS] . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Soumis au pr&eacute;l&egrave;vements (minibons)</th> <!-- Somme des interets bruts pour : Personne physique, résident français, non exonéré, type loan : minibons-->
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsInterestsMiniBon) . '</td>
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsTaxMiniBon) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS] . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Dispens&eacute;</th> <!-- Somme des interets bruts pour : Personne physique, résident français, exonéré, type loan : 1-->
                        <td class="right">' . $ficelle->formatNumber($exemptedInterests) . '</td>
                        <td class="right">' . $ficelle->formatNumber($exemptedIncomeTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber(0) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Total</th>
                        <td class="right">' . $ficelle->formatNumber($totalFrPhysicalPersonInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS] . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#ECAEAE;" colspan="4">Retenue &agrave; la source (bons de caisse et minibons)</th>
                    </tr>
                    <tr>
                        <th>&nbsp;</th>
                        <th style="background-color:#F4F3DA;">Base (Int&eacute;r&ecirc;ts bruts)</th>
                        <th style="background-color:#F4F3DA;">Montant retenues &agrave; la source</th>
                        <th style="background-color:#F4F3DA;">Taux</th>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Soumis &agrave; la retenue &agrave; la source</th> <!-- Somme des interets bruts pour : Personne morale résident français ou personne physique non résdent français, type loan : 2-->
                        <td class="right">' . $ficelle->formatNumber($deductionAtSourceInterests) . '</td>
                        <td class="right">' . $ficelle->formatNumber($deductionAtSourceTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE] . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#ECAEAE;" colspan="4">Pr&eacute;l&egrave;vements sociaux</th>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">CSG</th>
                        <td class="right">' . $ficelle->formatNumber($totalFrPhysicalPersonInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($csgTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_CSG] . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Pr&eacute;l&egrave;vement social</th>
                        <td class="right">' . $ficelle->formatNumber($totalFrPhysicalPersonInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($socialDeductionTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_SOCIAL_DEDUCTIONS] . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Contribution additionnelle</th>
                        <td class="right">' . $ficelle->formatNumber($totalFrPhysicalPersonInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($additionalContributionTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS] . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Pr&eacute;l&egrave;vements de solidarit&eacute;</th>
                        <td class="right">' . $ficelle->formatNumber($totalFrPhysicalPersonInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($solidarityDeductionTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_SOLIDARITY_DEDUCTIONS] . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">CRDS</th>
                        <td class="right">' . $ficelle->formatNumber($totalFrPhysicalPersonInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($crdsTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $taxRate[TaxType::TYPE_CRDS] . '%</td>
                    </tr>
                </table>';

        $filePath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_fiscal/Unilend_etat_fiscal_' . date('Ymd') . '.xls';
        file_put_contents($filePath, $table);

        /** @var Settings $recipientSetting */
        $recipientSetting = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneByType('Adresse notification etat fiscal');
        $url              = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $varMail          = ['$surl' => $url, '$url' => $url];

        /** @var TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-fiscal', $varMail, false);
        $message->setTo(explode(';', trim($recipientSetting->getValue())));
        $message->attach(\Swift_Attachment::fromPath($filePath));
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);

        $this->doTaxWalletsWithdrawals($lastDayOfLastMonth);
    }

    /**
     * @param \DateTime $lastDayOfLastMonth
     */
    private function doTaxWalletsWithdrawals(\DateTime $lastDayOfLastMonth)
    {
        $operationsManager = $this->getContainer()->get('unilend.service.operation_manager');
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger            = $this->getContainer()->get('monolog.logger.console');
        $totalTaxAmount    = 0;

        /** @var Wallet[] $taxWallets */
        $taxWallets = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getTaxWallets();
        foreach ($taxWallets as $wallet) {
            /** @var WalletBalanceHistory $lastMonthWalletHistory */
            $lastMonthWalletHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->getBalanceOfTheDay($wallet, $lastDayOfLastMonth);
            if (null === $lastMonthWalletHistory) {
                $logger->error('Could not get the wallet balance for ' . $wallet->getIdType()->getLabel(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
                continue;
            }
            $totalTaxAmount = round(bcadd($lastMonthWalletHistory->getAvailableBalance(), $totalTaxAmount, 4), 2);
            $operationsManager->withdrawTaxWallet($wallet, $lastMonthWalletHistory->getAvailableBalance());
        }
    }

    /**
     * @param array  $rawDataByContract
     * @param array  $regularisedRawDataByContract
     * @param string $contractType
     *
     * @return float|int
     */
    private function getTaxFromGroupedRawData($rawDataByContract, $regularisedRawDataByContract, $contractType)
    {
        $statutoryContributions = 0;
        if (isset($rawDataByContract[$contractType])) {
            $statutoryContributions = $rawDataByContract[$contractType]['tax'];
        }

        if (isset($regularisedRawDataByContract[$contractType])) {
            $statutoryContributions = round(bcsub($statutoryContributions, $regularisedRawDataByContract[$contractType]['tax'], 4), 2);
        }

        return $statutoryContributions;
    }

    private function getTaxFromRawData($rawDataByContract, $regularisedRawDataByContract)
    {
        $tax = 0;
        if (isset($rawDataByContract[0]['tax'])) {
            $tax = $rawDataByContract[0]['tax'];
        }
        if (isset($regularisedRawDataByContract[0]['tax'])) {
            $tax = round(bcsub($tax, $regularisedRawDataByContract[0]['tax'], 4), 2);
        }

        return $tax;
    }
}
