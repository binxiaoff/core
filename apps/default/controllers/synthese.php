<?php

class syntheseController extends bootstrap
{
    var $Command;

    function syntheseController($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        $this->setHeader('header_account');

        if ( ! $this->clients->checkAccess()) {
            header('Location:' . $this->lurl);
            die;
        } else {
            $this->clients->checkStatusPreEmp($this->clients->status_pre_emp, 'preteur', $this->clients->id_client);
        }

        $this->lng['preteur-projets']  = $this->ln->selectFront('preteur-projets', $this->language, $this->App);
        $this->lng['preteur-synthese'] = $this->ln->selectFront('preteur-synthese', $this->language, $this->App);

        $this->settings->get('Heure fin periode funding', 'type');
        $this->heureFinFunding = $this->settings->value;
        $this->page            = 'synthese';
    }

    function _default()
    {
        $this->loadCss('default/synthese1');

        $this->transactions            = $this->loadData('transactions');
        $this->lenders_accounts        = $this->loadData('lenders_accounts');
        $this->loans                   = $this->loadData('loans');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->projects                = $this->loadData('projects');
        $this->favoris                 = $this->loadData('favoris');
        $this->companies               = $this->loadData('companies');
        $this->companies_details       = $this->loadData('companies_details');
        $this->favoris                 = $this->loadData('favoris');
        $this->bids                    = $this->loadData('bids');
        $this->wallets_lines           = $this->loadData('wallets_lines');
        $this->projects_status         = $this->loadData('projects_status');
        $this->notifications           = $this->loadData('notifications');
        $this->clients_status          = $this->loadData('clients_status');
        $this->clients_status_history  = $this->loadData('clients_status_history');
        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');

        // Recuperation du bloc nos-partenaires
        $this->blocs->get('cgv', 'slug');
        $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->bloc_cgv[$this->elements->slug]           = $b_elt['value'];
            $this->bloc_cgvComplement[$this->elements->slug] = $b_elt['complement'];
        }

        // form qs
        if (isset($_POST['send_form_qs'])) {
            $form_ok = true;
            if ( ! isset($_POST['secret-question']) || $_POST['secret-question'] == '') {
                $form_ok = false;
            }
            if ( ! isset($_POST['secret-response']) || $_POST['secret-response'] == '') {
                $form_ok = false;
            }
            if ( ! in_array('', array($this->clients->secrete_question, $this->clients->secrete_reponse))) {
                $form_ok = false;
            }

            // form ok
            if ($form_ok == true) {
                $this->clients->secrete_question = $_POST['secret-question'];
                $this->clients->secrete_reponse  = md5($_POST['secret-response']);
                $this->clients->update();

                $_SESSION['qs_ok'] = 'OK';

                header('Location:' . $this->lurl . '/synthese');
                die;
            }
        }

        // cgu societe
        if (in_array($this->clients->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
            $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
            $this->lienConditionsGenerales = $this->settings->value;
        } else {
            $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
            $this->lienConditionsGenerales = $this->settings->value;
        }

        $listeAccept = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);

        $this->update_accept = false;

        // cherche si on a déjà le cgv
        if (in_array($this->lienConditionsGenerales, $listeAccept)) {
            $this->accept_ok = true;
        } else {
            $this->accept_ok = false;
            // Si on a deja des cgv d'accepté
            if ($listeAccept != false) {
                $this->update_accept = true;
            }
        }

        $this->settings->get('Heure fin periode funding', 'type');
        $this->heureFinFunding = $this->settings->value;
        $this->lenders_accounts->get($this->clients->id_client, 'id_client_owner');

        // On recupere les projets favoris
        $lesFav = $this->favoris->projetsFavorisPreteur($this->clients->id_client);

        // Liste des projets favoris
        if ($lesFav == false) {
            $this->lProjetsFav = 0;
        } else {
            $this->lProjetsFav = $this->projects->select('id_project IN (' . $lesFav . ')');
        }

        // Liste des projets en cours (projets a decouvrir)
        $this->lProjetEncours = $this->projects->selectProjectsByStatus(\projects_status::EN_FUNDING, $where_not_in, 'p.date_retrait ASC', 0, 30);

        $this->nbLoan = $this->loans->getProjectsCount($this->lenders_accounts->id_lender_account);

        // somme des bids en cours
        $this->sumBidsEncours = $this->bids->sumBidsEncours($this->lenders_accounts->id_lender_account);

        if ($_SERVER['REMOTE_ADDR'] == '93.26.42.98') {
            echo "o";
        }

        // somme Prêté
        $this->sumPrets = $this->loans->sumPrets($this->lenders_accounts->id_lender_account);

        // somme remboursé
        $this->sumRembMontant = $this->echeanciers->getSumRemb($this->lenders_accounts->id_lender_account, 'capital');
        // somme retant du (capital) (a rajouter en prod)
        $this->sumRestanteARemb = $this->echeanciers->getSumARemb($this->lenders_accounts->id_lender_account, 'capital', 'dashboard');

        // somme retenues fiscales remboursés
        $this->sumRevenuesFiscalesRemb = $this->echeanciers->getSumRevenuesFiscalesRemb($this->lenders_accounts->id_lender_account . ' AND status_ra = 0');

