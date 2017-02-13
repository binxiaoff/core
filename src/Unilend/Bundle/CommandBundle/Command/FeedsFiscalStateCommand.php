<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class FeedsFiscalStateCommand extends ContainerAwareCommand
{
    /**
     * @var \DateTime
     */
    private $dischargeMonth;

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
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \tax_type $taxType */
        $taxType = $entityManager->getRepository('tax_type');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var array $taxRate */
        $taxRate = $taxType->getTaxRateByCountry('fr', [1]);
        /** @var \underlying_contract $contract */
        $contract = $entityManager->getRepository('underlying_contract');

        $this->dischargeMonth = new \DateTime('last month');

        $aResult = $this->getData('fr');

        if (false === empty($aResult)) {
            $exemptedInterests          = 0;
            $exemptedIncomeTax          = 0;
            $deductionAtSourceTax       = 0;
            $deductionAtSourceInterests = 0;
            $interestsBDC               = 0;
            $incomeTaxBDC               = 0;
            $interestsIFP               = 0;
            $incomeTaxIFP               = 0;
            $interestsMiniBon           = 0;
            $incomeTaxMiniBon           = 0;

            foreach ($aResult as $row) {
                $contract->get($row['id_type_contract']);
                if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'taxable' == $row['exemption_status']) {
                    switch ($contract->label) {
                        case \underlying_contract::CONTRACT_BDC:
                            $interestsBDC = $row['interests'];
                            $incomeTaxBDC = $row['tax_' . \tax_type::TYPE_INCOME_TAX];
                            break;
                        case \underlying_contract::CONTRACT_IFP:
                            $interestsIFP = $row['interests'];
                            $incomeTaxIFP = $row['tax_' . \tax_type::TYPE_INCOME_TAX];
                            break;
                        case \underlying_contract::CONTRACT_MINIBON:
                            $interestsMiniBon = $row['interests'];
                            $incomeTaxMiniBon = $row['tax_' . \tax_type::TYPE_INCOME_TAX];
                            break;
                    }
                }

                if ('person' == $row['client_type'] && 'fr' == $row['fiscal_residence'] && 'non_taxable' == $row['exemption_status']) {
                    $exemptedInterests = bcadd($exemptedInterests, $row['interests'], 2);
                    $exemptedIncomeTax = bcadd($exemptedIncomeTax, $row['tax_' . \tax_type::TYPE_INCOME_TAX], 2);
                }

                if ((('person' == $row['client_type'] && 'ww' == $row['fiscal_residence']) || 'legal_entity' == $row['client_type'])) {
                    $deductionAtSourceInterests = bcadd($deductionAtSourceInterests, $row['interests'], 2);

                    if ($contract->label != \underlying_contract::CONTRACT_IFP) {
                        $deductionAtSourceTax = bcadd($deductionAtSourceTax, $row['tax_' . \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE], 2);
                    }
                }
            }
            $csg                    = array_sum(array_column($aResult, 'tax_' . \tax_type::TYPE_CSG));
            $socialDeduction        = array_sum(array_column($aResult, 'tax_' . \tax_type::TYPE_SOCIAL_DEDUCTIONS));
            $additionalContribution = array_sum(array_column($aResult, 'tax_' . \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS));
            $solidarityDeduction    = array_sum(array_column($aResult, 'tax_' . \tax_type::TYPE_SOLIDARITY_DEDUCTIONS));
            $crds                   = array_sum(array_column($aResult, 'tax_' . \tax_type::TYPE_CRDS));
            $totalInterest          = bcadd(bcadd(bcadd($interestsBDC, $interestsIFP, 2), $interestsMiniBon, 2), $exemptedInterests, 2);
            $totalIncomeTax         = bcadd(bcadd(bcadd($incomeTaxBDC, $incomeTaxIFP, 2), $incomeTaxMiniBon, 2), $exemptedIncomeTax, 2);

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
                        <th style="background-color:#C9DAF2;">' . (new \DateTime('first day of last month'))->format('d/m/Y') . '</th>
                        <th style="background-color:#C9DAF2;">au</th>
                        <th style="background-color:#C9DAF2;">' . (new \DateTime('last day of last month'))->format('d/m/Y') . '</th>
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
                        <td class="right">' . $ficelle->formatNumber($incomeTaxBDC) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_INCOME_TAX]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Soumis au pr&eacute;l&egrave;vements (pr&ecirc;t IFP)</th> <!-- Somme des interets bruts pour : Personne physique, résident français, non exonéré, type loan : 2-->
                        <td class="right">' . $ficelle->formatNumber($interestsIFP) . '</td>
                        <td class="right">' . $ficelle->formatNumber($incomeTaxIFP) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_INCOME_TAX]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Soumis au pr&eacute;l&egrave;vements (minibons)</th> <!-- Somme des interets bruts pour : Personne physique, résident français, non exonéré, type loan : minibons-->
                        <td class="right">' . $ficelle->formatNumber($interestsMiniBon) . '</td>
                        <td class="right">' . $ficelle->formatNumber($incomeTaxMiniBon) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_INCOME_TAX]) . '%</td>
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
                        <td class="right">' . $ficelle->formatNumber($totalIncomeTax) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_INCOME_TAX]) . '%</td>
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
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#ECAEAE;" colspan="4">Pr&eacute;l&egrave;vements sociaux</th>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">CSG</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($csg) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_CSG]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Pr&eacute;l&egrave;vement social</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($socialDeduction) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_SOCIAL_DEDUCTIONS]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Contribution additionnelle</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($additionalContribution) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">Pr&eacute;l&egrave;vements de solidarit&eacute;</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($solidarityDeduction) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_SOLIDARITY_DEDUCTIONS]) . '%</td>
                    </tr>
                    <tr>
                        <th style="background-color:#E6F4DA;">CRDS</th>
                        <td class="right">' . $ficelle->formatNumber($totalInterest) . '</td>
                        <td class="right">' . $ficelle->formatNumber($crds) . '</td>
                        <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($taxRate[\tax_type::TYPE_CRDS]) . '%</td>
                    </tr>
                </table>';

            $sFilePath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_fiscal/Unilend_etat_fiscal_' . date('Ymd') . '.xls';
            file_put_contents($sFilePath, $table);

            $settings->get('Adresse notification etat fiscal', 'type');
            $destinataire = $settings->value;

            $sUrl = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');

            $varMail = array(
                '$surl' => $sUrl,
                '$url'  => $sUrl
            );

            /** @var TemplateMessage $message */
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-fiscal', $varMail, false);
            $message->setTo(explode(';', trim($destinataire)));
            $message->attach(\Swift_Attachment::fromPath($sFilePath));
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
            $this->insertRepayment();
        }
    }

    /**
     * @param string $country
     * @return array
     */
    private function getData($country)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $entityManager->getRepository('echeanciers');
        /** @var \tax_type $tax_type */
        $tax_type = $entityManager->getRepository('tax_type');

        try {
            return $echeanciers->getFiscalState($this->dischargeMonth->modify('first day of this month'), $this->dischargeMonth->modify('last day of this month'), $tax_type->getTaxDetailsByCountry($country, [\tax_type::TYPE_VAT]));
        } catch (\Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('Could not get the fiscal state data : ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
            return [];
        }
    }

    /**
     * Create fiscal repayment transaction
     */
    private function insertRepayment()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \bank_unilend $bank_unilend */
        $bank_unilend = $entityManager->getRepository('bank_unilend');
        /** @var \transactions $transactions */
        $transactions = $entityManager->getRepository('transactions');
        /** @var \DateTime $lastMonth */
        $lastMonth = new \DateTime('last month');

        $etatRemb         = $bank_unilend->sumMontantEtat('status = 1 AND type = 2 AND LEFT(added, 7) = "' . $lastMonth->format('Y-m') . '"');
        $regulCom         = $transactions->getDailyState([\transactions_types::TYPE_REGULATION_COMMISSION], $lastMonth);
        $sommeRegulDuMois = 0;

        if (true === isset($regulCom[\transactions_types::TYPE_REGULATION_COMMISSION])) {
            foreach ($regulCom[\transactions_types::TYPE_REGULATION_COMMISSION] as $r) {
                $sommeRegulDuMois = bcadd($sommeRegulDuMois, bcmul($r['montant_unilend'], 100));
            }
        }
        $etatRemb = bcadd($etatRemb, $sommeRegulDuMois);

        if ($etatRemb > 0) {
            $transactions->id_client        = 0;
            $transactions->montant          = $etatRemb;
            $transactions->id_langue        = 'fr';
            $transactions->date_transaction = date('Y-m-d H:i:s');
            $transactions->status           = \transactions::STATUS_VALID;
            $transactions->type_transaction = \transactions_types::TYPE_FISCAL_BANK_TRANSFER;
            $transactions->create();

            $bank_unilend->id_transaction         = $transactions->id_transaction;
            $bank_unilend->id_echeance_emprunteur = 0;
            $bank_unilend->id_project             = 0;
            $bank_unilend->montant                = -$etatRemb;
            $bank_unilend->type                   = \bank_unilend::TYPE_DEBIT_UNILEND;
            $bank_unilend->status                 = 3;
            $bank_unilend->retrait_fiscale        = 1;
            $bank_unilend->create();
        }

        $totalTaxAmount = bcmul($this->doTaxWalletsWithdrawals(), 100);
        if (0 !== (bccomp($totalTaxAmount, $etatRemb, 2))) {
            $this->getContainer()->get('logger')->error('Tax balance does not match in fiscal state. stateWallets = ' . $totalTaxAmount . ' fiscal state value : ' . $etatRemb);
        }
    }

    private function doTaxWalletsWithdrawals()
    {
        $operationsManager = $this->getContainer()->get('unilend.service.operation_manager');
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger            = $this->getContainer()->get('monolog.logger.console');
        $totalTaxAmount    = 0;

        /** @var Wallet[] $taxWallets */
        $taxWallets = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getTaxWallets();
        foreach ($taxWallets as $wallet) {
            /** @var WalletBalanceHistory $lastMonthWalletHistory */
            $lastMonthWalletHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->getBalanceOfTheDay($wallet, $this->dischargeMonth->modify('last day of this month'));
            if (null === $lastMonthWalletHistory) {
                $logger->error('Could not get the wallet balance for ' . $wallet->getIdType()->getLabel(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
                continue;
            }
            $totalTaxAmount = bcadd($lastMonthWalletHistory->getAvailableBalance(), $totalTaxAmount, 2);
            $operationsManager->withdrawTaxWallet($wallet, $lastMonthWalletHistory->getAvailableBalance());
        }

        return $totalTaxAmount;
    }
}
