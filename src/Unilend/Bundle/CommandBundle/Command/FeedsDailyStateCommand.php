<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Psr\Log\LoggerInterface;

class FeedsDailyStateCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('feeds:daily_state')
            ->setDescription('Extract daily fiscal state')
            ->addArgument(
                'day',
                InputArgument::OPTIONAL,
                'Day of the state to export (format: Y-m-d)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var EntityManager $entityManager */
            $entityManager = $this->getContainer()->get('unilend.service.entity_manager');

            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');
            /** @var \dates $dates */
            $dates = Loader::loadLib('dates');

            /** @var \transactions $transaction */
            $transaction = $entityManager->getRepository('transactions');
            /** @var \echeanciers $lenderRepayment */
            $lenderRepayment = $entityManager->getRepository('echeanciers');
            /** @var \echeanciers_emprunteur $borrowerRepayment */
            $borrowerRepayment = $entityManager->getRepository('echeanciers_emprunteur');
            /** @var \virements $bankTransfer */
            $bankTransfer = $entityManager->getRepository('virements');
            /** @var \prelevements $directDebit */
            $directDebit = $entityManager->getRepository('prelevements');
            /** @var \etat_quotidien $dailyState */
            $dailyState = $entityManager->getRepository('etat_quotidien');
            /** @var \bank_unilend $unilendBank */
            $unilendBank = $entityManager->getRepository('bank_unilend');
            /** @var \tax $tax */
            $tax = $entityManager->getRepository('tax');

            $time = $input->getArgument('day');

            if ($time) {
                $time = strtotime($time);

                if (false === $time) {
                    $output->writeln('<error>Wrong date format ("Y-m-d" expected)</error>');
                    return;
                }
            } else {
                $time = time();
            }

            // si on veut mettre a jour une date on met le jour ici mais attention ca va sauvegarder en BDD et sur l'etat quotidien fait ce matin a 1h du mat
            if (date('d', $time) == 1) {
                $mois = mktime(0, 0, 0, date('m', $time) - 1, 1, date('Y', $time));
            } else {
                $mois = mktime(0, 0, 0, date('m', $time), 1, date('Y', $time));
            }
          
            $dateTime  = (new \DateTime())->setTimestamp($mois);
            $aColumns  = $this->getColumns();
            $monthDays = $this->getMonthDays($mois);

            $transactionType = [
                \transactions_types::TYPE_LENDER_SUBSCRIPTION,
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_BORROWER_REPAYMENT,
                \transactions_types::TYPE_DIRECT_DEBIT,
                \transactions_types::TYPE_LENDER_WITHDRAWAL,
                \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_UNILEND_REPAYMENT,
                \transactions_types::TYPE_UNILEND_BANK_TRANSFER,
                \transactions_types::TYPE_FISCAL_BANK_TRANSFER,
                \transactions_types::TYPE_REGULATION_COMMISSION,
                \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION,
                \transactions_types::TYPE_WELCOME_OFFER,
                \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION,
                \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER,
                \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_REGULATION_BANK_TRANSFER,
                \transactions_types::TYPE_RECOVERY_BANK_TRANSFER,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            ];

            $dailyTransactions = $transaction->getDailyState($transactionType, $dateTime);
            $dailyWelcomeOffer = $transaction->getDailyWelcomeOffer($dateTime);

            $lrembPreteurs                = $unilendBank->sumMontantByDayMonths('type = 2 AND status = 1', $dateTime->format('m'), $dateTime->format('Y')); // Les remboursements preteurs
            $alimCB                       = $this->combineTransactionTypes(
                    true === isset($dailyTransactions[\transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT]) ? $dailyTransactions[\transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT] : [],
                    $dailyWelcomeOffer
                ) + $monthDays;
            $rembEmprunteur               = $this->combineTransactionTypes(
                    true === isset($dailyTransactions[\transactions_types::TYPE_BORROWER_REPAYMENT]) ? $dailyTransactions[\transactions_types::TYPE_BORROWER_REPAYMENT] : [],
                    true === isset($dailyTransactions[\transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT]) ? $dailyTransactions[\transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT] : []
                ) + $monthDays;
            $alimVirement                 = (true === isset($dailyTransactions[\transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT]) ? $dailyTransactions[\transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT] : []) + $monthDays;
            $alimPrelevement              = (true === isset($dailyTransactions[\transactions_types::TYPE_DIRECT_DEBIT]) ? $dailyTransactions[\transactions_types::TYPE_DIRECT_DEBIT] : []) + $monthDays;
            $rembEmprunteurRegularisation = (true === isset($dailyTransactions[\transactions_types::TYPE_REGULATION_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_REGULATION_BANK_TRANSFER] : []) + $monthDays;
            $rejetrembEmprunteur          = (true === isset($dailyTransactions[\transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION]) ? $dailyTransactions[\transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION] : []) + $monthDays;
            $virementEmprunteur           = (true === isset($dailyTransactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT]) ? $dailyTransactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT] : []) + $monthDays;
            $virementUnilend              = (true === isset($dailyTransactions[\transactions_types::TYPE_UNILEND_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_UNILEND_BANK_TRANSFER] : []) + $monthDays;
            $virementEtat                 = (true === isset($dailyTransactions[\transactions_types::TYPE_FISCAL_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_FISCAL_BANK_TRANSFER] : []) + $monthDays;
            $retraitPreteur               = (true === isset($dailyTransactions[\transactions_types::TYPE_LENDER_WITHDRAWAL]) ? $dailyTransactions[\transactions_types::TYPE_LENDER_WITHDRAWAL] : []) + $monthDays;
            $regulCom                     = (true === isset($dailyTransactions[\transactions_types::TYPE_REGULATION_COMMISSION]) ? $dailyTransactions[\transactions_types::TYPE_REGULATION_COMMISSION] : []) + $monthDays;
            $offres_bienvenue             = (true === isset($dailyTransactions[\transactions_types::TYPE_WELCOME_OFFER]) ? $dailyTransactions[\transactions_types::TYPE_WELCOME_OFFER] : []) + $monthDays;
            $offres_bienvenue_retrait     = (true === isset($dailyTransactions[\transactions_types::TYPE_WELCOME_OFFER_CANCELLATION]) ? $dailyTransactions[\transactions_types::TYPE_WELCOME_OFFER_CANCELLATION] : []) + $monthDays;
            $unilend_bienvenue            = (true === isset($dailyTransactions[\transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER] : []) + $monthDays;
            $virementRecouv               = (true === isset($dailyTransactions[\transactions_types::TYPE_RECOVERY_BANK_TRANSFER]) ? $dailyTransactions[\transactions_types::TYPE_RECOVERY_BANK_TRANSFER] : []) + $monthDays;
            $rembRecouvPreteurs           = (true === isset($dailyTransactions[\transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT]) ? $dailyTransactions[\transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT] : []) + $monthDays;
            $listPrel                     = [];

            foreach ($directDebit->select('type_prelevement = 1 AND status > 0 AND type = 1') as $prelev) {
                $addedXml     = strtotime($prelev['added_xml']);
                $added        = strtotime($prelev['added']);
                $dateaddedXml = date('Y-m', $addedXml);
                $date         = date('Y-m', $added);
                $i            = 1;

                // on enregistre dans la table la premier prelevement
                $listPrel[date('Y-m-d', $added)] += $prelev['montant'];

                // tant que la date de creation n'est pas egale on rajoute les mois entre
                while ($date != $dateaddedXml) {
                    $newdate = mktime(0, 0, 0, date('m', $added) + $i, date('d', $addedXml), date('Y', $added));
                    $date    = date('Y-m', $newdate);
                    $added   = date('Y-m-d', $newdate) . ' 00:00:00';

                    $listPrel[date('Y-m-d', $newdate)] += $prelev['montant'];

                    $i++;
                }
            }

            $oldDate           = mktime(0, 0, 0, $dateTime->format('m') - 1, 1, $dateTime->format('Y'));
            $oldDate           = date('Y-m', $oldDate);
            $etat_quotidienOld = $dailyState->getTotauxbyMonth($oldDate);

            if ($etat_quotidienOld != false) {
                $soldeDeLaVeille      = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
                $soldeReel            = $etat_quotidienOld['totalNewSoldeReel'];
                $soldeSFFPME_old      = $etat_quotidienOld['totalSoldeSFFPME'];
                $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
                $soldePromotion_old   = isset($etat_quotidienOld['totalSoldePromotion']) ? $etat_quotidienOld['totalSoldePromotion'] : 0;
            } else {
                $soldeDeLaVeille      = 0;
                $soldeReel            = 0;
                $soldeSFFPME_old      = 0;
                $soldeAdminFiscal_old = 0;
                $soldePromotion_old   = 0;
            }

            $newsoldeDeLaVeille = $soldeDeLaVeille;
            $soldePromotion     = $soldePromotion_old;
            $oldecart           = $soldeDeLaVeille - $soldeReel;
            $soldeSFFPME        = $soldeSFFPME_old;
            $soldeAdminFiscal   = $soldeAdminFiscal_old;

            $totalAlimCB                              = 0;
            $totalAlimVirement                        = 0;
            $totalAlimPrelevement                     = 0;
            $totalRembEmprunteur                      = 0;
            $totalVirementEmprunteur                  = 0;
            $totalVirementCommissionUnilendEmprunteur = 0;
            $totalCommission                          = 0;
            $totalVirementUnilend_bienvenue           = 0;
            $totalAffectationEchEmpr                  = 0;
            $totalOffrePromo                          = 0;
            $totalOctroi_pret                         = 0;
            $totalCapitalPreteur                      = 0;
            $totalInteretNetPreteur                   = 0;
            $totalEcartMouvInternes                   = 0;
            $totalVirementsOK                         = 0;
            $totalVirementsAttente                    = 0;
            $totaladdsommePrelev                      = 0;
            $totalAdminFiscalVir                      = 0;
            $totalPrelevements_obligatoires           = 0;
            $totalRetenues_source                     = 0;
            $totalCsg                                 = 0;
            $totalPrelevements_sociaux                = 0;
            $totalContributions_additionnelles        = 0;
            $totalPrelevements_solidarite             = 0;
            $totalCrds                                = 0;
            $totalRetraitPreteur                      = 0;
            $totalSommeMouvements                     = 0;
            $totalNewSoldeReel                        = 0;
            $totalEcartSoldes                         = 0;
            $totalNewsoldeDeLaVeille                  = 0;
            $totalSoldePromotion                      = 0;
            $totalSoldeSFFPME                         = $soldeSFFPME_old;
            $totalSoldeAdminFiscal                    = $soldeAdminFiscal_old;

            $tableau = '
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

        <table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
            <tr>
                <th colspan="34" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
            </tr>
            <tr>
                <th rowspan="2">' . $dateTime->format('d-m-Y') . '</th>
                <th colspan="3">Chargements compte pr&ecirc;teurs</th>
                <th>Chargements offres</th>
                <th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi pr&ecirc;t</th>
                <th>Commissions<br />restant d&ucirc;</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux pr&ecirc;teurs</th>
                <th>&nbsp;</th>
                <th colspan="6">Soldes</th>
                <th colspan="6">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Pr&eacute;l&egrave;vements</th>
            </tr>
            <tr>';

            foreach ($aColumns as $key => $value) {
                $tableau .= '<td class="center">' . $value . '</td>';
            }

            $tableau .= '
            </tr>
            <tr>
                <td colspan="18">D&eacute;but du mois</td>
                <td class="right">' . $ficelle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $ficelle->formatNumber($oldecart) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

            foreach (array_keys($monthDays) as $date) {

                if (strtotime($date . ' 00:00:00') < $time) {
                    $interetNetPreteur = bcdiv($transaction->getInterestsAmount($date . ' 00:00:00', $date . ' 23:59:59'), 100, 2);
                    $aDailyTax         = $tax->getDailyTax($date . ' 00:00:00', $date . ' 23:59:59');
                    $iTotalTaxAmount   = 0;

                    foreach ($aDailyTax as $iTaxTypeId => $iTaxAmount) {
                        $aDailyTax[$iTaxTypeId] = bcdiv($iTaxAmount, 100, 2);
                        $iTotalTaxAmount += $aDailyTax[$iTaxTypeId];
                    }
                    $dailyRepaidCapital = $lenderRepayment->getRepaidCapitalInDateRange(null, $date . ' 00:00:00', $date . ' 23:59:59');
                    $commission         = bcdiv($borrowerRepayment->getCostsAndVatAmount($date), 100, 2);
                    $commission         = bcadd($commission, $regulCom[$date]['montant'], 2);

                    $soldePromotion += $unilend_bienvenue[$date]['montant'];
                    $soldePromotion -= $offres_bienvenue[$date]['montant'];
                    $soldePromotion += -$offres_bienvenue_retrait[$date]['montant'];

                    $offrePromo = $offres_bienvenue[$date]['montant'] + $offres_bienvenue_retrait[$date]['montant'];

                    $entrees = $alimCB[$date]['montant'] + $alimVirement[$date]['montant'] + $alimPrelevement[$date]['montant'] + $rembEmprunteur[$date]['montant'] + $rembEmprunteurRegularisation[$date]['montant'] + $unilend_bienvenue[$date]['montant'] + $rejetrembEmprunteur[$date]['montant'] + $virementRecouv[$date]['montant'];
                    $sorties = abs($virementEmprunteur[$date]['montant']) + $virementEmprunteur[$date]['montant_unilend'] + $commission + $iTotalTaxAmount + abs($retraitPreteur[$date]['montant']);

                    $sommeMouvements = $entrees - $sorties;
                    $newsoldeDeLaVeille += $sommeMouvements;

                    $soldeReel      = bcadd($soldeReel, $this->getRealSold($dailyTransactions, $date), 2);
                    $soldeReel      = bcadd(
                        $soldeReel,
                        bcsub(
                            $this->getUnilendRealSold($dailyTransactions, $date),
                            bcadd($commission, $this->getStateRealSold($dailyTransactions, $date), 2),
                            2),
                        2);
                    $newSoldeReel   = $soldeReel; // on retire la commission des echeances du jour ainsi que la partie pour l'etat
                    $soldeTheorique = $newsoldeDeLaVeille;
                    $leSoldeReel    = $newSoldeReel;

                    if (strtotime($date . ' 00:00:00') > time()) {
                        $soldeTheorique = 0;
                        $leSoldeReel    = 0;
                    }

                    $ecartSoldes = $soldeTheorique - $leSoldeReel;
                    $soldeSFFPME += $virementEmprunteur[$date]['montant_unilend'] - $virementUnilend[$date]['montant'] + $commission;
                    $soldeAdminFiscal += $iTotalTaxAmount - $virementEtat[$date]['montant'];

                    $capitalPreteur = $dailyRepaidCapital + $rembRecouvPreteurs[$date]['montant'];

                    $affectationEchEmpr = isset($lrembPreteurs[$date]) ? $lrembPreteurs[$date]['montant'] + $lrembPreteurs[$date]['etat'] + $commission + $rembRecouvPreteurs[$date]['montant'] : 0;
                    $ecartMouvInternes  = round($affectationEchEmpr - $commission - $iTotalTaxAmount - $capitalPreteur - $interetNetPreteur, 2);
                    $octroi_pret        = abs($virementEmprunteur[$date]['montant']) + $virementEmprunteur[$date]['montant_unilend'];
                    $virementsOK        = $bankTransfer->sumVirementsbyDay($date, 'status > 0');
                    $virementsAttente   = $virementUnilend[$date]['montant'];
                    $adminFiscalVir     = $virementEtat[$date]['montant'];
                    $prelevPonctuel     = $directDebit->sum('DATE(added_xml) = "' . $date . '" AND status > 0');

                    if (false === empty($listPrel[$date])) {
                        $sommePrelev = $prelevPonctuel + $listPrel[$date];
                    } else {
                        $sommePrelev = $prelevPonctuel;
                    }

                    $sommePrelev      = $sommePrelev / 100;
                    $leRembEmprunteur = $rembEmprunteur[$date]['montant'] + $rembEmprunteurRegularisation[$date]['montant'] + $rejetrembEmprunteur[$date]['montant'] + $virementRecouv[$date]['montant'];

                    $totalAlimCB += $alimCB[$date]['montant'];
                    $totalAlimVirement += $alimVirement[$date]['montant'];
                    $totalAlimPrelevement += $alimPrelevement[$date]['montant'];
                    $totalRembEmprunteur += $leRembEmprunteur; // update le 22/01/2015
                    $totalVirementEmprunteur += abs($virementEmprunteur[$date]['montant']);
                    $totalVirementCommissionUnilendEmprunteur += $virementEmprunteur[$date]['montant_unilend'];
                    $totalVirementUnilend_bienvenue += $unilend_bienvenue[$date]['montant'];
                    $totalCommission += $commission;

                    $totalPrelevements_obligatoires += isset($aDailyTax[\tax_type::TYPE_INCOME_TAX]) ? $aDailyTax[\tax_type::TYPE_INCOME_TAX] : 0.0;
                    $totalRetenues_source += isset($aDailyTax[\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]) ? $aDailyTax[\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE] : 0.0;
                    $totalCsg += isset($aDailyTax[\tax_type::TYPE_CSG]) ? $aDailyTax[\tax_type::TYPE_CSG] : 0.0;
                    $totalPrelevements_sociaux += isset($aDailyTax[\tax_type::TYPE_SOCIAL_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_SOCIAL_DEDUCTIONS] : 0.0;
                    $totalContributions_additionnelles += isset($aDailyTax[\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS] : 0.0;
                    $totalPrelevements_solidarite += isset($aDailyTax[\tax_type::TYPE_SOLIDARITY_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_SOLIDARITY_DEDUCTIONS] : 0.0;
                    $totalCrds += isset($aDailyTax[\tax_type::TYPE_CRDS]) ? $aDailyTax[\tax_type::TYPE_CRDS] : 0.0;

                    $totalRetraitPreteur += $retraitPreteur[$date]['montant'];
                    $totalSommeMouvements += $sommeMouvements;
                    $totalNewsoldeDeLaVeille = $newsoldeDeLaVeille; // Solde théorique
                    $totalNewSoldeReel       = $newSoldeReel;
                    $totalEcartSoldes        = $ecartSoldes;
                    $totalAffectationEchEmpr += $affectationEchEmpr;
                    $totalSoldePromotion = $soldePromotion;
                    $totalOffrePromo += $offrePromo;
                    $totalSoldeSFFPME      = $soldeSFFPME;
                    $totalSoldeAdminFiscal = $soldeAdminFiscal;
                    $totalOctroi_pret += $octroi_pret;
                    $totalCapitalPreteur += $capitalPreteur;
                    $totalInteretNetPreteur += $interetNetPreteur;
                    $totalEcartMouvInternes += $ecartMouvInternes;
                    $totalVirementsOK += $virementsOK;
                    $totalVirementsAttente += $virementsAttente;
                    $totaladdsommePrelev += $sommePrelev;
                    $totalAdminFiscalVir += $adminFiscalVir;

                    $tableau .= '
                <tr>
                    <td class="dates">' . date('d/m/Y', strtotime($date)) . '</td>
                    <td class="right">' . $ficelle->formatNumber($alimCB[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($alimVirement[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($alimPrelevement[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($unilend_bienvenue[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($leRembEmprunteur) . '</td>
                    <td class="right">' . $ficelle->formatNumber(abs($virementEmprunteur[$date]['montant'])) . '</td>
                    <td class="right">' . $ficelle->formatNumber($virementEmprunteur[$date]['montant_unilend']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($commission) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_INCOME_TAX]) ? $aDailyTax[\tax_type::TYPE_INCOME_TAX] : 0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]) ? $aDailyTax[\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_CSG]) ? $aDailyTax[\tax_type::TYPE_CSG] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_SOCIAL_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_SOCIAL_DEDUCTIONS] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_SOLIDARITY_DEDUCTIONS]) ? $aDailyTax[\tax_type::TYPE_SOLIDARITY_DEDUCTIONS] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(isset($aDailyTax[\tax_type::TYPE_CRDS]) ? $aDailyTax[\tax_type::TYPE_CRDS] : 0.0) . '</td>
                    <td class="right">' . $ficelle->formatNumber(abs($retraitPreteur[$date]['montant'])) . '</td>
                    <td class="right">' . $ficelle->formatNumber($sommeMouvements) . '</td>
                    <td class="right">' . $ficelle->formatNumber($soldeTheorique) . '</td>
                    <td class="right">' . $ficelle->formatNumber($leSoldeReel) . '</td>
                    <td class="right">' . $ficelle->formatNumber($ecartSoldes) . '</td>
                    <td class="right">' . $ficelle->formatNumber($soldePromotion) . '</td>
                    <td class="right">' . $ficelle->formatNumber($soldeSFFPME) . '</td>
                    <td class="right">' . $ficelle->formatNumber($soldeAdminFiscal) . '</td>
                    <td class="right">' . $ficelle->formatNumber($offrePromo) . '</td>
                    <td class="right">' . $ficelle->formatNumber($octroi_pret) . '</td>
                    <td class="right">' . $ficelle->formatNumber($capitalPreteur) . '</td>
                    <td class="right">' . $ficelle->formatNumber($interetNetPreteur) . '</td>
                    <td class="right">' . $ficelle->formatNumber($affectationEchEmpr) . '</td>
                    <td class="right">' . $ficelle->formatNumber($ecartMouvInternes) . '</td>
                    <td class="right">' . $ficelle->formatNumber($virementsOK) . '</td>
                    <td class="right">' . $ficelle->formatNumber($virementsAttente) . '</td>
                    <td class="right">' . $ficelle->formatNumber($adminFiscalVir) . '</td>
                    <td class="right">' . $ficelle->formatNumber($sommePrelev) . '</td>
                </tr>';
                } else {
                    $tableau .= '
                <tr>
                    <td class="dates">' . date('d/m/Y', strtotime($date)) . '</td>';
                    foreach ($aColumns as $value) {
                        $tableau .= '<td>&nbsp;</td>';
                    }
                    $tableau .= '</tr>';
                }
            }

            $tableau .= '
            <tr>
                <td colspan="33">&nbsp;</td>
            </tr>
            <tr>
                <th>Total mois</th>
                <th class="right">' . $ficelle->formatNumber($totalAlimCB) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalAlimVirement) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalAlimPrelevement) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementUnilend_bienvenue) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalRembEmprunteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementEmprunteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementCommissionUnilendEmprunteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalCommission) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalPrelevements_obligatoires) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalRetenues_source) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalCsg) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalPrelevements_sociaux) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalContributions_additionnelles) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalPrelevements_solidarite) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalCrds) . '</th>
                <th class="right">' . $ficelle->formatNumber(abs($totalRetraitPreteur)) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalSommeMouvements) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalNewsoldeDeLaVeille) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalNewSoldeReel) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalEcartSoldes) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalSoldePromotion) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalSoldeSFFPME) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalSoldeAdminFiscal) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalOffrePromo) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalOctroi_pret) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalCapitalPreteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalInteretNetPreteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalAffectationEchEmpr) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalEcartMouvInternes) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementsOK) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalVirementsAttente) . '</th>
                <th class="right">' . $ficelle->formatNumber($totalAdminFiscalVir) . '</th>
                <th class="right">' . $ficelle->formatNumber($totaladdsommePrelev) . '</th>
            </tr>
        </table>';

            $table = [
                1  => ['name' => 'totalAlimCB', 'val' => $totalAlimCB],
                2  => ['name' => 'totalAlimVirement', 'val' => $totalAlimVirement],
                3  => ['name' => 'totalAlimPrelevement', 'val' => $totalAlimPrelevement],
                4  => ['name' => 'totalRembEmprunteur', 'val' => $totalRembEmprunteur],
                5  => ['name' => 'totalVirementEmprunteur', 'val' => $totalVirementEmprunteur],
                6  => ['name' => 'totalVirementCommissionUnilendEmprunteur', 'val' => $totalVirementCommissionUnilendEmprunteur],
                7  => ['name' => 'totalCommission', 'val' => $totalCommission],
                8  => ['name' => 'totalPrelevements_obligatoires', 'val' => $totalPrelevements_obligatoires],
                9  => ['name' => 'totalRetenues_source', 'val' => $totalRetenues_source],
                10 => ['name' => 'totalCsg', 'val' => $totalCsg],
                11 => ['name' => 'totalPrelevements_sociaux', 'val' => $totalPrelevements_sociaux],
                12 => ['name' => 'totalContributions_additionnelles', 'val' => $totalContributions_additionnelles],
                13 => ['name' => 'totalPrelevements_solidarite', 'val' => $totalPrelevements_solidarite],
                14 => ['name' => 'totalCrds', 'val' => $totalCrds],
                15 => ['name' => 'totalRetraitPreteur', 'val' => $totalRetraitPreteur],
                16 => ['name' => 'totalSommeMouvements', 'val' => $totalSommeMouvements],
                17 => ['name' => 'totalNewsoldeDeLaVeille', 'val' => $totalNewsoldeDeLaVeille],
                18 => ['name' => 'totalNewSoldeReel', 'val' => $totalNewSoldeReel],
                19 => ['name' => 'totalEcartSoldes', 'val' => $totalEcartSoldes],
                20 => ['name' => 'totalOctroi_pret', 'val' => $totalOctroi_pret],
                21 => ['name' => 'totalCapitalPreteur', 'val' => $totalCapitalPreteur],
                22 => ['name' => 'totalInteretNetPreteur', 'val' => $totalInteretNetPreteur],
                23 => ['name' => 'totalEcartMouvInternes', 'val' => $totalEcartMouvInternes],
                24 => ['name' => 'totalVirementsOK', 'val' => $totalVirementsOK],
                25 => ['name' => 'totalVirementsAttente', 'val' => $totalVirementsAttente],
                26 => ['name' => 'totaladdsommePrelev', 'val' => $totaladdsommePrelev],
                27 => ['name' => 'totalSoldeSFFPME', 'val' => $totalSoldeSFFPME],
                28 => ['name' => 'totalSoldeAdminFiscal', 'val' => $totalSoldeAdminFiscal],
                29 => ['name' => 'totalAdminFiscalVir', 'val' => $totalAdminFiscalVir],
                30 => ['name' => 'totalAffectationEchEmpr', 'val' => $totalAffectationEchEmpr],
                31 => ['name' => 'totalVirementUnilend_bienvenue', 'val' => $totalVirementUnilend_bienvenue],
                32 => ['name' => 'totalSoldePromotion', 'val' => $totalSoldePromotion],
                33 => ['name' => 'totalOffrePromo', 'val' => $totalOffrePromo]
            ];

            $dailyState->createEtat_quotidient($table, $dateTime->format('m'), $dateTime->format('Y'));

            $oldDate           = mktime(0, 0, 0, 12, date('d', $time), $dateTime->format('Y') - 1);
            $oldDate           = date('Y-m', $oldDate);
            $etat_quotidienOld = $dailyState->getTotauxbyMonth($oldDate);

            if ($etat_quotidienOld != false) {
                $soldeDeLaVeille      = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
                $soldeReel            = $etat_quotidienOld['totalNewSoldeReel'];
                $soldeSFFPME_old      = $etat_quotidienOld['totalSoldeSFFPME'];
                $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
                $soldePromotion_old   = isset($etat_quotidienOld['totalSoldePromotion']) ? $etat_quotidienOld['totalSoldePromotion'] : 0;
            } else {
                $soldeDeLaVeille      = 0;
                $soldeReel            = 0;
                $soldeSFFPME_old      = 0;
                $soldeAdminFiscal_old = 0;
                $soldePromotion_old   = 0;
            }

            $oldecart = $soldeDeLaVeille - $soldeReel;

            $tableau .= '
        <table border="0" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 2500px;">
            <tr>
                <th colspan="34" style="font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">&nbsp;</th>
            </tr>
            <tr>
                <th colspan="34" style="height:35px;font:italic 18px Arial, Helvetica, sans-serif; text-align:center;">UNILEND</th>
            </tr>
            <tr>
                <th rowspan="2">' . $dateTime->format('Y') . '</th>
                <th colspan="3">Chargements compte pr&ecirc;teurs</th>
                <th>Chargements offres</th>
                <th>Echeances<br />Emprunteur</th>
                <th>Octroi pr&ecirc;t</th>
                <th>Commissions<br />octroi pr&ecirc;t</th>
                <th>Commissions<br />restant &ucirc;</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux pr&ecirc;teurs</th>
                <th>&nbsp;</th>
                <th colspan="6">Soldes</th>
                <th colspan="6">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Pr&eacute;l&egrave;vements</th>
            </tr>
            <tr>';

            foreach ($aColumns as $key => $value) {
                $tableau .= '<td class="center">' . $value . '</td>';
            }

            $tableau .= '
            </tr>
            <tr>
                <td colspan="18">D&eacute;but d\'ann&eacute;e</td>
                <td class="right">' . $ficelle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $ficelle->formatNumber($oldecart) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

            foreach ($aColumns as $sKey => $value) {
                $$sKey = 0;
            }

            for ($i = 1; $i <= 12; $i++) {
                if (strlen($i) < 2) {
                    $numMois = '0' . $i;
                } else {
                    $numMois = $i;
                }

                $lemois = $dailyState->getTotauxbyMonth($dateTime->format('Y') . '-' . $numMois);

                if (false === empty($lemois)) {

                    foreach ($lemois as $key => $value) {
                        if (false === in_array($key, array('totalNewsoldeDeLaVeille', 'totalNewSoldeReel', 'totalEcartSoldes', 'totalSoldeSFFPME', 'totalSoldeAdminFiscal', 'totalSoldePromotion'))) {
                            $$key += $value;
                        } else {
                            $$key = $value;
                        }
                    }
                }

                $tableau .= '
                <tr>
                    <th>' . $dates->tableauMois['fr'][$i] . '</th>';

                if (false === empty($lemois)) {
                    foreach ($aColumns as $key => $value) {
                        if ('totalRetraitPreteur' === $key) {
                            $amount = abs(isset($lemois[$key]) ? $lemois[$key] : 0);
                        } else {
                            $amount = isset($lemois[$key]) ? $lemois[$key] : 0;
                        }
                        $tableau .= '<td class="right">' . $ficelle->formatNumber($amount) . '</td>';
                    }
                } else {
                    for ($index = 0; $index++; $index < 33) {
                        $tableau .= '<td>&nbsp;</td>';
                    }
                }

                $tableau .= '</tr>';
            }
            $tableau .= '<tr>
                <th>Total ann&eacute;e</th>';
            foreach ($aColumns as $key => $value) {
                if ('totalRetraitPreteur' === $key) {
                    $amount = abs($$key);
                } else {
                    $amount = $$key;
                }
                $tableau .= '<th class="right">' . $ficelle->formatNumber($amount) . '</th>';
            }
            $tableau .= '
            </tr>
        </table>';

            file_put_contents($this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_quotidien/Unilend_etat_' . date('Ymd', $time) . '.xls', $tableau);

            /** @var \settings $oSettings */
            $oSettings = $entityManager->getRepository('settings');
            $oSettings->get('Adresse notification etat quotidien', 'type');

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-quotidien', [], false);
            $message
                ->setTo(explode(';', trim($oSettings->value)))
                ->attach(\Swift_Attachment::fromPath($this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_quotidien/Unilend_etat_' . date('Ymd', $time) . '.xls'));

            /** @var \Swift_Mailer $mailer */
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('An error occured while generating daily state at line : ' . $exception->getLine() . '. Error message : ' . $exception->getMessage(), array('class' => __CLASS__, 'function' => __FUNCTION__));
        }
    }

    /**
     * @return array
     */
    private function getColumns()
    {
        return [
            'totalAlimCB'                              => 'Carte<br />bancaire',
            'totalAlimVirement'                        => 'Virement',
            'totalAlimPrelevement'                     => 'Pr&eacute;l&egrave;vement',
            'totalVirementUnilend_bienvenue'           => 'Virement',
            'totalRembEmprunteur'                      => 'Pr&eacute;l&egrave;vement',
            'totalVirementEmprunteur'                  => 'Virement',
            'totalVirementCommissionUnilendEmprunteur' => 'Virement',
            'totalCommission'                          => 'Virement',
            'totalPrelevements_obligatoires'           => 'Pr&eacute;l&egrave;vements<br />obligatoires',
            'totalRetenues_source'                     => 'Retenues &agrave; la<br />source',
            'totalCsg'                                 => 'CSG',
            'totalPrelevements_sociaux'                => 'Pr&eacute;l&egrave;vements<br />sociaux',
            'totalContributions_additionnelles'        => 'Contributions<br />additionnelles',
            'totalPrelevements_solidarite'             => 'Pr&eacute;l&egrave;vements<br />solidarit&eacute;',
            'totalCrds'                                => 'CRDS',
            'totalRetraitPreteur'                      => 'Virement',
            'totalSommeMouvements'                     => 'Total<br />mouvements',
            'totalNewsoldeDeLaVeille'                  => 'Solde<br />th&eacute;orique',
            'totalNewSoldeReel'                        => 'Solde<br />r&eacute;el',
            'totalEcartSoldes'                         => 'Ecart<br />global',
            'totalSoldePromotion'                      => 'Solde<br />Promotions',
            'totalSoldeSFFPME'                         => 'Solde<br />SFF PME',
            'totalSoldeAdminFiscal'                    => 'Solde Admin.<br>Fiscale',
            'totalOffrePromo'                          => 'Offre promo',
            'totalOctroi_pret'                         => 'Octroi pr&ecirc;t',
            'totalCapitalPreteur'                      => 'Retour pr&ecirc;teur<br />(Capital)',
            'totalInteretNetPreteur'                   => 'Retour pr&ecirc;teur<br />(Int&eacute;r&ecirc;ts nets)',
            'totalAffectationEchEmpr'                  => 'Affectation<br />Ech. Empr.',
            'totalEcartMouvInternes'                   => 'Ecart<br />fiscal',
            'totalVirementsOK'                         => 'Fichier<br />virements',
            'totalVirementsAttente'                    => 'Dont<br />SFF PME',
            'totalAdminFiscalVir'                      => 'Administration<br />Fiscale',
            'totaladdsommePrelev'                      => 'Fichier<br />pr&eacute;l&egrave;vements',
        ];
    }

    /**
     * @param array $a
     * @param array $b
     * @return array
     */
    private function combineTransactionTypes(array $a, array $b)
    {
        foreach (array_intersect_key($a, $b) as $date => $row) {
            $a[$date]['montant']         = bcadd($a[$date]['montant'], $b[$date]['montant']);
            $a[$date]['montant_unilend'] = bcadd($a[$date]['montant_unilend'], $b[$date]['montant_unilend']);
            $a[$date]['montant_etat']    = bcadd($a[$date]['montant_etat'], $b[$date]['montant_etat']);
        }
        return $a + $b;
    }

    /**
     * @param int $time
     * @return array
     */
    private function getMonthDays($time)
    {
        $monthDays = [];
        $nbDays    = date('t', $time);
        $date      = (new \DateTime())->setTimestamp($time);
        $data      = [
            'montant'         => 0,
            'montant_unilend' => 0,
            'montant_etat'    => 0
        ];
        $di        = new \DateInterval('P1D');
        $i         = 1;

        while ($i <= $nbDays) {
            $monthDays[$date->format('Y-m-d')] = $data;
            $date->add($di);
            $i++;
        }
        return $monthDays;
    }

    /**
     * @param array $transactions
     * @param string $date
     * @return string
     */
    private function getRealSold(array $transactions, $date)
    {
        $sold     = 0;
        $realType = array(
            \transactions_types::TYPE_LENDER_SUBSCRIPTION,
            \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
            \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
            \transactions_types::TYPE_BORROWER_REPAYMENT,
            \transactions_types::TYPE_DIRECT_DEBIT,
            \transactions_types::TYPE_LENDER_WITHDRAWAL,
            \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION,
            \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER,
            \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT,
            \transactions_types::TYPE_REGULATION_BANK_TRANSFER,
            \transactions_types::TYPE_RECOVERY_BANK_TRANSFER
        );

        foreach ($realType as $transactionType) {
            if (isset($transactions[$transactionType][$date])) {
                $sold =
                    bcadd(
                        $sold,
                        $transactions[$transactionType][$date]['montant'],
                        2
                    );
            }
        }
        return $sold;
    }

    /**
     * @param array $transactions
     * @param string $date
     * @return string
     */
    private function getUnilendRealSold(array $transactions, $date)
    {
        $sold = 0;
        if (isset($transactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT][$date])) {
            $sold =
                bcsub(
                    $transactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT][$date]['montant'],
                    $transactions[\transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT][$date]['montant_unilend'],
                    2
                );
        }
        return $sold;
    }

    /**
     * @param array $transactions
     * @param $date
     * @return string
     */
    private function getStateRealSold(array $transactions, $date)
    {
        $sold = 0;
        if (isset($transactions[\transactions_types::TYPE_UNILEND_REPAYMENT][$date])) {
            $sold = $transactions[\transactions_types::TYPE_UNILEND_REPAYMENT][$date]['montant_etat'];
        }
        return $sold;
    }
}
