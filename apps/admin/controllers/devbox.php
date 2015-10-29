<?php

// Controller de developpement, aucun acc�s client autoris�, fonctions en BETA
class devboxController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        if ($_SERVER['REMOTE_ADDR'] != "93.26.42.99") {
            die;
        }
    }

    public function _listes_repartitions_old()
    {
        // recup�ration des comptes bloques avant le 31/07/14
        $this->clients = $this->loadData('clients');

        // 1 preteurs offline
        $this->PreteursOffline = $this->clients->counter_de_test('status = 0 AND status_inscription_preteur = 1 AND status_pre_emp IN(1,3) AND LEFT(added,10) < "2014-07-31"');
        echo '<br>';
        echo '<br>';


        // 2 preteurs avec nom/prenom/email
        echo $sql = '
            SELECT
                c.*
                FROM clients c
                LEFT JOIN prospects p ON c.email = p.email
                LEFT JOIN lenders_accounts la ON c.id_client = la.id_client_owner
                WHERE c.nom != ""
                AND c.prenom != ""
                AND c.status = 0
                AND status_inscription_preteur = 0
                AND LEFT(c.added,10) < "2014-07-31"
                AND c.email != ""
                AND c.type IN (1,2,3,4)
                AND p.email IS NULL
                AND la.id_lender_account != ""';
        $this->Preteurs2 = $this->clients->get_preteurs_restriction($sql);
        $this->Preteurs2 = count($this->Preteurs2);
        echo '<br>';
        echo '<br>';

        // 3 preteurs avec nom/prenom/email/tel/adresse
        echo $sql = '
            SELECT
                c.id_client,
                c.email,
                ad.id_client,
                ad.ville,
                c.status_pre_emp,
                c.etape_inscription_preteur
                FROM clients c
                LEFT JOIN clients_adresses ad ON c.id_client = ad.id_client
                LEFT JOIN lenders_accounts la ON c.id_client = la.id_client_owner
                WHERE c.telephone != ""
                AND c.nom != ""
                AND c.prenom != ""
                AND c.status = 0
                AND LEFT(c.added,10) < "2014-07-31"
                AND c.email != ""
                AND c.type IN (1,2,3,4)
                AND ad.adresse1 != ""
                AND la.id_lender_account != ""
                AND c.status_inscription_preteur = 0';
        $this->Preteurs3 = $this->clients->get_preteurs_restriction($sql);
        $this->Preteurs3 = count($this->Preteurs3);
        echo '<br>';
        echo '<br>';

        // 4 preteurs avec nom/prenom/email/tel/adresse / info bancaire
        echo $sql = '
            SELECT
                c.id_client,
                c.email,
                ad.id_client,
                ad.ville,
                c.status_pre_emp,
                c.etape_inscription_preteur
                FROM clients c
                LEFT JOIN clients_adresses ad ON c.id_client = ad.id_client
                LEFT JOIN lenders_accounts la ON c.id_client = la.id_client_owner
                WHERE c.telephone != ""
                AND c.nom != ""
                AND c.prenom != ""
                AND c.status = 0
                AND LEFT(c.added,10) < "2014-07-31"
                AND c.email != ""
                AND c.type IN (1,2,3,4)
                AND ad.adresse1 != ""
                AND la.id_lender_account != ""
                AND c.status_inscription_preteur = 0
                AND la.iban != ""';
        $this->Preteurs4 = $this->clients->get_preteurs_restriction($sql);
        $this->Preteurs4 = count($this->Preteurs4);

        echo '<br>';
        echo '<br>';
        echo '1 preteurs offline : ' . $this->PreteursOffline;
        echo '<br>';
        echo '2 preteurs avec nom/prenom/email : ' . $this->Preteurs2;
        echo '<br>';
        echo '3 preteurs avec nom/prenom/email/tel/adresse : ' . $this->Preteurs3;
        echo '<br>';
        echo '4 preteurs avec nom/prenom/email/tel/adresse/ info bancaire : ' . $this->Preteurs4;
        echo '<br>';
        die;
    }

    public function _listes_repartitions()
    {
        // recup�ration des comptes bloques avant le 31/07/14
        $this->clients                = $this->loadData('clients');
        $this->prospects              = $this->loadData('prospects');
        $this->lenders_accounts       = $this->loadData('lenders_accounts');
        $this->clients_status_history = $this->loadData('clients_status_history');


        // 1 preteurs offline
        $this->countPreteursOffline = $this->clients->counter_de_test('status = 0 AND status_inscription_preteur = 1 AND status_pre_emp IN(1,3) AND LEFT(added,10) < "2014-07-31"');
        echo '<br>';
        echo '<br>';


        // 2 preteurs avec nom/prenom/email
        echo $sql = '
            SELECT
                c.*
                FROM clients c
                LEFT JOIN prospects p ON c.email = p.email
                LEFT JOIN lenders_accounts la ON c.id_client = la.id_client_owner
                WHERE c.nom != ""
                AND c.prenom != ""
                AND c.status = 0
                AND status_inscription_preteur = 0
                AND LEFT(c.added,10) < "2014-07-31"
                AND c.email != ""
                AND c.type IN (1,2,3,4)
                AND p.email IS NULL
                AND la.id_lender_account != ""';
        $this->Preteurs2      = $this->clients->get_preteurs_restriction($sql);
        $this->countPreteurs2 = count($this->Preteurs2);


        /*foreach($this->Preteurs2 as $p2){
            $this->prospects->nom = $p2['nom'];
            $this->prospects->prenom = $p2['prenom'];
            $this->prospects->email = $p2['email'];
            $this->prospects->id_langue = $p2['id_langue'];
            $this->prospects->source = $p2['source'];
            $this->prospects->source2 = $p2['source2'];
            $this->prospects->source3 = $p2['source3'];
            $this->prospects->slug_origine = $p2['slug_origine'];
            $this->prospects->create();
        }
        die;*/

        echo '<br>';
        echo '<br>';

        // 3 preteurs avec nom/prenom/email/tel/adresse
        echo $sql = '
            SELECT
                c.id_client,
                c.email,
                ad.id_client,
                ad.ville,
                c.status_pre_emp,
                c.etape_inscription_preteur
                FROM clients c
                LEFT JOIN clients_adresses ad ON c.id_client = ad.id_client
                LEFT JOIN lenders_accounts la ON c.id_client = la.id_client_owner
                WHERE c.telephone != ""
                AND c.nom != ""
                AND c.prenom != ""
                AND c.status = 0
                AND LEFT(c.added,10) < "2014-07-31"
                AND c.email != ""
                AND c.type IN (1,2,3,4)
                AND ad.adresse1 != ""
                AND la.id_lender_account != ""
                AND c.status_inscription_preteur = 0';
        $this->Preteurs3      = $this->clients->get_preteurs_restriction($sql);
        $this->countPreteurs3 = count($this->Preteurs3);
        echo '<br>';
        echo '<br>';

        // 4 preteurs avec nom/prenom/email/tel/adresse / info bancaire
        echo $sql = '
            SELECT
                c.id_client,
                c.email,
                ad.id_client,
                ad.ville,
                c.status_pre_emp,
                c.etape_inscription_preteur,
                c.status_inscription_preteur,
                c.id_nationalite,
                c.type,
                c.status
                FROM clients c
                LEFT JOIN clients_adresses ad ON c.id_client = ad.id_client
                LEFT JOIN lenders_accounts la ON c.id_client = la.id_client_owner
                WHERE c.telephone != ""
                AND c.nom != ""
                AND c.prenom != ""
                AND c.status = 0
                AND LEFT(c.added,10) < "2014-07-31"
                AND c.email != ""
                AND c.type IN (1,2,3,4)
                AND ad.adresse1 != ""
                AND la.id_lender_account != ""
                AND c.status_inscription_preteur = 0
                AND la.iban != ""';
        $this->Preteurs4      = $this->clients->get_preteurs_restriction($sql);
        $this->countPreteurs4 = count($this->Preteurs4);

        /*
        echo '<pre>';
        print_r($this->Preteurs3);
        echo '</pre>';
        die;*/


        // 2eme etape
        /*foreach($this->Preteurs4 as $p4)
        {
            $this->lenders_accounts->get($p4['id_client'],'id_client_owner');
            $this->clients->get($p4['id_client'],'id_client');

            // creation du statut "a contr�ler"
            $this->clients_status_history->addStatus('-2','10',$p4['id_client']);

            $this->clients->status_pre_emp = 1;
            $this->clients->status_inscription_preteur = 1;
            $this->clients->etape_inscription_preteur = 2;
            $this->clients->status = 1;
            $this->clients->update();

            $this->lenders_accounts->status = 1;
            $this->lenders_accounts->update();

        }
        die;*/
        // 3eme etape

        /*foreach($this->Preteurs3 as $p3)
        {
            $this->lenders_accounts->get($p3['id_client'],'id_client_owner');
            $this->clients->get($p3['id_client'],'id_client');

            $this->clients->status_pre_emp = 1;
            $this->clients->status_inscription_preteur = 1;
            $this->clients->etape_inscription_preteur = 1;
            $this->clients->status = 1;
            $this->clients->update();

            $this->lenders_accounts->status = 1;
            $this->lenders_accounts->update();

        }*/


        echo '<br>';
        echo '<br>';
        echo '1 preteurs offline : ' . $this->countPreteursOffline;
        echo '<br>';
        echo '2 preteurs avec nom/prenom/email : ' . $this->countPreteurs2;
        echo '<br>';
        echo '3 preteurs avec nom/prenom/email/tel/adresse : ' . $this->countPreteurs3;
        echo '<br>';
        echo '4 preteurs avec nom/prenom/email/tel/adresse/ info bancaire : ' . $this->countPreteurs4;
        echo '<br>';

        die;
    }

    public function _nettoyage()
    {
        // recup�ration des comptes bloques avant le 31/07/14
        $this->clients                = $this->loadData('clients');
        $this->prospects              = $this->loadData('prospects');
        $this->lenders_accounts       = $this->loadData('lenders_accounts');
        $this->clients_status_history = $this->loadData('clients_status_history');

        //AND LEFT(c.added,10) < "2014-07-31"
        echo $sql = "
        SELECT
                c.id_client,c.email,p.id_prospect,p.email as email_prospect
                FROM clients c
                LEFT JOIN prospects p ON c.email = p.email
                LEFT JOIN lenders_accounts la ON c.id_client = la.id_client_owner
                WHERE p.email IS NOT NULL
                AND la.id_lender_account != ''
                AND c.status_inscription_preteur = 1
                AND p.email != ''";
        $this->inscripts = $this->clients->get_preteurs_restriction($sql);

        die;
    }

    public function _test_requete()
    {
        $this->clients = $this->loadData('clients');

        $sql = "SELECT * FROM `mails_filer` WHERE LEFT(added,10) = '2015-04-15' AND id_textemail = 17";

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            echo $record['id_textemail'] . ' - ' . $record['added'] . '<br>';
        }

        die;
    }

    public function _doublons()
    {
        $this->clients = $this->loadData('clients');

        $sql = "
        SELECT c.id_client,c.email,c.added,c.updated,c.lastlogin,c.status
        FROM clients c
        WHERE (SELECT csh.id_client_status FROM clients_status_history csh WHERE csh.id_client = c.id_client ORDER BY csh.added LIMIT 1) IN(1,2) ";

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            echo '<pre>';
            print_r($record);
            echo '</pre>';
        }


        die;
    }

    // on veut les preteurs qui ont changé de pays dans le mois choisi en params
    public function _get_preteur_changement_pays()
    {
        $date_debut = "2015-05-01 00:00:00";
        $date_fin   = "2015-05-31 00:00:00";

        //recupération des lenders qui ont changé de pays dans la periode
        $this->lenders_imposition_history = $this->loadData('lenders_imposition_history');

        $sql      = '
        SELECT lih1.id_lender,
                    (
                        SELECT lih2.id_pays
                        FROM `lenders_imposition_history` lih2
                        WHERE lih2.`added` BETWEEN "2015-05-01 00:00:00" AND "2015-05-31 00:00:00"
                        AND lih2.id_lender = lih1.id_lender
                        ORDER BY lih2.id_lenders_imposition_history DESC
                        LIMIT 1

                    ) as id_pays

                FROM `lenders_imposition_history` lih1
                WHERE lih1.`added` BETWEEN "2015-05-01 00:00:00" AND "2015-05-31 00:00:00"
                GROUP BY lih1.id_lender';
        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }


        $L_lender_changed = $result;

        $tab_liste_lender_changement_periode = array();
        $compteur                            = 0;

        foreach ($L_lender_changed as $lender) {
            //récuperation de l'id_pays juste avant le debut de la periode
            $sql      = '
                        SELECT lih.id_pays
                        FROM `lenders_imposition_history` lih
                        WHERE lih.`added` < "2015-05-01 00:00:00"
                        AND lih.id_lender = ' . $lender['id_lender'] . '
                        ORDER BY lih.id_lenders_imposition_history DESC
                        LIMIT 1';
            $resultat = $this->bdd->query($sql);
            $result   = array();
            while ($record = $this->bdd->fetch_array($resultat)) {
                $result[] = $record;
            }

            $last_id_pays_before_periode = $result[0]['id_pays'];

            // on enregistre tous les lenders qui ont changé de pays dans la période
            if ($lender['id_pays'] != $last_id_pays_before_periode && $last_id_pays_before_periode != "") {
                $tab_liste_lender_changement_periode[$compteur]['id_lender']     = $lender['id_lender'];
                $tab_liste_lender_changement_periode[$compteur]['id_pays_avant'] = $last_id_pays_before_periode;
                $tab_liste_lender_changement_periode[$compteur]['id_pays_apres'] = $lender['id_pays'];
                $compteur++;
            }

        }

        print_r($tab_liste_lender_changement_periode);
        die;
        //pour chaque lender qui a changé, on va recup tous ces remboursements
