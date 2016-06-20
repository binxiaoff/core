<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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

        $nbJours       = date('t', $mois);
        $leMois        = date('m', $mois);
        $lannee        = date('Y', $mois);
        $InfeA         = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));
        $lanneeLemois  = date('Y-m', $mois);
        $laDate        = date('d-m-Y', $time);
        $lemoisLannee2 = date('m/Y', $mois);

        $lrembPreteurs                = $unilendBank->sumMontantByDayMonths('type = 2 AND status = 1', $leMois, $lannee); // Les remboursements preteurs
        $listEcheances                = $unilendBank->ListEcheancesByDayMonths('type = 2 AND status = 1', $leMois, $lannee); // On recup les echeances le jour où ils ont été remb aux preteurs
        $alimCB                       = $transaction->sumByday('3', $leMois, $lannee); // alimentations CB
        $alimVirement                 = $transaction->sumByday('4', $leMois, $lannee); // 2 : alimentations virements
        $alimPrelevement              = $transaction->sumByday('7', $leMois, $lannee); // 7 : alimentations prelevements
        $rembEmprunteur               = $transaction->sumByday('6, 22', $leMois, $lannee); // 6 : remb Emprunteur (prelevement) - 22 : remboursement anticipé
        $rembEmprunteurRegularisation = $transaction->sumByday('24', $leMois, $lannee); // 24 : remb regularisation Emprunteur (prelevement)
        $rejetrembEmprunteur          = $transaction->sumByday('15', $leMois, $lannee); // 15 : rejet remb emprunteur
        $virementEmprunteur           = $transaction->sumByday('9', $leMois, $lannee); // 9 : virement emprunteur (octroi prêt : montant | commissions octoi pret : unilend_montant)
        $virementUnilend              = $transaction->sumByday('11', $leMois, $lannee); // 11 : virement unilend (argent gagné envoyé sur le compte)
        $virementEtat                 = $transaction->sumByday('12', $leMois, $lannee); // 12 virerment pour l'etat
        $retraitPreteur               = $transaction->sumByday('8', $leMois, $lannee); // 8 : retrait preteur
        $regulCom                     = $transaction->sumByday('13', $leMois, $lannee); // 13 regul commission
        $offres_bienvenue             = $transaction->sumByday('16', $leMois, $lannee); // 16 unilend offre bienvenue
        $offres_bienvenue_retrait     = $transaction->sumByday('17', $leMois, $lannee); // 17 unilend offre bienvenue retrait
        $unilend_bienvenue            = $transaction->sumByday('18', $leMois, $lannee); // 18 unilend offre bienvenue
        $virementRecouv               = $transaction->sumByday('25', $leMois, $lannee);
        $rembRecouvPreteurs           = $transaction->sumByday('26', $leMois, $lannee);

        $listDates = [];
        for ($i = 1; $i <= $nbJours; $i++) {
            $listDates[$i] = $lanneeLemois . '-' . (strlen($i) < 2 ? '0' : '') . $i;
        }

        $listPrel = [];
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

        $oldDate           = mktime(0, 0, 0, $leMois - 1, 1, $lannee);
        $oldDate           = date('Y-m', $oldDate);
        $etat_quotidienOld = $dailyState->getTotauxbyMonth($oldDate);

        if ($etat_quotidienOld != false) {
            $soldeDeLaVeille      = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
            $soldeReel            = $etat_quotidienOld['totalNewSoldeReel'];
            $soldeSFFPME_old      = $etat_quotidienOld['totalSoldeSFFPME'];
            $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
            $soldePromotion_old   = $etat_quotidienOld['totalSoldePromotion'];
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
                <th rowspan="2">' . $laDate . '</th>
                <th colspan="3">Chargements compte prêteurs</th>
                <th>Chargements offres</th>
                <th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="6">Soldes</th>
                <th colspan="6">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Prélèvements</th>
            </tr>
            <tr>
                <td class="center">Carte<br>bancaire</td>
                <td class="center">Virement</td>
                <td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements<br />sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart<br />global</td>
                <td class="center">Solde<br />Promotions</td>
                <td class="center">Solde<br />SFF PME</td>
                <td class="center">Solde Admin.<br>Fiscale</td>
                <td class="center">Offre promo</td>
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
                <td class="center">Affectation<br />Ech. Empr.</td>
                <td class="center">Ecart<br />fiscal</td>
                <td class="center">Fichier<br />virements</td>
                <td class="center">Dont<br />SFF PME</td>
                <td class="center">Administration<br />Fiscale</td>
                <td class="center">Fichier<br />prélèvements</td>
            </tr>
            <tr>
                <td colspan="18">Début du mois</td>
                <td class="right">' . $ficelle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $ficelle->formatNumber($oldecart) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

        foreach ($listDates as $key => $date) {
            if (strtotime($date . ' 00:00:00') < $InfeA) {
                $echangeDate   = $lenderRepayment->getEcheanceByDayAll($date, '1 AND status_ra = 0');
                $echangeDateRA = $lenderRepayment->getEcheanceByDayAll($date, '1 AND status_ra = 1');
                $latva         = isset($listEcheances[$date]) ? $borrowerRepayment->sum('tva', 'id_echeancier_emprunteur IN(' . $listEcheances[$date] . ')') / 100 : 0;
                $commission    = isset($listEcheances[$date]) ? $borrowerRepayment->sum('commission', 'id_echeancier_emprunteur IN(' . $listEcheances[$date] . ')') / 100 + $latva : 0;
                $commission   += $regulCom[$date]['montant'];

                $prelevements_obligatoires    = $echangeDate['prelevements_obligatoires'];
                $retenues_source              = $echangeDate['retenues_source'];
                $csg                          = $echangeDate['csg'];
                $prelevements_sociaux         = $echangeDate['prelevements_sociaux'];
                $contributions_additionnelles = $echangeDate['contributions_additionnelles'];
                $prelevements_solidarite      = $echangeDate['prelevements_solidarite'];
                $crds                         = $echangeDate['crds'];

                $retenuesFiscales = $prelevements_obligatoires + $retenues_source + $csg + $prelevements_sociaux + $contributions_additionnelles + $prelevements_solidarite + $crds;

                $soldePromotion += $unilend_bienvenue[$date]['montant'];
                $soldePromotion -= $offres_bienvenue[$date]['montant'];
                $soldePromotion += - $offres_bienvenue_retrait[$date]['montant'];

                $offrePromo = $offres_bienvenue[$date]['montant'] + $offres_bienvenue_retrait[$date]['montant'];

                $entrees = $alimCB[$date]['montant'] + $alimVirement[$date]['montant'] + $alimPrelevement[$date]['montant'] + $rembEmprunteur[$date]['montant'] + $rembEmprunteurRegularisation[$date]['montant'] + $unilend_bienvenue[$date]['montant'] + $rejetrembEmprunteur[$date]['montant'] + $virementRecouv[$date]['montant'];
                $sorties = abs($virementEmprunteur[$date]['montant']) + $virementEmprunteur[$date]['montant_unilend'] + $commission + $retenuesFiscales + abs($retraitPreteur[$date]['montant']);

                $sommeMouvements     = $entrees - $sorties;
                $newsoldeDeLaVeille += $sommeMouvements;
                $soldeReel          += $transaction->getSoldeReelDay($date);
                $soldeReelUnilend    = $transaction->getSoldeReelUnilendDay($date);
                $soldeReelEtat       = $transaction->getSoldeReelEtatDay($date);
                $laComPlusLetat      = $commission + $soldeReelEtat;
                $soldeReel          += $soldeReelUnilend - $laComPlusLetat;
                $newSoldeReel        = $soldeReel; // on retire la commission des echeances du jour ainsi que la partie pour l'etat
                $soldeTheorique      = $newsoldeDeLaVeille;
                $leSoldeReel         = $newSoldeReel;

                if (strtotime($date . ' 00:00:00') > time()) {
                    $soldeTheorique = 0;
                    $leSoldeReel    = 0;
                }

                $ecartSoldes       = $soldeTheorique - $leSoldeReel;
                $soldeSFFPME      += $virementEmprunteur[$date]['montant_unilend'] - $virementUnilend[$date]['montant'] + $commission;
                $soldeAdminFiscal += $retenuesFiscales - $virementEtat[$date]['montant'];

                $capitalPreteur     = ($echangeDate['capital'] + $echangeDateRA['capital']) / 100 + $rembRecouvPreteurs[$date]['montant'];
                $interetNetPreteur  = $echangeDate['interets'] / 100 - $retenuesFiscales;
                $affectationEchEmpr = isset($lrembPreteurs[$date]) ? $lrembPreteurs[$date]['montant'] + $lrembPreteurs[$date]['etat'] + $commission + $rembRecouvPreteurs[$date]['montant'] : 0;
                $ecartMouvInternes  = round($affectationEchEmpr - $commission - $retenuesFiscales - $capitalPreteur - $interetNetPreteur, 2);
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
                $totalPrelevements_obligatoires += $prelevements_obligatoires;
                $totalRetenues_source += $retenues_source;
                $totalCsg += $csg;
                $totalPrelevements_sociaux += $prelevements_sociaux;
                $totalContributions_additionnelles += $contributions_additionnelles;
                $totalPrelevements_solidarite += $prelevements_solidarite;
                $totalCrds += $crds;
                $totalRetraitPreteur += $retraitPreteur[$date]['montant'];
                $totalSommeMouvements += $sommeMouvements;
                $totalNewsoldeDeLaVeille = $newsoldeDeLaVeille; // Solde théorique
                $totalNewSoldeReel       = $newSoldeReel;
                $totalEcartSoldes        = $ecartSoldes;
                $totalAffectationEchEmpr += $affectationEchEmpr;
                $totalSoldePromotion = $soldePromotion;
                $totalOffrePromo += $offrePromo;
                $totalSoldeSFFPME = $soldeSFFPME;
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
                    <td class="dates">' . (strlen($key) < 2 ? '0' : '') . $key . '/' . $lemoisLannee2 . '</td>
                    <td class="right">' . $ficelle->formatNumber($alimCB[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($alimVirement[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($alimPrelevement[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($unilend_bienvenue[$date]['montant']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($leRembEmprunteur) . '</td>
                    <td class="right">' . $ficelle->formatNumber(abs($virementEmprunteur[$date]['montant'])) . '</td>
                    <td class="right">' . $ficelle->formatNumber($virementEmprunteur[$date]['montant_unilend']) . '</td>
                    <td class="right">' . $ficelle->formatNumber($commission) . '</td>
                    <td class="right">' . $ficelle->formatNumber($prelevements_obligatoires) . '</td>
                    <td class="right">' . $ficelle->formatNumber($retenues_source) . '</td>
                    <td class="right">' . $ficelle->formatNumber($csg) . '</td>
                    <td class="right">' . $ficelle->formatNumber($prelevements_sociaux) . '</td>
                    <td class="right">' . $ficelle->formatNumber($contributions_additionnelles) . '</td>
                    <td class="right">' . $ficelle->formatNumber($prelevements_solidarite) . '</td>
                    <td class="right">' . $ficelle->formatNumber($crds) . '</td>
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
                    <td class="dates">' . (strlen($key) < 2 ? '0' : '') . $key . '/' . $lemoisLannee2 . '</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>';
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

        $dailyState->createEtat_quotidient($table, $leMois, $lannee);

        $oldDate           = mktime(0, 0, 0, 12, date('d', $time), $lannee - 1);
        $oldDate           = date('Y-m', $oldDate);
        $etat_quotidienOld = $dailyState->getTotauxbyMonth($oldDate);

        if ($etat_quotidienOld != false) {
            $soldeDeLaVeille      = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
            $soldeReel            = $etat_quotidienOld['totalNewSoldeReel'];
            $soldeSFFPME_old      = $etat_quotidienOld['totalSoldeSFFPME'];
            $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
            $soldePromotion_old   = $etat_quotidienOld['totalSoldePromotion'];
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
                <th rowspan="2">' . $lannee . '</th>
                <th colspan="3">Chargements compte prêteurs</th>
                <th>Chargements offres</th>
                <th>Echeances<br />Emprunteur</th>
                <th>Octroi prêt</th>
                <th>Commissions<br />octroi prêt</th>
                <th>Commissions<br />restant dû</th>
                <th colspan="7">Retenues fiscales</th>
                <th>Remboursements<br />aux prêteurs</th>
                <th>&nbsp;</th>
                <th colspan="6">Soldes</th>
                <th colspan="6">Mouvements internes</th>
                <th colspan="3">Virements</th>
                <th>Prélèvements</th>
            </tr>
            <tr>
                <td class="center">Carte<br />bancaire</td>
                <td class="center">Virement</td>
                <td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Prélèvement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Virement</td>
                <td class="center">Prélèvements<br />obligatoires</td>
                <td class="center">Retenues à la<br />source</td>
                <td class="center">CSG</td>
                <td class="center">Prélèvements<br />sociaux</td>
                <td class="center">Contributions<br />additionnelles</td>
                <td class="center">Prélèvements<br />solidarité</td>
                <td class="center">CRDS</td>
                <td class="center">Virement</td>
                <td class="center">Total<br />mouvements</td>
                <td class="center">Solde<br />théorique</td>
                <td class="center">Solde<br />réel</td>
                <td class="center">Ecart<br />global</td>
                <td class="center">Solde<br />Promotions</td>
                <td class="center">Solde<br />SFF PME</td>
                <td class="center">Solde Admin.<br>Fiscale</td>
                <td class="center">Offre promo</td>
                <td class="center">Octroi prêt</td>
                <td class="center">Retour prêteur<br />(Capital)</td>
                <td class="center">Retour prêteur<br />(Intérêts nets)</td>
                <td class="center">Affectation<br />Ech. Empr.</td>
                <td class="center">Ecart<br />fiscal</td>
                <td class="center">Fichier<br />virements</td>
                <td class="center">Dont<br />SFF PME</td>
                <td class="center">Administration<br />Fiscale</td>
                <td class="center">Fichier<br />prélèvements</td>
            </tr>
            <tr>
                <td colspan="18">Début d\'année</td>
                <td class="right">' . $ficelle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $ficelle->formatNumber($oldecart) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $ficelle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

        $sommetotalAlimCB                              = 0;
        $sommetotalAlimVirement                        = 0;
        $sommetotalAlimPrelevement                     = 0;
        $sommetotalRembEmprunteur                      = 0;
        $sommetotalVirementEmprunteur                  = 0;
        $sommetotalVirementCommissionUnilendEmprunteur = 0;
        $sommetotalCommission                          = 0;
        $sommetotalPrelevements_obligatoires           = 0;
        $sommetotalRetenues_source                     = 0;
        $sommetotalCsg                                 = 0;
        $sommetotalPrelevements_sociaux                = 0;
        $sommetotalContributions_additionnelles        = 0;
        $sommetotalPrelevements_solidarite             = 0;
        $sommetotalCrds                                = 0;
        $sommetotalAffectationEchEmpr                  = 0;
        $sommetotalRetraitPreteur                      = 0;
        $sommetotalSommeMouvements                     = 0;
        $sommetotalNewsoldeDeLaVeille                  = 0;
        $sommetotalNewSoldeReel                        = 0;
        $sommetotalEcartSoldes                         = 0;
        $sommetotalSoldeSFFPME                         = 0;
        $sommetotalSoldeAdminFiscal                    = 0;
        $sommetotalSoldePromotion                      = 0;
        $sommetotalOctroi_pret                         = 0;
        $sommetotalCapitalPreteur                      = 0;
        $sommetotalInteretNetPreteur                   = 0;
        $sommetotalEcartMouvInternes                   = 0;
        $sommetotalVirementsOK                         = 0;
        $sommetotalVirementsAttente                    = 0;
        $sommetotalAdminFiscalVir                      = 0;
        $sommetotaladdsommePrelev                      = 0;
        $sommetotalVirementUnilend_bienvenue           = 0;
        $sommetotalOffrePromo                          = 0;

        for ($i = 1; $i <= 12; $i++) {
            if (strlen($i) < 2) {
                $numMois = '0' . $i;
            } else {
                $numMois = $i;
            }

            $lemois = $dailyState->getTotauxbyMonth($lannee . '-' . $numMois);

            if (false === empty($lemois)) {
                $sommetotalAlimCB += $lemois['totalAlimCB'];
                $sommetotalAlimVirement += $lemois['totalAlimVirement'];
                $sommetotalAlimPrelevement += $lemois['totalAlimPrelevement'];
                $sommetotalRembEmprunteur += $lemois['totalRembEmprunteur'];
                $sommetotalVirementEmprunteur += $lemois['totalVirementEmprunteur'];
                $sommetotalVirementCommissionUnilendEmprunteur += $lemois['totalVirementCommissionUnilendEmprunteur'];
                $sommetotalCommission += $lemois['totalCommission'];
                $sommetotalVirementUnilend_bienvenue += $lemois['totalVirementUnilend_bienvenue'];
                $sommetotalOffrePromo += $lemois['totalOffrePromo'];
                $sommetotalPrelevements_obligatoires += $lemois['totalPrelevements_obligatoires'];
                $sommetotalRetenues_source += $lemois['totalRetenues_source'];
                $sommetotalCsg += $lemois['totalCsg'];
                $sommetotalPrelevements_sociaux += $lemois['totalPrelevements_sociaux'];
                $sommetotalContributions_additionnelles += $lemois['totalContributions_additionnelles'];
                $sommetotalPrelevements_solidarite += $lemois['totalPrelevements_solidarite'];
                $sommetotalCrds += $lemois['totalCrds'];
                $sommetotalRetraitPreteur += $lemois['totalRetraitPreteur'];
                $sommetotalSommeMouvements += $lemois['totalSommeMouvements'];
                $sommetotalOctroi_pret += $lemois['totalOctroi_pret'];
                $sommetotalCapitalPreteur += $lemois['totalCapitalPreteur'];
                $sommetotalInteretNetPreteur += $lemois['totalInteretNetPreteur'];
                $sommetotalEcartMouvInternes += $lemois['totalEcartMouvInternes'];
                $sommetotalVirementsOK += $lemois['totalVirementsOK'];
                $sommetotalVirementsAttente += $lemois['totalVirementsAttente'];
                $sommetotalAdminFiscalVir += $lemois['totalAdminFiscalVir'];
                $sommetotaladdsommePrelev += $lemois['totaladdsommePrelev'];
                $sommetotalAffectationEchEmpr += $lemois['totalAffectationEchEmpr'];

                $sommetotalNewsoldeDeLaVeille = $lemois['totalNewsoldeDeLaVeille'];
                $sommetotalNewSoldeReel       = $lemois['totalNewSoldeReel'];
                $sommetotalEcartSoldes        = $lemois['totalEcartSoldes'];
                $sommetotalSoldeSFFPME        = $lemois['totalSoldeSFFPME'];
                $sommetotalSoldeAdminFiscal   = $lemois['totalSoldeAdminFiscal'];
                $sommetotalSoldePromotion     = $lemois['totalSoldePromotion'];
            }

            $tableau .= '
                <tr>
                    <th>' . $dates->tableauMois['fr'][$i] . '</th>';

            if (false === empty($lemois)) {
                $tableau .= '
                        <td class="right">' . $ficelle->formatNumber($lemois['totalAlimCB']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalAlimVirement']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalAlimPrelevement']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalVirementUnilend_bienvenue']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalRembEmprunteur']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalVirementEmprunteur']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalVirementCommissionUnilendEmprunteur']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalCommission']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalPrelevements_obligatoires']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalRetenues_source']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalCsg']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalPrelevements_sociaux']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalContributions_additionnelles']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalPrelevements_solidarite']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalCrds']) . '</td>
                        <td class="right">' . $ficelle->formatNumber(abs($lemois['totalRetraitPreteur'])) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalSommeMouvements']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalNewsoldeDeLaVeille']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalNewSoldeReel']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalEcartSoldes']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalSoldePromotion']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalSoldeSFFPME']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalSoldeAdminFiscal']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalOffrePromo']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalOctroi_pret']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalCapitalPreteur']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalInteretNetPreteur']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalAffectationEchEmpr']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalEcartMouvInternes']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalVirementsOK']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalVirementsAttente']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totalAdminFiscalVir']) . '</td>
                        <td class="right">' . $ficelle->formatNumber($lemois['totaladdsommePrelev']) . '</td>';
            } else {
                $tableau .= '
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>';
            }

            $tableau .= '</tr>';
        }

        $tableau .= '
            <tr>
                <th>Total année</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalAlimCB) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalAlimVirement) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalAlimPrelevement) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalVirementUnilend_bienvenue) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalRembEmprunteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalVirementEmprunteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalVirementCommissionUnilendEmprunteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalCommission) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalPrelevements_obligatoires) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalRetenues_source) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalCsg) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalPrelevements_sociaux) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalContributions_additionnelles) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalPrelevements_solidarite) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalCrds) . '</th>
                <th class="right">' . $ficelle->formatNumber(abs($sommetotalRetraitPreteur)) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalSommeMouvements) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalNewsoldeDeLaVeille) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalNewSoldeReel) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalEcartSoldes) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalSoldePromotion) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalSoldeSFFPME) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalSoldeAdminFiscal) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalOffrePromo) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalOctroi_pret) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalCapitalPreteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalInteretNetPreteur) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalAffectationEchEmpr) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalEcartMouvInternes) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalVirementsOK) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalVirementsAttente) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotalAdminFiscalVir) . '</th>
                <th class="right">' . $ficelle->formatNumber($sommetotaladdsommePrelev) . '</th>
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
    }
}
