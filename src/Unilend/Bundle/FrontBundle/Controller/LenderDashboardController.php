<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\Translator;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;
use Unilend\Bundle\FrontBundle\Service\LenderAccountDisplayManager;
use Unilend\core\Loader;

class LenderDashboardController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("synthese", name="lender_dashboard")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function indexAction(Request $request)
    {
        /** @var \dates dates */
        $this->dates = Loader::loadLib('dates');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var Translator $translator */
        $translator = $this->get('translator');
        /** @var \lenders_account_stats $oLenderAccountStats */
        $oLenderAccountStats = $entityManager->getRepository('lenders_account_stats');

        $this->transactions            = $entityManager->getRepository('transactions');
        $this->loans                   = $entityManager->getRepository('loans');
        $this->echeanciers             = $entityManager->getRepository('echeanciers');
        $this->projects                = $entityManager->getRepository('projects');
        $this->favoris                 = $entityManager->getRepository('favoris');
        $this->companies               = $entityManager->getRepository('companies');
        $this->favoris                 = $entityManager->getRepository('favoris');
        $this->bids                    = $entityManager->getRepository('bids');
        $this->wallets_lines           = $entityManager->getRepository('wallets_lines');
        $this->projects_status         = $entityManager->getRepository('projects_status');
        $this->notifications           = $entityManager->getRepository('notifications');
        $this->clients_status          = $entityManager->getRepository('clients_status');
        $this->clients_status_history  = $entityManager->getRepository('clients_status_history');
        $this->acceptations_legal_docs = $entityManager->getRepository('acceptations_legal_docs');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');


        $client->get($this->getUser()->getClientId());
        $lender->get($client->id_client, 'id_client_owner');

        $lender->get($client->id_client, 'id_client_owner');

        $balance = $this->getUser()->getBalance();

        if (in_array($client->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
            $settings->get('Lien conditions generales inscription preteur societe', 'type');
            $this->lienConditionsGenerales = $settings->value;
        } else {
            $settings->get('Lien conditions generales inscription preteur particulier', 'type');
            $this->lienConditionsGenerales = $settings->value;
        }


        $settings->get('Heure fin periode funding', 'type');
        $this->heureFinFunding = $settings->value;

        $lesFav = $this->favoris->projetsFavorisPreteur($client->id_client);

        if ($lesFav == false) {
            $this->lProjetsFav = 0;
        } else {
            $this->lProjetsFav = $this->projects->select('id_project IN (' . $lesFav . ')');
            foreach ($this->lProjetsFav as $iKey => $aProject) {
                $this->lProjetsFav[$iKey]['avgrate'] = $this->projects->getAverageInterestRate($aProject['id_project']);
            }
        }

        $this->lProjetEncours = $this->projects->selectProjectsByStatus([\projects_status::EN_FUNDING], '', [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_ASC], 0, 30);

        foreach ($this->lProjetEncours as $iKey => $aProject) {
            $this->lProjetEncours[$iKey]['avgrate'] = $this->projects->getAverageInterestRate($aProject['id_project'], $aProject['status']);
        }

        $this->nbLoan         = $this->loans->getProjectsCount($lender->id_lender_account);
        $this->sumBidsEncours = $this->bids->sumBidsEncours($lender->id_lender_account);
        $this->sumPrets       = $this->loans->sumPrets($lender->id_lender_account);
        $this->sumRembMontant = $this->echeanciers->getSumRemb($lender->id_lender_account, 'capital');

        // somme retant du (capital) (a rajouter en prod)
        $ProblematicProjects    = $this->echeanciers->getProblematicProjects($lender->id_lender_account);
        $this->nbProblems       = $ProblematicProjects['projects'];
        $this->sumProblems      = $ProblematicProjects['capital'];
        $this->sumRestanteARemb = $this->echeanciers->getSumARemb($lender->id_lender_account, 'capital') - $this->sumProblems;

        $this->sumRevenuesFiscalesRemb = $this->echeanciers->getSumRevenuesFiscalesRemb($lender->id_lender_account . ' AND status_ra = 0');

        $this->sumInterets = $this->echeanciers->getSumRemb($lender->id_lender_account . ' AND status_ra = 0', 'interets');
        $this->sumInterets -= $this->sumRevenuesFiscalesRemb; // interets net

        $total = $balance + $this->sumBidsEncours + $this->sumRestanteARemb;

        $this->soldePourcent          = $total > 0 ? round($balance / $total * 100, 1) : 0;
        $this->sumBidsEncoursPourcent = $total > 0 ? round($this->sumBidsEncours / $total * 100, 1) : 0;
        $this->sumPretsPourcent       = $total > 0 ? round($this->sumRestanteARemb / $total * 100, 1) : 0;
        $this->sumProblemsPourcent    = $total > 0 ? round($this->sumProblems / $total * 100, 1) : 0;

        $anneeCreationCompte = date('Y', strtotime($client->added));

        $this->arrayMois = array(
            '1'  => 'JAN',
            '2'  => 'FEV',
            '3'  => 'MAR',
            '4'  => 'AVR',
            '5'  => 'MAI',
            '6'  => 'JUIN',
            '7'  => 'JUIL',
            '8'  => 'AOUT',
            '9'  => 'SEPT',
            '10' => 'OCT',
            '11' => 'NOV',
            '12' => 'DEC'
        );

        $c = 1;
        $d = 0;

        for ($annee = $anneeCreationCompte; $annee <= date('Y'); $annee++) {
            $tabSumRembParMois[$annee]             = $this->echeanciers->getSumRembByMonthsCapital($lender->id_lender_account, $annee); // captial remboursé / mois
            $tabSumIntbParMois[$annee]             = $this->echeanciers->getSumIntByMonths($lender->id_lender_account . ' AND status_ra = 0 ', $annee); // intérets brut / mois
            $tabSumRevenuesfiscalesParMois[$annee] = $this->echeanciers->getSumRevenuesFiscalesByMonths($lender->id_lender_account . ' AND status_ra = 0 ', $annee);

            for ($i = 1; $i <= 12; $i++) {
                $a                                            = $i;
                $a                                            = ($i < 10 ? '0' . $a : $a);
                $this->sumRembParMois[$annee][$i]             = number_format(isset($tabSumRembParMois[$annee][$a]) ? $tabSumRembParMois[$annee][$a] : 0, 2, '.', ''); // capital remboursé / mois
                $this->sumIntbParMois[$annee][$i]             = number_format(isset($tabSumIntbParMois[$annee][$a]) ? $tabSumIntbParMois[$annee][$a] - $tabSumRevenuesfiscalesParMois[$annee][$a] : 0,
                    2, '.', ''); // interets net / mois
                $this->sumRevenuesfiscalesParMois[$annee][$i] = number_format(isset($tabSumRevenuesfiscalesParMois[$annee][$a]) ? $tabSumRevenuesfiscalesParMois[$annee][$a] : 0, 2, '.',
                    ''); // prelevements fiscaux

                // on organise l'affichage
                if ($d == 3) {
                    $d = 0;
                    $c += 1;
                }
                $this->lesmois[$annee . '_' . $i] = $c;
                $nbSlides                         = $c;
                $d++;
            }
        }

        // On organise l'afichage partie 2
        $a = 1;
        for ($i = 1; $i <= $nbSlides; $i++) {
            // On recup a partir de la date du jour
            if ($this->lesmois[date('Y_n')] <= $i) {
                $this->ordre[$a] = $i;
                $a++;
            } else {
                $tabPositionsAvavant[$i] = $i;
            }
        }

        // On recupe la derniere clé
        $this->TabTempOrdre = $this->ordre;
        end($this->TabTempOrdre);
        $lastKey = key($this->TabTempOrdre);

        // On assemble le tout comme ca tout est dans le bon ordre d'affichage
        $position = $lastKey + 1;
        if ($tabPositionsAvavant != false) {
            foreach ($tabPositionsAvavant as $p) {
                $this->ordre[$position] = $p;
                $position++;
            }
        }

        $this->lFavP   = $this->projects->getDerniersFav($client->id_client);
        $this->lRejetB = $this->notifications->select('id_lender = ' . $lender->id_lender_account . ' AND type = 1 AND status = 0');
        $this->lRembB  = $this->notifications->select('id_lender = ' . $lender->id_lender_account . ' AND type = 2 AND status = 0');

        $this->nblFavP   = count($this->lFavP);
        $this->nblRejetB = count($this->lRejetB);
        $this->nblRembB  = count($this->lRembB);

        // statut client
        $this->clients_status->getLastStatut($client->id_client);


        $this->iDiversificationLevel = '';
        $this->sDisplayedValue       = '';
        $this->sTypeMessageTooltip   = '';
        $this->sDisplayedMessage     = '';
        $this->sDate                 = null;
        $this->iNumberOfCompanies    = $lender->countCompaniesLenderInvestedIn($lender->id_lender_account);


        if ($this->iNumberOfCompanies === 0) {
            $this->iDiversificationLevel = 0;
            $this->sDisplayedMessage     = str_replace('[#SURL#]', $this->surl, '4');
        }

        if ($this->iNumberOfCompanies >= 1 && $this->iNumberOfCompanies <= 19) {
            $this->iDiversificationLevel = 1;
        }

        if ($this->iNumberOfCompanies >= 20 && $this->iNumberOfCompanies <= 49) {
            $this->iDiversificationLevel = 2;
        }

        if ($this->iNumberOfCompanies >= 50 && $this->iNumberOfCompanies <= 79) {
            $this->iDiversificationLevel = 3;
        }

        if ($this->iNumberOfCompanies >= 80 && $this->iNumberOfCompanies <= 119) {
            $this->iDiversificationLevel = 4;
        }

        if ($this->iNumberOfCompanies >= 120) {
            $this->iDiversificationLevel = 5;
        }

        if ($this->iNumberOfCompanies > 0) {
            $aLastIRR = $oLenderAccountStats->getLastIRRForLender($lender->id_lender_account);
            if ($aLastIRR) {
                $this->sDate               = $this->dates->formatDateMysqltoFrTxtMonth($aLastIRR['tri_date']);
                $this->sDisplayedValue     = ($aLastIRR['tri_value'] > 0) ? '+ ' . $ficelle->formatNumber($aLastIRR['tri_value']) . '%' : $ficelle->formatNumber($aLastIRR['tri_value']) . '%';
                $this->bIRRIsNegative      = ($aLastIRR['tri_value'] > 0) ? false : true;
                $this->sTypeMessageTooltip = 'tri';
                $this->sDisplayedMessage   = $translator->trans('lender-dashboard_irr-' . (($aLastIRR['tri_value'] > 0) ? 'positive-level-' : 'negative-level-') . $this->iDiversificationLevel);
            } else {
                $fLossRate = $oLenderAccountStats->getLossRate($lender->id_lender_account, $lender);

                if ($fLossRate > 0) {
                    $this->sDisplayedValue     = $ficelle->formatNumber(-$fLossRate) . '%';
                    $this->bHasIRR             = false;
                    $this->sTypeMessageTooltip = 'taux-de-perte';
                    $this->sDisplayedMessage   = str_replace('[#SURL#]', $this->surl, $translator->trans('lender-dashboard_irr-not-calculable'));

                    $this->sDate = $this->dates->formatDateMysqltoFrTxtMonth(date('Y-m-d'));
                } else {
                    $this->sDisplayedValue     = '';
                    $this->sTypeMessageTooltip = 'tri';
                    $this->sDisplayedMessage   = $translator->trans('lender-dashboard_irr-not-calculated-yet');
                }
            }
        }

        //Ongoing Bids Widget
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
        $oAutoBidSettingsManager         = $this->get('unilend.service.autobid_settings_manager');
        $this->bIsAllowedToSeeAutobid    = $oAutoBidSettingsManager->isQualified($lender);
        $this->bFirstTimeActivation      = ! $oAutoBidSettingsManager->hasAutoBidActivationHistory($lender);
        $this->iDisplayTotalNumberOfBids = $this->bids->counter('id_lender_account = ' . $lender->id_lender_account);
        $aProjectsWithBids               = array();

        foreach ($this->lProjetEncours as $iKey => $aProject) {
            if (0 < $this->bids->counter('id_project = ' . $aProject['id_project'] . ' AND id_lender_account = ' . $lender->id_lender_account)) {
                $this->aOngoingBidsByProject[$iKey]                 = $aProject;
                $this->aOngoingBidsByProject[$iKey]['oEndFunding']  = \DateTime::createFromFormat('Y-m-d H:i:s', $aProject['date_retrait_full']);
                $this->aOngoingBidsByProject[$iKey]['aPendingBids'] = $this->bids->select(
                    'id_project = ' . $aProject['id_project'] .
                    ' AND id_lender_account = ' . $lender->id_lender_account .
                    ' AND status = ' . \bids::STATUS_BID_PENDING,
                    'id_bid DESC'
                );

                $bids = $this->bids->select(
                    'id_project = ' . $aProject['id_project'] .
                    ' AND id_lender_account = ' . $lender->id_lender_account .
                    ' AND status = ' . \bids::STATUS_BID_REJECTED,
                    'id_bid DESC',
                    null,
                    '1'
                );

                $this->aOngoingBidsByProject[$iKey]['aRejectedBid']          = array_shift($bids);
                $this->aOngoingBidsByProject[$iKey]['iNumberOfRejectedBids'] = $this->bids->counter('id_project = ' . $aProject['id_project'] .
                    ' AND id_lender_account = ' . $lender->id_lender_account .
                    ' AND status = ' . \bids::STATUS_BID_REJECTED);

                $aProjectsWithBids[] = $aProject['id_project'];
            }
        }
        $this->bHasNoBidsOnProjectsInFunding = (0 === count($aProjectsWithBids));

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\IRRManager $oIRRManager */
        $oIRRManager = $this->get('unilend.service.irr_manager');
        /** @var LenderAccountDisplayManager $lenderDisplayManager */
        $lenderDisplayManager = $this->get('unilend.frontbundle.service.lender_account_display_manager');

        $aLastUnilendIRR   = $oIRRManager->getLastUnilendIRR();
        $this->sIRRUnilend = $ficelle->formatNumber((float) $aLastUnilendIRR['value']);

        return $this->render(
            '/pages/lender_dashboard/lender_dashboard.html.twig',
            [
                'dashboardPanels'  => $this->getDashboardPreferences(),
                'lenderDetails'    => [
                    'balance'             => $balance,
                    'level'               => $this->getUser()->getLevel(),
                    'unilend_irr'         => $this->sIRRUnilend,
                    'irr'                 => $this->sDisplayedValue,
                    'initials'            => $this->getUser()->getInitials(),
                    'number_of_companies' => $this->iNumberOfCompanies
                ],
                'walletData'       => [
                    'by_sector' => $lenderDisplayManager->getLenderLoansAllocationByCompanySector($lender->id_lender_account),
                    'by_region' => $lenderDisplayManager->getLenderLoansAllocationByRegion($lender->id_lender_account),
                ],
                'amountDetails'    => [
                    'loaned_amount'     => round($this->sumPrets, 2),
                    'blocked_amount'    => round($this->sumBidsEncours, 2),
                    /**@todo use calculated amount instead of of using hard-coded value */
                    'expected_earnings' => 123.55,
                    'deposited_amount'   => $this->wallets_lines->getSumDepot($lender->id_lender_account, '10,30')
                ],
                'capitalDetails'   => ['repaid_capital' => round($this->sumRembMontant, 2), 'owed_capital' => round($this->sumRestanteARemb, 2), 'capital_in_difficulty' => round($this->sumProblems, 2)],
                /** @todo Ask for interests amount : dow display net or not? */
                'interestsDetails' => ['received_interests' => round($this->sumInterets, 2), 'upcoming_interests' => round($this->sumRestanteARemb, 2)/**@todo calculer le vrai montant des intrêts à venir à base d'echeancier */]
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("synthese/preferences", name="save_user_preferences")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function saveUserDisplayPreferences(Request $request)
    {
        /** @var \user_preferences $userPreferences */
        $userPreferences = $this->get('unilend.service.entity_manager')->getRepository('user_preferences');

        $pageName = 'lender_dashboard';

        $postData = $request->request->get('panels');

        if ($request->getMethod() === 'PUT') {
            try {
                $preferences = $userPreferences->getUserPreferencesByPage($this->getUser()->getClientId(), $pageName);

                foreach ($postData as $panel) {
                    if (isset($preferences[$panel['id']])) {
                        if ($preferences[$panel['id']]['hidden'] != $panel['hidden'] || $preferences[$panel['id']]['panel_order'] != $panel['order']) {
                            $userPreferences->get($preferences[$panel['id']]['id_user_preferences']);
                            $userPreferences->hidden      = ('true' == $panel['hidden']) ? 1 : 0;
                            $userPreferences->panel_order = $panel['order'];
                            $userPreferences->updated     = date('Y-m-d H:i:s');
                            $userPreferences->update();
                        }
                    } else {
                        $userPreferences->id_client   = $this->getUser()->getClientId();
                        $userPreferences->page_name   = $pageName;
                        $userPreferences->panel_name  = $panel['id'];
                        $userPreferences->panel_order = $panel['order'];
                        $userPreferences->hidden      = ('true' == $panel['hidden']) ? 1 : 0;
                        $userPreferences->added       = date('Y-m-d H:i:s');
                        $userPreferences->updated     = date('Y-m-d H:i:s');

                        $userPreferences->create();
                    }
                }
                $result = ['success' => 1, 'data' => $postData, 'preferences' => $preferences];
            } catch (\Exception $exception) {
                $result = ['error' => 1, 'msg' => $exception->getMessage(), 'data' => $postData];
            }
        } elseif ($request->getMethod() === 'GET') {
            try {
                $preferences = $userPreferences->getUserPreferencesByPage($this->getUser()->getClientId(), $pageName);

                if (false === empty($preferences)) {
                    foreach ($preferences as $panelName => $panel) {
                        $data['panels'][] = ['id' => $panelName, 'order' => $panel['panel_order'], 'hidden' => ($panel['hidden'] == 1) ? true : false];
                    }
                }
                $result = ['success' => 1, 'data' => $data, 'preferences' => $preferences];
            } catch (\Exception $exception) {
                $result = ['error' => 1, 'msg' => $exception->getMessage(), 'data' => $request->query->all()];
            }
        }

        return $this->json($result);
    }

    /**
     * @return array
     */
    private function getDashboardPreferences()
    {
        /** @var \user_preferences $userPreferences */
        $userPreferences = $this->get('unilend.service.entity_manager')->getRepository('user_preferences');

        $pageName           = 'lender_dashboard';
        $defaultPreferences = [
            'myaccount'   => ['order' => 0, 'id' => 'myaccount', 'hidden' => false],
            'userlevel'   => ['order' => 1, 'id' => 'userlevel', 'hidden' => false],
            'mywallet'    => ['order' => 2, 'id' => 'mywallet', 'hidden' => false],
            'myoffers'    => ['order' => 3, 'id' => 'myoffers', 'hidden' => false],
            'projects'    => ['order' => 4, 'id' => 'projects', 'hidden' => false],
            'myrembourse' => ['order' => 5, 'id' => 'myrembourse', 'hidden' => false],
        ];
        try {
            $preferences = $userPreferences->getUserPreferencesByPage($this->getUser()->getClientId(), $pageName);

            if (false === empty($preferences)) {

                foreach ($preferences as $panelName => $panel) {
                    $userPreferencesData[$panelName] = ['id' => $panelName, 'order' => $panel['panel_order'], 'hidden' => ($panel['hidden'] == 1) ? true : false];
                }
            } else {
                $userPreferencesData = $defaultPreferences;
            }
        } catch (\Exception $exception) {
            $userPreferencesData = $defaultPreferences;
        }
        return $userPreferencesData;
    }
}