        // somme des interets
        $this->sumInterets = $this->echeanciers->getSumRemb($this->lenders_accounts->id_lender_account . ' AND status_ra = 0', 'interets');
        $this->sumInterets -= $this->sumRevenuesFiscalesRemb; // interets net

        $total = $this->solde + $this->sumBidsEncours + $this->sumPrets; // solde + bids en cours + prets validés

        $this->soldePourcent          = @round(($this->solde / $total) * 100, 1); // solde du compte en pourcentage
        $this->sumBidsEncoursPourcent = @round(($this->sumBidsEncours / $total) * 100, 1); // bids en pourcentage
        $this->sumPretsPourcent       = @round(($this->sumPrets / $total) * 100, 1); // pret en pourcentage

        $this->SumDepot = $this->wallets_lines->getSumDepot($this->lenders_accounts->id_lender_account, '10,30'); // sommes deposé

        // Année de creation
        $anneeCreationCompte = date('Y', strtotime($this->clients->added));

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

        // variables pour une boucle
        $c = 1;
        $d = 0;

        // On parcourt toutes les années de la creation du compte a aujourd'hui
        for ($annee = $anneeCreationCompte; $annee <= date('Y'); $annee++) {
            // Revenus mensuel
            $tabSumRembParMois[$annee]             = $this->echeanciers->getSumRembByMonthsCapital($this->lenders_accounts->id_lender_account, $annee); // captial remboursé / mois
            $tabSumIntbParMois[$annee]             = $this->echeanciers->getSumIntByMonths($this->lenders_accounts->id_lender_account . ' AND status_ra = 0 ', $annee); // intérets brut / mois
            $tabSumRevenuesfiscalesParMois[$annee] = $this->echeanciers->getSumRevenuesFiscalesByMonths($this->lenders_accounts->id_lender_account . ' AND status_ra = 0 ', $annee); // revenues fiscales / mois

            // on fait le tour sur l'année
            for ($i = 1; $i <= 12; $i++) {
                $a                                            = $i;
                $a                                            = ($i < 10 ? '0' . $a : $a);
                $this->sumRembParMois[$annee][$i]             = number_format(($tabSumRembParMois[$annee][$a] != '' ? $tabSumRembParMois[$annee][$a] : 0), 2, '.', ''); // capital remboursé / mois
                $this->sumIntbParMois[$annee][$i]             = number_format(($tabSumIntbParMois[$annee][$a] != '' ? $tabSumIntbParMois[$annee][$a] - $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // interets net / mois
                $this->sumRevenuesfiscalesParMois[$annee][$i] = number_format(($tabSumRevenuesfiscalesParMois[$annee][$a] != '' ? $tabSumRevenuesfiscalesParMois[$annee][$a] : 0), 2, '.', ''); // prelevements fiscaux

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

        $this->lFavP   = $this->projects->getDerniersFav($this->clients->id_client);
        $this->lRejetB = $this->notifications->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND type = 1 AND status = 0');
        $this->lRembB  = $this->notifications->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND type = 2 AND status = 0');

        $this->nblFavP   = count($this->lFavP);
        $this->nblRejetB = count($this->lRejetB);
        $this->nblRembB  = count($this->lRembB);

        // statut client
        $this->clients_status->getLastStatut($this->clients->id_client);

        //Ongoing Bids Widget
        $oBids          = $this->loadData('bids');
        $oProjects      = $this->loadData('projects');
        $oLenderAccount = $this->loadData('lenders_accounts');
        $oLenderAccount->get($this->clients->id_client, 'id_client_owner');

        $aProjectsInFunding = $oProjects->selectProjectsByStatus(\projects_status::EN_FUNDING, null, 'date_retrait_full ASC');

        foreach ($aProjectsInFunding as $iKey => $aProject) {
            $aProjectsInFunding[$iKey]['oEndFunding']  = \DateTime::createFromFormat('Y-m-d H:i:s', $aProject['date_retrait_full']);
            $aProjectsInFunding[$iKey]['aPendingBids'] = $oBids->select('id_project = ' . $aProject['id_project'] . ' AND id_lender_account = ' . $oLenderAccount->id_lender_account . ' AND status = ' . \bids::STATUS_BID_PENDING, 'id_bid DESC');
            $aProjectsInFunding[$iKey]['aRejectedBid'] = array_shift($oBids->select('id_project = ' . $aProject['id_project'] . ' AND id_lender_account = ' . $oLenderAccount->id_lender_account . ' AND status IN (' . implode(',', array(\bids::STATUS_BID_REJECTED,\bids::STATUS_AUTOBID_REJECTED)) . ')', 'id_bid DESC', null, '1'));
            $aProjectsInFunding[$iKey]['iNumberOfRejectedBids'] = $oBids->counter('id_project = ' . $aProject['id_project'] . ' AND id_lender_account = ' . $oLenderAccount->id_lender_account . ' AND status IN (' . implode(',', array(\bids::STATUS_BID_REJECTED, \bids::STATUS_AUTOBID_REJECTED)) . ')');
        }

        $this->aOngoingBidsByProject     = $aProjectsInFunding;
        $this->iDisplayTotalNumberOfBids = $oBids->counter('id_lender_account = ' . $oLenderAccount->id_lender_account);
    }
}
