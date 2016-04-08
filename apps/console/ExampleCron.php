<?php
namespace Unilend\apps\Console;

use Unilend\core\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExampleCron extends Console
{
    protected function configure()
    {
        $this
            ->setName('report:etat_quotidien:generate')
            ->setDescription('Etat quotidien')
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'The date to generate'
            )
            ->addOption(
                'no-email',
                null,
                InputOption::VALUE_NONE,
                'If set, the task will not send the report mail'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (true === $this->startCron('etat_quotidien', 10)) {
            $sDate = $input->getArgument('date');
            if ($sDate && 1 === preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $sDate)) {
                $iTimeStamp = strtotime($sDate);
                if (false === $iTimeStamp) {
                    $this->stopCron();
                    return;
                }
            } else {
                $iTimeStamp = time();
            }

            $jour = date('d', $iTimeStamp);

            // si on veut mettre a jour une date on met le jour ici mais attention ca va sauvegarder en BDD et sur l'etat quotidien fait ce matin a 1h du mat
            // On recup le nombre de jour dans le mois
            if ($jour == 1) {
                $mois = mktime(0, 0, 0, date('m', $iTimeStamp) - 1, 1, date('Y', $iTimeStamp));
            } else {
                $mois = mktime(0, 0, 0, date('m', $iTimeStamp), 1, date('Y', $iTimeStamp));
            }

            $nbJours       = date('t', $mois);
            $leMois        = date('m', $mois);
            $lannee        = date('Y', $mois);
            $InfeA         = mktime(0, 0, 0, date('m', $iTimeStamp), date('d', $iTimeStamp), date('Y', $iTimeStamp));
            $lanneeLemois  = date('Y-m', $mois);
            $laDate        = date('d-m-Y', $iTimeStamp);
            $lemoisLannee2 = date('m/Y', $mois);

            $transac                = $this->loadData('transactions');
            $echeanciers            = $this->loadData('echeanciers');
            $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
            $virements              = $this->loadData('virements');
            $prelevements           = $this->loadData('prelevements');
            $etat_quotidien         = $this->loadData('etat_quotidien');
            $bank_unilend           = $this->loadData('bank_unilend');

            $oDates  = $this->loadLib('dates');
            $oBundle = $this->loadLib('ficelle');

            $lrembPreteurs                = $bank_unilend->sumMontantByDayMonths('type = 2 AND status = 1', $leMois, $lannee); // Les remboursements preteurs
            $listEcheances                = $bank_unilend->ListEcheancesByDayMonths('type = 2 AND status = 1', $leMois, $lannee); // On recup les echeances le jour où ils ont été remb aux preteurs
            $alimCB                       = $transac->sumByday('3', $leMois, $lannee); // alimentations CB
            $alimVirement                 = $transac->sumByday('4', $leMois, $lannee); // 2 : alimentations virements
            $alimPrelevement              = $transac->sumByday('7', $leMois, $lannee); // 7 : alimentations prelevements
            $rembEmprunteur               = $transac->sumByday('6, 22', $leMois, $lannee); // 6 : remb Emprunteur (prelevement) - 22 : remboursement anticipé
            $rembEmprunteurRegularisation = $transac->sumByday('24', $leMois, $lannee); // 24 : remb regularisation Emprunteur (prelevement)
            $rejetrembEmprunteur          = $transac->sumByday('15', $leMois, $lannee); // 15 : rejet remb emprunteur
            $virementEmprunteur           = $transac->sumByday('9', $leMois, $lannee); // 9 : virement emprunteur (octroi prêt : montant | commissions octoi pret : unilend_montant)
            $virementUnilend              = $transac->sumByday('11', $leMois, $lannee); // 11 : virement unilend (argent gagné envoyé sur le compte)
            $virementEtat                 = $transac->sumByday('12', $leMois, $lannee); // 12 virerment pour l'etat
            $retraitPreteur               = $transac->sumByday('8', $leMois, $lannee); // 8 : retrait preteur
            $regulCom                     = $transac->sumByday('13', $leMois, $lannee); // 13 regul commission
            $offres_bienvenue             = $transac->sumByday('16', $leMois, $lannee); // 16 unilend offre bienvenue
            $offres_bienvenue_retrait     = $transac->sumByday('17', $leMois, $lannee); // 17 unilend offre bienvenue retrait
            $unilend_bienvenue            = $transac->sumByday('18', $leMois, $lannee); // 18 unilend offre bienvenue
            $virementRecouv               = $transac->sumByday('25', $leMois, $lannee);
            $rembRecouvPreteurs           = $transac->sumByday('26', $leMois, $lannee);

            $listDates = array();
            for ($i = 1; $i <= $nbJours; $i++) {
                $listDates[$i] = $lanneeLemois . '-' . (strlen($i) < 2 ? '0' : '') . $i;
            }

            // recup des prelevements permanent
            $listPrel = array();
            foreach ($prelevements->select('type_prelevement = 1 AND status > 0 AND type = 1') as $prelev) {
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

            // on recup totaux du mois dernier
            $oldDate           = mktime(0, 0, 0, $leMois - 1, 1, $lannee);
            $oldDate           = date('Y-m', $oldDate);
            $etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);

            if ($etat_quotidienOld != false) {
                $soldeDeLaVeille      = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
                $soldeReel            = $etat_quotidienOld['totalNewSoldeReel'];
                $soldeSFFPME_old      = $etat_quotidienOld['totalSoldeSFFPME'];
                $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
                $soldePromotion_old   = $etat_quotidienOld['totalSoldePromotion'];
            } else {
                // Solde theorique
                $soldeDeLaVeille = 0;

                // solde reel
                $soldeReel            = 0;
                $soldeSFFPME_old      = 0;
                $soldeAdminFiscal_old = 0;

                // soldePromotion
                $soldePromotion_old = 0;
            }

            $newsoldeDeLaVeille = $soldeDeLaVeille;
            $soldePromotion     = $soldePromotion_old;

            // ecart
            $oldecart = $soldeDeLaVeille - $soldeReel;

            // Solde SFF PME
            $soldeSFFPME = $soldeSFFPME_old;

            // Solde Admin. Fiscale
            $soldeAdminFiscal = $soldeAdminFiscal_old;

            // -- totaux -- //
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

            // Retenues fiscales
            $totalPrelevements_obligatoires    = 0;
            $totalRetenues_source              = 0;
            $totalCsg                          = 0;
            $totalPrelevements_sociaux         = 0;
            $totalContributions_additionnelles = 0;
            $totalPrelevements_solidarite      = 0;
            $totalCrds                         = 0;

            $totalRetraitPreteur  = 0;
            $totalSommeMouvements = 0;

            $totalNewSoldeReel = 0;

            $totalEcartSoldes = 0;

            // Solde SFF PME
            $totalSoldeSFFPME = $soldeSFFPME_old;

            // Solde Admin. Fiscale
            $totalSoldeAdminFiscal = $soldeAdminFiscal_old;

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
                <td class="right">' . $oBundle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $oBundle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $oBundle->formatNumber($oldecart) . '</td>
                <td class="right">' . $oBundle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $oBundle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $oBundle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

            foreach ($listDates as $key => $date) {
                if (strtotime($date . ' 00:00:00') < $InfeA) {
                    // sommes des echeance par jour (sans RA)
                    $echangeDate = $echeanciers->getEcheanceByDayAll($date, '1 AND status_ra = 0');

                    // sommes des echeance par jour (que RA)
                    $echangeDateRA = $echeanciers->getEcheanceByDayAll($date, '1 AND status_ra = 1');

                    // on recup com de lecheance emprunteur a la date de mise a jour de la ligne (ddonc au changement de statut remboursé)
                    //$commission = $echeanciers_emprunteur->sum('commission','LEFT(date_echeance_emprunteur_reel,10) = "'.$date.'" AND status_emprunteur = 1');
                    // on met la commission au moment du remb preteurs
                    $commission = 0;
                    if ($listEcheances[$date]) {
                        $commission = $echeanciers_emprunteur->sum('commission', 'id_echeancier_emprunteur IN(' . $listEcheances[$date] . ')');

                        // commission sommes remboursé
                        $commission = ($commission / 100);

                        //$latva = $echeanciers_emprunteur->sum('tva','LEFT(date_echeance_emprunteur_reel,10) = "'.$date.'" AND status_emprunteur = 1');
                        // On met la TVA au moment du remb preteurs
                        $latva = $echeanciers_emprunteur->sum('tva', 'id_echeancier_emprunteur IN(' . $listEcheances[$date] . ')');

                        // la tva
                        $latva = ($latva / 100);

                        $commission += $latva;
                    }


                    ////////////////////////////
                    /// add regul commission ///

                    $commission += $regulCom[$date]['montant'];

                    ///////////////////////////
                    //prelevements_obligatoires
                    $prelevements_obligatoires = $echangeDate['prelevements_obligatoires'];
                    //retenues_source
                    $retenues_source = $echangeDate['retenues_source'];
                    //csg
                    $csg = $echangeDate['csg'];
                    //prelevements_sociaux
                    $prelevements_sociaux = $echangeDate['prelevements_sociaux'];
                    //contributions_additionnelles
                    $contributions_additionnelles = $echangeDate['contributions_additionnelles'];
                    //prelevements_solidarite
                    $prelevements_solidarite = $echangeDate['prelevements_solidarite'];
                    //crds
                    $crds = $echangeDate['crds'];

                    // Retenues Fiscales
                    $retenuesFiscales = $prelevements_obligatoires + $retenues_source + $csg + $prelevements_sociaux + $contributions_additionnelles + $prelevements_solidarite + $crds;

                    // Solde promotion
                    $soldePromotion += $unilend_bienvenue[$date]['montant'];
                    $soldePromotion -= $offres_bienvenue[$date]['montant'];
                    $soldePromotion += (-$offres_bienvenue_retrait[$date]['montant']);

                    $offrePromo = $offres_bienvenue[$date]['montant'] + $offres_bienvenue_retrait[$date]['montant'];
                    // ADD $rejetrembEmprunteur[$date]['montant'] // 22/01/2015
                    // total Mouvements
                    $entrees = ($alimCB[$date]['montant'] + $alimVirement[$date]['montant'] + $alimPrelevement[$date]['montant'] + $rembEmprunteur[$date]['montant'] + $rembEmprunteurRegularisation[$date]['montant'] + $unilend_bienvenue[$date]['montant'] + $rejetrembEmprunteur[$date]['montant'] + $virementRecouv[$date]['montant']);
                    $sorties = (str_replace('-', '', $virementEmprunteur[$date]['montant']) + $virementEmprunteur[$date]['montant_unilend'] + $commission + $retenuesFiscales + str_replace('-', '',
                            $retraitPreteur[$date]['montant']));

                    // Total mouvementsc de la journée
                    $sommeMouvements = ($entrees - $sorties);;    // solde De La Veille (solde theorique)
                    // addition du solde theorique et des mouvements
                    $newsoldeDeLaVeille += $sommeMouvements;

                    // Solde reel de base
                    $soldeReel += $transac->getSoldeReelDay($date);

                    // on rajoute les virements des emprunteurs
                    $soldeReelUnilend = $transac->getSoldeReelUnilendDay($date);

                    // solde pour l'etat
                    $soldeReelEtat = $transac->getSoldeReelEtatDay($date);

                    // la partie pour l'etat des remb unilend + la commission qu'on retire a chaque fois du solde
                    $laComPlusLetat = $commission + $soldeReelEtat;

                    // Solde réel  = solde reel unilend
                    $soldeReel += $soldeReelUnilend - $laComPlusLetat;

                    // on addition les solde precedant
                    $newSoldeReel = $soldeReel; // on retire la commission des echeances du jour ainsi que la partie pour l'etat
                    // On recupere le solde dans une autre variable
                    $soldeTheorique = $newsoldeDeLaVeille;

                    $leSoldeReel = $newSoldeReel;

                    if (strtotime($date . ' 00:00:00') > time()) {
                        $soldeTheorique = 0;
                        $leSoldeReel    = 0;
                    }

                    // ecart global soldes
                    $ecartSoldes = ($soldeTheorique - $leSoldeReel);

                    // Solde SFF PME
                    $soldeSFFPME += $virementEmprunteur[$date]['montant_unilend'] - $virementUnilend[$date]['montant'] + $commission;

                    // Solde Admin. Fiscale
                    $soldeAdminFiscal += $retenuesFiscales - $virementEtat[$date]['montant'];

                    ////////////////////////////
                    /// add regul partie etat fiscal ///

                    $soldeAdminFiscal += $regulCom[$date]['montant_unilend'];

                    ///////////////////////////
                    // somme capital preteurs par jour
                    $capitalPreteur = $echangeDate['capital'];
                    $capitalPreteur += $echangeDateRA['capital'];
                    $capitalPreteur = ($capitalPreteur / 100);
                    $capitalPreteur += $rembRecouvPreteurs[$date]['montant'];

                    // somme net net preteurs par jour
                    $interetNetPreteur = ($echangeDate['interets'] / 100) - $retenuesFiscales;

                    // Affectation Ech. Empr.
                    $affectationEchEmpr = $lrembPreteurs[$date]['montant'] + $lrembPreteurs[$date]['etat'] + $commission + $rembRecouvPreteurs[$date]['montant'];

                    // ecart Mouv Internes
                    $ecartMouvInternes = round(($affectationEchEmpr) - $commission - $retenuesFiscales - $capitalPreteur - $interetNetPreteur, 2);

                    // solde bids validés
                    $octroi_pret = (str_replace('-', '', $virementEmprunteur[$date]['montant']) + $virementEmprunteur[$date]['montant_unilend']);

                    // Virements ok (fichier virements)
                    $virementsOK = $virements->sumVirementsbyDay($date, 'status > 0');

                    //dont sffpme virements (argent gagné a donner a sffpme)
                    $virementsAttente = $virementUnilend[$date]['montant'];

                    // Administration Fiscale
                    $adminFiscalVir = $virementEtat[$date]['montant'];

                    // prelevements
                    $prelevPonctuel = $prelevements->sum('LEFT(added_xml,10) = "' . $date . '" AND status > 0');

                    if (false === empty($listPrel[$date])) {
                        $sommePrelev = $prelevPonctuel + $listPrel[$date];
                    } else {
                        $sommePrelev = $prelevPonctuel;
                    }

                    $sommePrelev = $sommePrelev / 100;

                    $leRembEmprunteur = $rembEmprunteur[$date]['montant'] + $rembEmprunteurRegularisation[$date]['montant'] + $rejetrembEmprunteur[$date]['montant'] + $virementRecouv[$date]['montant'];

                    $totalAlimCB += $alimCB[$date]['montant'];
                    $totalAlimVirement += $alimVirement[$date]['montant'];
                    $totalAlimPrelevement += $alimPrelevement[$date]['montant'];
                    $totalRembEmprunteur += $leRembEmprunteur; // update le 22/01/2015
                    $totalVirementEmprunteur += str_replace('-', '', $virementEmprunteur[$date]['montant']);
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

                    // total solde promotion
                    $totalSoldePromotion = $soldePromotion;

                    // total des offre promo retiré d'un compte prêteur
                    $totalOffrePromo += $offrePromo;

                    // Solde SFF PME
                    $totalSoldeSFFPME = $soldeSFFPME;
                    // Solde Admin. Fiscale
                    $totalSoldeAdminFiscal = $soldeAdminFiscal;

                    $totalOctroi_pret += $octroi_pret;
                    $totalCapitalPreteur += $capitalPreteur;
                    $totalInteretNetPreteur += $interetNetPreteur;
                    $totalEcartMouvInternes += $ecartMouvInternes;
                    $totalVirementsOK += $virementsOK;

                    // dont sff pme
                    $totalVirementsAttente += $virementsAttente;
                    $totaladdsommePrelev += $sommePrelev;
                    $totalAdminFiscalVir += $adminFiscalVir;

                    $tableau .= '
                <tr>
                    <td class="dates">' . (strlen($key) < 2 ? '0' : '') . $key . '/' . $lemoisLannee2 . '</td>
                    <td class="right">' . $oBundle->formatNumber($alimCB[$date]['montant']) . '</td>
                    <td class="right">' . $oBundle->formatNumber($alimVirement[$date]['montant']) . '</td>
                    <td class="right">' . $oBundle->formatNumber($alimPrelevement[$date]['montant']) . '</td>
                    <td class="right">' . $oBundle->formatNumber($unilend_bienvenue[$date]['montant']) . '</td>
                    <td class="right">' . $oBundle->formatNumber($leRembEmprunteur) . '</td>
                    <td class="right">' . $oBundle->formatNumber(str_replace('-', '', $virementEmprunteur[$date]['montant'])) . '</td>
                    <td class="right">' . $oBundle->formatNumber($virementEmprunteur[$date]['montant_unilend']) . '</td>
                    <td class="right">' . $oBundle->formatNumber($commission) . '</td>
                    <td class="right">' . $oBundle->formatNumber($prelevements_obligatoires) . '</td>
                    <td class="right">' . $oBundle->formatNumber($retenues_source) . '</td>
                    <td class="right">' . $oBundle->formatNumber($csg) . '</td>
                    <td class="right">' . $oBundle->formatNumber($prelevements_sociaux) . '</td>
                    <td class="right">' . $oBundle->formatNumber($contributions_additionnelles) . '</td>
                    <td class="right">' . $oBundle->formatNumber($prelevements_solidarite) . '</td>
                    <td class="right">' . $oBundle->formatNumber($crds) . '</td>
                    <td class="right">' . $oBundle->formatNumber(str_replace('-', '', $retraitPreteur[$date]['montant'])) . '</td>
                    <td class="right">' . $oBundle->formatNumber($sommeMouvements) . '</td>
                    <td class="right">' . $oBundle->formatNumber($soldeTheorique) . '</td>
                    <td class="right">' . $oBundle->formatNumber($leSoldeReel) . '</td>
                    <td class="right">' . $oBundle->formatNumber($ecartSoldes) . '</td>
                    <td class="right">' . $oBundle->formatNumber($soldePromotion) . '</td>
                    <td class="right">' . $oBundle->formatNumber($soldeSFFPME) . '</td>
                    <td class="right">' . $oBundle->formatNumber($soldeAdminFiscal) . '</td>
                    <td class="right">' . $oBundle->formatNumber($offrePromo) . '</td>
                    <td class="right">' . $oBundle->formatNumber($octroi_pret) . '</td>
                    <td class="right">' . $oBundle->formatNumber($capitalPreteur) . '</td>
                    <td class="right">' . $oBundle->formatNumber($interetNetPreteur) . '</td>
                    <td class="right">' . $oBundle->formatNumber($affectationEchEmpr) . '</td>
                    <td class="right">' . $oBundle->formatNumber($ecartMouvInternes) . '</td>
                    <td class="right">' . $oBundle->formatNumber($virementsOK) . '</td>
                    <td class="right">' . $oBundle->formatNumber($virementsAttente) . '</td>
                    <td class="right">' . $oBundle->formatNumber($adminFiscalVir) . '</td>
                    <td class="right">' . $oBundle->formatNumber($sommePrelev) . '</td>
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
                <th class="right">' . $oBundle->formatNumber($totalAlimCB) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalAlimVirement) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalAlimPrelevement) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalVirementUnilend_bienvenue) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalRembEmprunteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalVirementEmprunteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalVirementCommissionUnilendEmprunteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalCommission) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalPrelevements_obligatoires) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalRetenues_source) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalCsg) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalPrelevements_sociaux) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalContributions_additionnelles) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalPrelevements_solidarite) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalCrds) . '</th>
                <th class="right">' . $oBundle->formatNumber(str_replace('-', '', $totalRetraitPreteur)) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalSommeMouvements) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalNewsoldeDeLaVeille) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalNewSoldeReel) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalEcartSoldes) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalSoldePromotion) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalSoldeSFFPME) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalSoldeAdminFiscal) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalOffrePromo) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalOctroi_pret) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalCapitalPreteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalInteretNetPreteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalAffectationEchEmpr) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalEcartMouvInternes) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalVirementsOK) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalVirementsAttente) . '</th>
                <th class="right">' . $oBundle->formatNumber($totalAdminFiscalVir) . '</th>
                <th class="right">' . $oBundle->formatNumber($totaladdsommePrelev) . '</th>
            </tr>
        </table>';

            $table = array(
                1  => array('name' => 'totalAlimCB', 'val' => $totalAlimCB),
                2  => array('name' => 'totalAlimVirement', 'val' => $totalAlimVirement),
                3  => array('name' => 'totalAlimPrelevement', 'val' => $totalAlimPrelevement),
                4  => array('name' => 'totalRembEmprunteur', 'val' => $totalRembEmprunteur),
                5  => array('name' => 'totalVirementEmprunteur', 'val' => $totalVirementEmprunteur),
                6  => array('name' => 'totalVirementCommissionUnilendEmprunteur', 'val' => $totalVirementCommissionUnilendEmprunteur),
                7  => array('name' => 'totalCommission', 'val' => $totalCommission),
                8  => array('name' => 'totalPrelevements_obligatoires', 'val' => $totalPrelevements_obligatoires),
                9  => array('name' => 'totalRetenues_source', 'val' => $totalRetenues_source),
                10 => array('name' => 'totalCsg', 'val' => $totalCsg),
                11 => array('name' => 'totalPrelevements_sociaux', 'val' => $totalPrelevements_sociaux),
                12 => array('name' => 'totalContributions_additionnelles', 'val' => $totalContributions_additionnelles),
                13 => array('name' => 'totalPrelevements_solidarite', 'val' => $totalPrelevements_solidarite),
                14 => array('name' => 'totalCrds', 'val' => $totalCrds),
                15 => array('name' => 'totalRetraitPreteur', 'val' => $totalRetraitPreteur),
                16 => array('name' => 'totalSommeMouvements', 'val' => $totalSommeMouvements),
                17 => array('name' => 'totalNewsoldeDeLaVeille', 'val' => $totalNewsoldeDeLaVeille),
                18 => array('name' => 'totalNewSoldeReel', 'val' => $totalNewSoldeReel),
                19 => array('name' => 'totalEcartSoldes', 'val' => $totalEcartSoldes),
                20 => array('name' => 'totalOctroi_pret', 'val' => $totalOctroi_pret),
                21 => array('name' => 'totalCapitalPreteur', 'val' => $totalCapitalPreteur),
                22 => array('name' => 'totalInteretNetPreteur', 'val' => $totalInteretNetPreteur),
                23 => array('name' => 'totalEcartMouvInternes', 'val' => $totalEcartMouvInternes),
                24 => array('name' => 'totalVirementsOK', 'val' => $totalVirementsOK),
                25 => array('name' => 'totalVirementsAttente', 'val' => $totalVirementsAttente),
                26 => array('name' => 'totaladdsommePrelev', 'val' => $totaladdsommePrelev),
                27 => array('name' => 'totalSoldeSFFPME', 'val' => $totalSoldeSFFPME),
                28 => array('name' => 'totalSoldeAdminFiscal', 'val' => $totalSoldeAdminFiscal),
                29 => array('name' => 'totalAdminFiscalVir', 'val' => $totalAdminFiscalVir),
                30 => array('name' => 'totalAffectationEchEmpr', 'val' => $totalAffectationEchEmpr),
                31 => array('name' => 'totalVirementUnilend_bienvenue', 'val' => $totalVirementUnilend_bienvenue),
                32 => array('name' => 'totalSoldePromotion', 'val' => $totalSoldePromotion),
                33 => array('name' => 'totalOffrePromo', 'val' => $totalOffrePromo)
            );

            $etat_quotidien->createEtat_quotidient($table, $leMois, $lannee);

            // on recup toataux du mois de decembre de l'année precedente
            $oldDate           = mktime(0, 0, 0, 12, $jour, $lannee - 1);
            $oldDate           = date('Y-m', $oldDate);
            $etat_quotidienOld = $etat_quotidien->getTotauxbyMonth($oldDate);

            if ($etat_quotidienOld != false) {
                $soldeDeLaVeille      = $etat_quotidienOld['totalNewsoldeDeLaVeille'];
                $soldeReel            = $etat_quotidienOld['totalNewSoldeReel'];
                $soldeSFFPME_old      = $etat_quotidienOld['totalSoldeSFFPME'];
                $soldeAdminFiscal_old = $etat_quotidienOld['totalSoldeAdminFiscal'];
                $soldePromotion_old   = $etat_quotidienOld['totalSoldePromotion'];
            } else {
                // Solde theorique
                $soldeDeLaVeille = 0;

                // solde reel
                $soldeReel            = 0;
                $soldeSFFPME_old      = 0;
                $soldeAdminFiscal_old = 0;
                $soldePromotion_old   = 0;
            }

            // ecart
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
                <td class="right">' . $oBundle->formatNumber($soldeDeLaVeille) . '</td>
                <td class="right">' . $oBundle->formatNumber($soldeReel) . '</td>
                <td class="right">' . $oBundle->formatNumber($oldecart) . '</td>
                <td class="right">' . $oBundle->formatNumber($soldePromotion_old) . '</td>
                <td class="right">' . $oBundle->formatNumber($soldeSFFPME_old) . '</td>
                <td class="right">' . $oBundle->formatNumber($soldeAdminFiscal_old) . '</td>
                <td colspan="10">&nbsp;</td>
            </tr>';

            $sommetotalAlimCB                              = 0;
            $sommetotalAlimVirement                        = 0;
            $sommetotalAlimPrelevement                     = 0;
            $sommetotalRembEmprunteur                      = 0;
            $sommetotalVirementEmprunteur                  = 0;
            $sommetotalVirementCommissionUnilendEmprunteur = 0;
            $sommetotalCommission                          = 0;

            // Retenues fiscales
            $sommetotalPrelevements_obligatoires    = 0;
            $sommetotalRetenues_source              = 0;
            $sommetotalCsg                          = 0;
            $sommetotalPrelevements_sociaux         = 0;
            $sommetotalContributions_additionnelles = 0;
            $sommetotalPrelevements_solidarite      = 0;
            $sommetotalCrds                         = 0;
            $sommetotalAffectationEchEmpr           = 0;

            // Remboursements aux prêteurs
            $sommetotalRetraitPreteur = 0;

            $sommetotalSommeMouvements = 0;

            $sommetotalNewsoldeDeLaVeille = 0;
            $sommetotalNewSoldeReel       = 0;
            $sommetotalEcartSoldes        = 0;
            $sommetotalSoldeSFFPME        = 0;
            $sommetotalSoldeAdminFiscal   = 0;
            $sommetotalSoldePromotion     = 0;

            // Mouvements internes
            $sommetotalOctroi_pret       = 0;
            $sommetotalCapitalPreteur    = 0;
            $sommetotalInteretNetPreteur = 0;
            $sommetotalEcartMouvInternes = 0;

            // Virements
            $sommetotalVirementsOK      = 0;
            $sommetotalVirementsAttente = 0;
            $sommetotalAdminFiscalVir   = 0;

            // Prélèvements
            $sommetotaladdsommePrelev = 0;

            $sommetotalVirementUnilend_bienvenue = 0;

            $sommetotalOffrePromo = 0;

            for ($i = 1; $i <= 12; $i++) {
                if (strlen($i) < 2) {
                    $numMois = '0' . $i;
                } else {
                    $numMois = $i;
                }

                $lemois = $etat_quotidien->getTotauxbyMonth($lannee . '-' . $numMois);

                $sommetotalAlimCB += $lemois['totalAlimCB'];
                $sommetotalAlimVirement += $lemois['totalAlimVirement'];
                $sommetotalAlimPrelevement += $lemois['totalAlimPrelevement'];
                $sommetotalRembEmprunteur += $lemois['totalRembEmprunteur'];
                $sommetotalVirementEmprunteur += $lemois['totalVirementEmprunteur'];
                $sommetotalVirementCommissionUnilendEmprunteur += $lemois['totalVirementCommissionUnilendEmprunteur'];
                $sommetotalCommission += $lemois['totalCommission'];

                $sommetotalVirementUnilend_bienvenue += $lemois['totalVirementUnilend_bienvenue'];

                $sommetotalOffrePromo += $lemois['totalOffrePromo'];

                // Retenues fiscales
                $sommetotalPrelevements_obligatoires += $lemois['totalPrelevements_obligatoires'];
                $sommetotalRetenues_source += $lemois['totalRetenues_source'];
                $sommetotalCsg += $lemois['totalCsg'];
                $sommetotalPrelevements_sociaux += $lemois['totalPrelevements_sociaux'];
                $sommetotalContributions_additionnelles += $lemois['totalContributions_additionnelles'];
                $sommetotalPrelevements_solidarite += $lemois['totalPrelevements_solidarite'];
                $sommetotalCrds += $lemois['totalCrds'];

                // Remboursements aux prêteurs
                $sommetotalRetraitPreteur += $lemois['totalRetraitPreteur'];

                $sommetotalSommeMouvements += $lemois['totalSommeMouvements'];

                // Soldes
                if ($lemois != false) {
                    $sommetotalNewsoldeDeLaVeille = $lemois['totalNewsoldeDeLaVeille'];
                    $sommetotalNewSoldeReel       = $lemois['totalNewSoldeReel'];
                    $sommetotalEcartSoldes        = $lemois['totalEcartSoldes'];
                    $sommetotalSoldeSFFPME        = $lemois['totalSoldeSFFPME'];
                    $sommetotalSoldeAdminFiscal   = $lemois['totalSoldeAdminFiscal'];
                    $sommetotalSoldePromotion     = $lemois['totalSoldePromotion'];
                }

                // Mouvements internes
                $sommetotalOctroi_pret += $lemois['totalOctroi_pret'];
                $sommetotalCapitalPreteur += $lemois['totalCapitalPreteur'];
                $sommetotalInteretNetPreteur += $lemois['totalInteretNetPreteur'];
                $sommetotalEcartMouvInternes += $lemois['totalEcartMouvInternes'];

                // Virements
                $sommetotalVirementsOK += $lemois['totalVirementsOK'];
                $sommetotalVirementsAttente += $lemois['totalVirementsAttente'];
                $sommetotalAdminFiscalVir += $lemois['totalAdminFiscalVir'];

                // Prélèvements
                $sommetotaladdsommePrelev += $lemois['totaladdsommePrelev'];

                $sommetotalAffectationEchEmpr += $lemois['totalAffectationEchEmpr'];

                $tableau .= '
                <tr>
                    <th>' . $oDates->tableauMois['fr'][$i] . '</th>';

                if ($lemois != false) {
                    $tableau .= '
                        <td class="right">' . $oBundle->formatNumber($lemois['totalAlimCB']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalAlimVirement']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalAlimPrelevement']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalVirementUnilend_bienvenue']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalRembEmprunteur']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalVirementEmprunteur']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalVirementCommissionUnilendEmprunteur']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalCommission']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalPrelevements_obligatoires']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalRetenues_source']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalCsg']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalPrelevements_sociaux']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalContributions_additionnelles']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalPrelevements_solidarite']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalCrds']) . '</td>
                        <td class="right">' . $oBundle->formatNumber(str_replace('-', '', $lemois['totalRetraitPreteur'])) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalSommeMouvements']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalNewsoldeDeLaVeille']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalNewSoldeReel']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalEcartSoldes']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalSoldePromotion']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalSoldeSFFPME']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalSoldeAdminFiscal']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalOffrePromo']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalOctroi_pret']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalCapitalPreteur']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalInteretNetPreteur']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalAffectationEchEmpr']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalEcartMouvInternes']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalVirementsOK']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalVirementsAttente']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totalAdminFiscalVir']) . '</td>
                        <td class="right">' . $oBundle->formatNumber($lemois['totaladdsommePrelev']) . '</td>';
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
                <th class="right">' . $oBundle->formatNumber($sommetotalAlimCB) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalAlimVirement) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalAlimPrelevement) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalVirementUnilend_bienvenue) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalRembEmprunteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalVirementEmprunteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalVirementCommissionUnilendEmprunteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalCommission) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalPrelevements_obligatoires) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalRetenues_source) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalCsg) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalPrelevements_sociaux) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalContributions_additionnelles) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalPrelevements_solidarite) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalCrds) . '</th>
                <th class="right">' . $oBundle->formatNumber(str_replace('-', '', $sommetotalRetraitPreteur)) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalSommeMouvements) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalNewsoldeDeLaVeille) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalNewSoldeReel) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalEcartSoldes) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalSoldePromotion) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalSoldeSFFPME) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalSoldeAdminFiscal) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalOffrePromo) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalOctroi_pret) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalCapitalPreteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalInteretNetPreteur) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalAffectationEchEmpr) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalEcartMouvInternes) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalVirementsOK) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalVirementsAttente) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotalAdminFiscalVir) . '</th>
                <th class="right">' . $oBundle->formatNumber($sommetotaladdsommePrelev) . '</th>
            </tr>
        </table>';

            $filename = 'Unilend_etat_' . date('Ymd', $iTimeStamp);

            file_put_contents($this->path . 'protected/sftp/etat_quotidien/' . $filename . '.xls', $tableau);

            $this->stopCron();
        }
    }
}