//            foreach($tab_liste_lender_changement_periode as $index => $lender)
//            {
//
//                //recupération de l'id_client du lender
//                $this->lenders_account = $this->loadData('lenders_account');
//                $lender_acc = $this->lenders_account->select('id_lender_account = '.$lender['id_lender']);
//
//                $id_client = $lender_acc[0]['id_client_owner'];
//
//                print_r($id_client);
//                die;
//
//                // Récup des remb du lender sur le mois
//                $this->transac = $this->loadData('transactions');
//                // 6 : remb Emprunteur (prelevement)
//                $sql = '
//                    SELECT
//                            SUM(ROUND(montant/100,2)) AS montant,
//                            SUM(ROUND(montant_unilend/100,2)) AS montant_unilend,
//                            SUM(ROUND(montant_etat/100,2)) AS montant_etat,
//                            LEFT(date_transaction,10) as jour
//                    FROM transactions
//                    WHERE MONTH(added) = "5"
//                    AND YEAR(added) = "2015"
//                    AND etat = 1
//                    AND status = 1
//                    AND id_client = '.$id_client.'
//                    AND type_transaction IN(6) /*rbt*/
//                    GROUP BY LEFT(date_transaction,10)';
//
//                $resultat = $this->bdd->query($sql);
//                $result = array();
//                while($record = $this->bdd->fetch_array($resultat))
//                {
//                        $result[] = $record;
//                }
//
//                $montant = $result[0]['montant'];
//                $montant_unilend = $result[0]['montant_unilend'];
//                $montant_etat = $result[0]['montant_etat'];
//                $jour = $result[0]['jour'];
//
//                $rembEmprunteur = $this->transac->sumByday(6, 5, 2015);  // 6 type remb // 5 Mai //  2015 annee
//
//                $tab_liste_lender_changement_periode[$index]['montant']= $montant;
//                $tab_liste_lender_changement_periode[$index]['montant_unilend']= $montant_unilend;
//                $tab_liste_lender_changement_periode[$index]['montant_etat']= $montant_etat;
//                $tab_liste_lender_changement_periode[$index]['jour']= $jour;
//
//            }

        print_r($tab_liste_lender_changement_periode);
        die;
    }


    // on doit renvoyer les mails de contact recu depuis le 1juin15
    public function _renvoi_mail_contact_juin()
    {
        $this->mails_filer = $this->loadData('mails_filer');

        $L_mails = $this->mails_filer->select('added > "2015-06-06 00:00:00" AND subject = "=?UTF-8?B?VW5pbGVuZCA6IGRlbWFuZGUgZGUgY29udGFjdA==?="');

        print_r($L_mails);
        die;


    }


    /*
     *  Fonction qui permet de faire un rollback sur un remboursement fait
     */
    public function _retour_arriere_echeance()
    {
        // Variables
        $id_projet = 7727;
        $ordre     = 4;


        $this->echeanciers = $this->loadData('echeanciers');
        $lEcheances        = $this->echeanciers->select('id_project = ' . $id_projet . ' AND status_emprunteur = 1 AND ordre = ' . $ordre);

//            echo '<pre>';
//            print_r($lEcheances);
//            echo '</pre>';


        // passer toutes les echeances à status 0 et date reelle à 0000  ===> deja fait à la main

        $cpt = 1;
        foreach ($lEcheances as $e) {

            // on met l'écheance à 0
            $this->echeanciers = $this->loadData('echeanciers');
            $this->echeanciers->get($e['id_echeancier']);
            $this->echeanciers->status             = 0;
            $this->echeanciers->date_echeance_reel = "0000-00-00 00:00:00";
            $this->echeanciers->update();


            //recup des transactions faites liées
            $this->transactions = $this->loadData('transactions');
            if ($this->transactions->get($e['id_echeancier'], 'id_echeancier')) {

                // supp la walletline
                $this->wallets_lines = $this->loadData('wallets_lines');
                $this->wallets_lines->delete($this->transactions->id_transaction, 'id_transaction');

                // delete la notif
                $this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');
                $this->clients_gestion_mails_notif->delete($this->transactions->id_transaction, 'id_transaction');

                //DELETE A FAIRE A LA FIN
                $this->transactions->delete($e['id_echeancier'], 'id_echeancier');

                // supp les indexations des transactions
                $this->indexage_vos_operations = $this->loadData('indexage_vos_operations');
                $this->indexage_vos_operations->delete($e['id_echeancier'], 'id_echeancier');

                echo "<br /> Supp transaction id : " . $this->transactions->id_transaction;
                echo "<br /><br />";

                // on annule les notifs a la main
                //UPDATE `unilend`.`notifications` SET `status` = '1' WHERE `type` = 2 AND `id_project` = 3013 AND `status` = 0 AND added LIKE "2015-06-10 %"

                // DELETE FROM `unilend`.`notifications` WHERE `id_project` = 1614 AND `added` LIKE '%2015-07-08%'


                $cpt++;
            }
        }

        echo "<br /><br />";
        echo "<br /><br />";
        echo "Nb ligne supp :" . $cpt;


        // on supprime aussi la transaction et le bank unilend vers unilend (remb total)


        // TRANSACTION  qui déduit à unilend la redistribution
        // On cherche la transaction avec l'id_echeancier_emprunteur

        // Bank_unilend
        //idem sur l'id_echeancier_emprunteur


    }


    public function _RA_email()
    {
        $this->projects                      = $this->loadData('projects');
        $this->echeanciers                   = $this->loadData('echeanciers');
        $this->receptions                    = $this->loadData('receptions');
        $this->echeanciers_emprunteur        = $this->loadData('echeanciers_emprunteur');
        $this->transactions                  = $this->loadData('transactions');
        $this->lenders_accounts              = $this->loadData('lenders_accounts');
        $this->clients                       = $this->loadData('clients');
        $this->wallets_lines                 = $this->loadData('wallets_lines');
        $this->notifications                 = $this->loadData('notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $this->projects_status_history       = $this->loadData('projects_status_history');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->mails_text                    = $this->loadData('mails_text');
        $this->companies                     = $this->loadData('companies');
        $this->loans                         = $this->loadData('loans');
        $loans                               = $this->loadData('loans');

        //die; // <---------------------------
        $id_reception = 7764; // <-------------------------


        $this->receptions->get($id_reception);
        $this->projects->get($this->receptions->id_project);
        $this->companies->get($this->projects->id_company, 'id_company');

        // REMB ECHEANCE PRETEURS ----------------------------------------------------------------------


        // FB
        $this->settings->get('Facebook', 'type');
        $lien_fb = $this->settings->value;

        // Twitter
        $this->settings->get('Twitter', 'type');
        $lien_tw = $this->settings->value;


        // on recupere les preteurs de ce projet (par loans)
        $L_preteur_on_projet = $this->echeanciers->get_liste_preteur_on_project($this->projects->id_project);


        $reste_a_payer_pour_preteur = 0;
        $montant_total              = 0;


        // on veut recup le nb d'echeances restantes
        $sum_ech_restant = $this->echeanciers_emprunteur->counter('id_project = ' . $this->projects->id_project . ' AND status_ra = 1');

        // par loan
        foreach ($L_preteur_on_projet as $preteur) {
            // pour chaque preteur on calcule le total qui restait à lui payer (sum capital par loan)
            //$reste_a_payer_pour_preteur= $this->echeanciers->getSumRestanteARembByProject_capital($preteur['id_lender'],'id_loan = '.$preteur['id_loan'].' AND '.$this->projects->id_project);

            $reste_a_payer_pour_preteur = $this->echeanciers->getSumRestanteARembByProject_capital(' AND id_lender =' . $preteur['id_lender'] . ' AND id_loan = ' . $preteur['id_loan'] . ' AND status_ra = 1 AND id_project = ' . $this->projects->id_project);

            // on rembourse le preteur

            // On recup lenders_accounts
            $this->lenders_accounts->get($preteur['id_lender'], 'id_lender_account');
            // On recup le client
            $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');


//            // On enregistre la transaction
//            $this->transactions->id_client = $this->lenders_accounts->id_client_owner;
//            $this->transactions->montant = ($reste_a_payer_pour_preteur * 100);
//            $this->transactions->id_echeancier = 0; // pas d'id_echeance car multiple
//            $this->transactions->id_loan_remb = $preteur['id_loan']; // <-------------- on met ici pour retrouver la jointure
//            $this->transactions->id_project = $this->projects->id_project;
//            $this->transactions->id_langue = 'fr';
//            $this->transactions->date_transaction = date('Y-m-d H:i:s');
//            $this->transactions->status = '1';
//            $this->transactions->etat = '1';
//            $this->transactions->ip_client = $_SERVER['REMOTE_ADDR'];
//            $this->transactions->type_transaction = 23; // remb anticipe preteur
//            $this->transactions->transaction = 2; // transaction virtuelle
//            $this->transactions->id_transaction = $this->transactions->create();
//
//            // on enregistre la transaction dans son wallet
//            $this->wallets_lines->id_lender = $preteur['id_lender'];
//            $this->wallets_lines->type_financial_operation = 40;
//            $this->wallets_lines->id_loan = $preteur['id_loan']; // <-------------- on met ici pour retrouver la jointure
//            $this->wallets_lines->id_transaction = $this->transactions->id_transaction;
//            $this->wallets_lines->status = 1; // non utilisé
//            $this->wallets_lines->type = 2; // transaction virtuelle
//            $this->wallets_lines->amount = ($reste_a_payer_pour_preteur * 100);
//            $this->wallets_lines->id_wallet_line = $this->wallets_lines->create();


            /////////////////// EMAIL PRETEURS REMBOURSEMENTS //////////////////
            //*******************************************//
            //*** ENVOI DU MAIL REMBOURSEMENT PRETEUR ***//
            //*******************************************//
            // Recuperation du modele de mail
            $this->mails_text->get('preteur-remboursement-anticipe', 'lang = "' . $this->language . '" AND type');

            $nbpret = $loans->counter('id_lender = ' . $preteur['id_lender'] . ' AND id_project = ' . $this->projects->id_project);

            // Récupération de la sommes des intérets deja versé au lender
            $sum_interet = $this->echeanciers->sum('interets', 'id_project = ' . $this->projects->id_project . ' AND id_loan = ' . $preteur['id_loan'] . ' AND status_ra = 0 AND status = 1 AND id_lender =' . $preteur['id_lender']);


            // Remb net email
            if ($reste_a_payer_pour_preteur >= 2) {
                $euros = ' euros';
            } else {
                $euros = ' euro';
            }

            $rembNetEmail = number_format($reste_a_payer_pour_preteur, 2, ',', ' ') . $euros;

            // Solde preteur
            $getsolde = $this->transactions->getSolde($this->clients->id_client);
            if ($getsolde > 1) {
                $euros = ' euros';
            } else {
                $euros = ' euro';
            }
            $solde = number_format($getsolde, 2, ',', ' ') . $euros;

            // FB
            $this->settings->get('Facebook', 'type');
            $lien_fb = $this->settings->value;


            // Twitter
            $this->settings->get('Twitter', 'type');
            $lien_tw = $this->settings->value;

            $loans->get($preteur['id_loan'], 'id_loan');

            $this->transactions->get($preteur['id_loan'], 'id_loan_remb');


            // Variables du mailing
            $varMail = array(
                'surl'                 => $this->surl,
                'url'                  => $this->furl,
                'prenom_p'             => $this->clients->prenom,
                'nomproject'           => $this->projects->title,
                'nom_entreprise'       => $this->companies->name,
                'taux_bid'             => number_format($loans->rate, 2, ',', ' '),
                'nbecheancesrestantes' => $sum_ech_restant,
                'interetsdejaverses'   => number_format($sum_interet, 2, ',', ' '),
                'crdpreteur'           => $rembNetEmail,
                'Datera'               => date('d/m/Y'),
                'solde_p'              => $solde,
                'motif_virement'       => $motif,
                'lien_fb'              => $lien_fb,
                'lien_tw'              => $lien_tw
            );

            // Construction du tableau avec les balises EMV
            $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

            // Attribution des données aux variables
            $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
            $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
            $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

            // Envoi du mail
            $this->email = $this->loadLib('email', array());
            $this->email->setFrom($this->mails_text->exp_email, $exp_name);
            $this->email->setSubject(stripslashes($sujetMail));
            $this->email->setHTMLBody(stripslashes($texteMail));

            $notifications                  = $this->loadData('notifications');
            $notifications->type            = 2; // remb
            $notifications->id_lender       = $preteur['id_lender'];
            $notifications->id_project      = $this->projects->id_project;
            $notifications->amount          = ($reste_a_payer_pour_preteur * 100);
            $notifications->id_notification = $notifications->create();

            //////// GESTION ALERTES //////////
            $this->clients_gestion_mails_notif = $this->loadData('clients_gestion_mails_notif');

            $this->clients_gestion_mails_notif->id_client                      = $this->clients->id_client;
            $this->clients_gestion_mails_notif->id_notif                       = 5; // remb preteur
            $this->clients_gestion_mails_notif->date_notif                     = date('Y-m-d H:i:s');
            $this->clients_gestion_mails_notif->id_notification                = $notifications->id_notification;
            $this->clients_gestion_mails_notif->id_transaction                 = $this->transactions->id_transaction;
            $this->clients_gestion_mails_notif->id_clients_gestion_mails_notif = $this->clients_gestion_mails_notif->create();

            //////// FIN GESTION ALERTES //////////

            $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');

            // envoi email remb ok maintenant ou non
            if ($this->clients_gestion_notifications->getNotif($this->clients->id_client, 5, 'immediatement') == true) {
                //////// GESTION ALERTES //////////
                $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                $this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
                $this->clients_gestion_mails_notif->update();
                //////// FIN GESTION ALERTES //////////

                // Pas de mail si le compte est desactivé
                /*if ($this->clients->status == 1)
                {
                    if ($this->Config['env'] == 'prod') // nmp
                    {
                        Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                        // Injection du mail NMP dans la queue
                        $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                    }
                    else // non nmp
                    {
                        $this->email->addRecipient(trim($this->clients->email));
                        $this->email->addBCCRecipient('k1@david.equinoa.net');
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                    }
                }*/
            }//End si notif ok

        }

        die;
    }

    // on veut les preteurs qui ont changé de pays dans le mois choisi en params
    public function _get_selection_op()
    {
        $sql      = '

                SET SQL_BIG_SELECTS=1;

        ( SELECT t.*,

            CASE
                            WHEN t.type_transaction = 1 THEN "Dépôt de fonds" WHEN t.type_transaction = 2 AND t.montant <= 0 AND (SELECT lo.id_loan FROM loans lo WHERE lo.id_bid = b.id_bid AND lo.status = 0) IS NULL THEN "Offre proposée" WHEN t.type_transaction = 2 AND t.montant > 0 THEN "Offre rejetée" WHEN t.type_transaction = 2 AND t.montant <= 0 AND (SELECT lo.id_loan FROM loans lo WHERE lo.id_bid = b.id_bid AND lo.status = 0) IS NOT NULL THEN "Offre proposée"
                            WHEN t.type_transaction = 3 THEN "Dépôt de fonds"
                            WHEN t.type_transaction = 4 THEN "Dépôt de fonds"
                            WHEN t.type_transaction = 5 THEN "Remboursement"
                            WHEN t.type_transaction = 7 THEN "Dépôt de fonds"
                            WHEN t.type_transaction = 8 THEN "Retrait d argent"
                            WHEN t.type_transaction = 16 THEN "Offre de bienvenue"
                            WHEN t.type_transaction = 17 THEN "Retrait offre"
                            WHEN t.type_transaction = 19 THEN "Gain filleul"
                            WHEN t.type_transaction = 20 THEN "Gain parrain"
                            WHEN t.type_transaction = 22 THEN "Remboursement anticipé"
                            WHEN t.type_transaction = 23 THEN "Remboursement anticipé"
                ELSE ""
            END as type_transaction_alpha,

            CASE
                WHEN t.type_transaction = 5 THEN (SELECT ech.id_project FROM echeanciers ech WHERE ech.id_echeancier = t.id_echeancier)
                WHEN b.id_project IS NULL THEN b2.id_project
                ELSE b.id_project
            END as le_id_project,


            date_transaction as date_tri,

            (SELECT ROUND(SUM(t2.montant/100),2) as solde FROM transactions t2 WHERE t2.etat = 1 AND t2.status = 1 AND t2.id_client = t.id_client AND t2.type_transaction NOT IN (9,6,15) AND t2.id_transaction <= t.id_transaction ) as solde,

            CASE t.type_transaction
                WHEN 2 THEN (SELECT p.title FROM projects p WHERE p.id_project = le_id_project)
                WHEN 5 THEN (SELECT p2.title FROM projects p2 LEFT JOIN echeanciers e ON p2.id_project = e.id_project WHERE e.id_echeancier = t.id_echeancier)
                                WHEN 23 THEN (SELECT p2.title FROM projects p2 WHERE p2.id_project = t.id_project)
                ELSE ""
            END as title,

            CASE t.type_transaction
                WHEN 2 THEN (SELECT loa.id_loan FROM loans loa WHERE loa.id_bid = b.id_bid AND loa.status = 0)
                WHEN 5 THEN (SELECT e.id_loan FROM echeanciers e WHERE e.id_echeancier = t.id_echeancier)
                                WHEN 23 THEN (SELECT e.id_loan FROM echeanciers e WHERE e.id_project = t.id_project AND w.id_lender = e.id_lender LIMIT 1)
                ELSE ""
            END as bdc



            FROM transactions t
            LEFT JOIN wallets_lines w ON t.id_transaction = w.id_transaction
            LEFT JOIN bids b ON w.id_wallet_line = b.id_lender_wallet_line
            LEFT JOIN bids b2 ON t.id_bid_remb = b2.id_bid
            WHERE 1=1
                        AND t.type_transaction IN (1,2,3,4,5,7,8,16,17,19,20,23)
                        AND t.status = 1
                        AND t.etat = 1
                        AND t.display = 0
                        AND t.id_client = 1
                        AND LEFT(t.date_transaction,10) >= "2015-06-22" ORDER BY id_transaction DESC
        )

        UNION ALL

        (
            SELECT t.*,  "Offre acceptée" as type_transaction_alpha,
                CASE
                    WHEN t.type_transaction = 5 THEN (SELECT ech.id_project FROM echeanciers ech WHERE ech.id_echeancier = t.id_echeancier)
                    WHEN b.id_project IS NULL THEN b2.id_project
                    ELSE b.id_project
                END as le_id_project,

                (SELECT psh.added FROM projects_status_history psh WHERE psh.id_project = le_id_project AND id_project_status = 8 ORDER BY added ASC LIMIT 1) as date_tri,

                (SELECT ROUND(SUM(t2.montant/100),2) as solde FROM transactions t2 WHERE t2.etat = 1 AND t2.status = 1 AND t2.id_client = t.id_client AND t2.type_transaction NOT IN (9,6,15) AND t2.date_transaction < date_tri ) as solde,

                CASE t.type_transaction
                    WHEN 2 THEN (SELECT p.title FROM projects p WHERE p.id_project = le_id_project)
                    WHEN 5 THEN (SELECT p2.title FROM projects p2 LEFT JOIN echeanciers e ON p2.id_project = e.id_project WHERE e.id_echeancier = t.id_echeancier)
                                        WHEN 23 THEN (SELECT p2.title FROM projects p2 WHERE p2.id_project = t.id_project)
                    ELSE ""
                END as title,

                lo.id_loan as bdc

            FROM loans lo
            LEFT JOIN bids b ON lo.id_bid = b.id_bid
            LEFT JOIN wallets_lines w ON w.id_wallet_line = b.id_lender_wallet_line
            LEFT JOIN transactions t ON t.id_transaction = w.id_transaction
            LEFT JOIN bids b2 ON t.id_bid_remb = b2.id_bid
            WHERE 1=1
            AND lo.status = 0
             AND t.type_transaction IN (1,2,3,4,5,7,8,16,17,19,20,23)
                        AND t.status = 1
                        AND t.etat = 1
                        AND t.display = 0
                        AND t.id_client = 1
                        AND LEFT(t.date_transaction,10) >= "2015-06-22"


             ORDER BY id_transaction DESC

        )
         ORDER BY id_transaction DESC
        ';
        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }


        print_r($result);
        die;
    }
}
