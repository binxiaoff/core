<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
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
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var EntityManagerSimulator $entityManangerSimulator */
        $entityManangerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \tax_type $taxType */
        $taxType = $entityManangerSimulator->getRepository('tax_type');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $taxRate             = $taxType->getTaxRateByCountry('fr', [1]);
        $firstDayOfLastMonth = new \DateTime('first day of last month');
        $lastDayOfLastMonth  = new \DateTime('last day of last month');
        $data                = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getInterestAndTaxForFiscalState($firstDayOfLastMonth, $lastDayOfLastMonth);

        if (false === empty($data)) {
            $exemptedInterests             = 0;
            $exemptedIncomeTax             = 0;
            $deductionAtSourceTax          = 0;
            $deductionAtSourceInterests    = 0;
            $interestsBDC                  = 0;
            $statutoryContributionsBDC     = 0;
            $interestsIFP                  = 0;
            $statutoryContributionsIFP     = 0;
            $interestsMiniBon              = 0;
            $statutoryContributionsMiniBon = 0;

            foreach ($data as $row) {
                /** @var UnderlyingContract $contract */
                $contract = $entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContract')->find($row['id_type_contract']);
                if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'taxable' == $row['exemption_status']) {
                    switch ($contract->getLabel()) {
                        case \underlying_contract::CONTRACT_BDC:
                            $interestsBDC = $row['interests'];
                            $statutoryContributionsBDC = $row[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS];
                            break;
                        case \underlying_contract::CONTRACT_IFP:
                            $interestsIFP = $row['interests'];
                            $statutoryContributionsIFP = $row[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS];
                            break;
                        case \underlying_contract::CONTRACT_MINIBON:
                            $interestsMiniBon = $row['interests'];
                            $statutoryContributionsMiniBon = $row[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS];
                            break;
                    }
                }

                if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'non_taxable' == $row['exemption_status']) {
                    $exemptedInterests = bcadd($exemptedInterests, $row['interests'], 2);
                    $exemptedIncomeTax = bcadd($exemptedIncomeTax, $row[OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS], 2);
                }

                if ((('person' == $row['client_type'] && 'ww' == $row['fiscal_residence']) || 'legal_entity' == $row['client_type'])) {
                    $deductionAtSourceInterests = bcadd($deductionAtSourceInterests, $row['interests'], 2);

                    if ($contract->getLabel() != \underlying_contract::CONTRACT_IFP) {
                        $deductionAtSourceTax = bcadd($deductionAtSourceTax, $row[OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE], 2);
                    }
                }
            }
            $csg                    = array_sum(array_column($data, OperationType::TAX_FR_CSG));
            $socialDeduction        = array_sum(array_column($data, OperationType::TAX_FR_SOCIAL_DEDUCTIONS));
            $additionalContribution = array_sum(array_column($data, OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS));
            $solidarityDeduction    = array_sum(array_column($data, OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS));
            $crds                   = array_sum(array_column($data, OperationType::TAX_FR_CRDS));
            $totalInterest          = bcadd(bcadd(bcadd($interestsBDC, $interestsIFP, 2), $interestsMiniBon, 2), $exemptedInterests, 2);
            $statutoryContributions = bcadd(bcadd(bcadd($statutoryContributionsBDC, $statutoryContributionsIFP, 2), $statutoryContributionsMiniBon, 2), $exemptedIncomeTax, 2);

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
                        <td class="right">' . $ficelle->formatNumber($interestsBDC) . '</td>
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsBDC) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Soumis au pr&eacute;l&egrave;vements (pr&ecirc;t IFP)</th> <!-- Somme des interets bruts pour : Personne physique, résident français, non exonéré, type loan : 2-->
                        <td class="right">' . $ficelle->formatNumber($interestsIFP) . '</td>
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsIFP) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Soumis au pr&eacute;l&egrave;vements (minibons)</th> <!-- Somme des interets bruts pour : Personne physique, résident français, non exonéré, type loan : minibons-->
                        <td class="right">' . $ficelle->formatNumber($interestsMiniBon) . '</td>
                        <td class="right">' . $ficelle->formatNumber($statutoryContributionsMiniBon) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Dispens&eacute;</th> <!-- Somme des interets bruts pour : Personne physique, résident français, exonéré, type loan : 1-->
                        <td class="right">' . $ficelle->formatNumber($exemptedInterests) . '</td>
                        <td class="right">' . $ficelle->formatNumber($exemptedIncomeTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber(0) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Total</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($statutoryContributions) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_STATUTORY_CONTRIBUTIONS]) . '%</td>
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
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#ECAEAE;" colspan="4">Pr&eacute;l&egrave;vements sociaux</th>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">CSG</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($csg) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_CSG]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Pr&eacute;l&egrave;vement social</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($socialDeduction) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_SOCIAL_DEDUCTIONS]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Contribution additionnelle</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($additionalContribution) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Pr&eacute;l&egrave;vements de solidarit&eacute;</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($solidarityDeduction) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_SOLIDARITY_DEDUCTIONS]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">CRDS</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($crds) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[TaxType::TYPE_CRDS]) . '%</td>
                    </tr>
                </table>';

            $filePath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_fiscal/Unilend_etat_fiscal_' . date('Ymd') . '.xls';
            file_put_contents($filePath, $table);

            /** @var Settings $recipientSetting */
            $recipientSetting = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneByType('Adresse notification etat fiscal');
            $url     = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
            $varMail = ['$surl' => $url, '$url' => $url];

            /** @var TemplateMessage $message */
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-fiscal', $varMail, false);
            $message->setTo(explode(';', trim($recipientSetting->getValue())));
            $message->attach(\Swift_Attachment::fromPath($filePath));
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);

            $this->doTaxWalletsWithdrawals($lastDayOfLastMonth);
        }
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
            $totalTaxAmount = bcadd($lastMonthWalletHistory->getAvailableBalance(), $totalTaxAmount, 2);
            $operationsManager->withdrawTaxWallet($wallet, $lastMonthWalletHistory->getAvailableBalance());
        }
    }
}
