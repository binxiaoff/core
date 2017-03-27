<?php

use \Unilend\Bundle\TranslationBundle\Service\TranslationManager;


class ajaxController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $_SESSION['request_url'] = $this->url;

        $this->users->checkAccess();

        $this->hideDecoration();
    }

    /* Fonction AJAX delete image ELEMENT */
    public function _deleteImageElement()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->tree_elements->value);

            // On supprime le fichier de la base
            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier ELEMENT */
    public function _deleteFichierElement()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'fichiers/' . $this->tree_elements->value);

            // On supprime le fichier de la base
            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier protected ELEMENT */
    public function _deleteFichierProtectedElement()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->path . 'protected/templates/' . $this->tree_elements->value);

            // On supprime le fichier de la base
            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete image ELEMENT BLOC */
    public function _deleteImageElementBloc()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier ELEMENT BLOC */
    public function _deleteFichierElementBloc()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'fichiers/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete fichier protected ELEMENT BLOC */
    public function _deleteFichierProtectedElementBloc()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->path . 'protected/templates/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    /* Fonction AJAX delete image TREE */
    public function _deleteImageTree()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree->get(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->tree->img_menu);

            // On supprime le fichier de la base
            $this->tree->img_menu = '';
            $this->tree->update(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));
        }
    }

    /* Fonction AJAX delete video TREE */
    public function _deleteVideoTree()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->tree->get(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'videos/' . $this->tree->video);

            // On supprime le fichier de la base
            $this->tree->video = '';
            $this->tree->update(array('id_tree' => $this->params[0], 'id_langue' => $this->params[1]));
        }
    }

    /* Fonction AJAX chargement des noms de la section de traduction */
    public function _loadNomTexte()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->lNoms = $translationManager->selectNamesForSection($this->params[0]);
        }
    }

    /* Fonction AJAX chargement des traductions de la section de traduction */
    public function _loadTradTexte()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->lTranslations = $translationManager->noCacheTrans($this->params[1], $this->params[0]);
        }
    }

    /* Activer un utilisateur sur une zone */
    public function _activeUserZone()
    {
        $this->autoFireView = false;

        if (isset($this->params[0]) && $this->params[0] != '') {
            // Recuperation du statut actuel de l'user
            $this->users_zones->get($this->params[0], 'id_zone = ' . $this->params[1] . ' AND id_user');

            if ($this->users_zones->id != '') {
                $this->users_zones->delete($this->users_zones->id);
                echo $this->surl . '/images/admin/check_off.png';
            } else {
                $this->users_zones->id_user = $this->params[0];
                $this->users_zones->id_zone = $this->params[1];
                $this->users_zones->create();
                echo $this->surl . '/images/admin/check_on.png';
            }
        }
    }

    public function _addMemo()
    {
        $this->autoFireView = true;

        if (isset($_POST['content_memo']) && isset($_POST['id']) && isset($_POST['type'])) {
            $this->projects_comments = $this->loadData('projects_comments');

            if ($_POST['type'] == 'edit') {
                $this->projects_comments->get($_POST['id'], 'id_project_comment');
                $this->projects_comments->content = $_POST['content_memo'];
                $this->projects_comments->update();
                $this->lProjects_comments = $this->projects_comments->select('id_project = ' . $this->projects_comments->id_project, 'added ASC');
            } else {
                $this->projects_comments->id_project = $_POST['id'];
                $this->projects_comments->content    = $_POST['content_memo'];
                $this->projects_comments->status     = 1;
                $this->projects_comments->create();
                $this->lProjects_comments = $this->projects_comments->select('id_project = ' . $_POST['id'], 'added ASC');
            }
        }
    }

    public function _deleteMemo()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_project_comment']) && isset($_POST['id_project'])) {
            $this->projects_comments = $this->loadData('projects_comments');
            $this->projects_comments->delete($_POST['id_project_comment'], 'id_project_comment');

            $this->lProjects_comments = $this->projects_comments->select('id_project = ' . $_POST['id_project'], 'added ASC');
        }
    }

    public function _valid_etapes()
    {
        $this->autoFireView = false;
        /** @var \Symfony\Component\Translation\Translator translator */
        $this->translator = $this->get('translator');

        if (isset($_POST['id_project']) && isset($_POST['etape'])) {
            $this->projects         = $this->loadData('projects');
            $this->companies        = $this->loadData('companies');
            $this->clients          = $this->loadData('clients');
            $this->clients_adresses = $this->loadData('clients_adresses');

            $serialize = serialize($_POST);
            $this->users_history->histo(8, 'dossier edit etapes', $_SESSION['user']['id_user'], $serialize);

            if ($_POST['etape'] == 1) {
                $this->projects->get($_POST['id_project'], 'id_project');
                $this->projects->amount     = $this->ficelle->cleanFormatedNumber($_POST['montant_etape1']);
                $this->projects->period     = (0 < (int) $_POST['duree_etape1']) ? (int) $_POST['duree_etape1'] : $this->projects->period;

                if ($_POST['partner_etape1'] != $this->projects->id_partner) {
                    $this->projects->commission_rate_funds     = null;
                    $this->projects->commission_rate_repayment = null;
                }
                $this->projects->id_partner = $_POST['partner_etape1'];
                $this->projects->update();

                $this->companies->get($this->projects->id_company, 'id_company');
                $this->companies->siren = $_POST['siren_etape1'];
                $this->companies->update();

                $this->clients->get($this->companies->id_client_owner);
                $this->clients->source = $_POST['source_etape1'];
                $this->clients->update();
            } elseif ($_POST['etape'] == 2) {
                $this->projects->get($_POST['id_project'], 'id_project');
                $this->projects->id_prescripteur = ('true' === $_POST['has_prescripteur']) ? $_POST['id_prescripteur'] : 0;
                $this->projects->balance_count   = empty($this->projects->balance_count) ? \DateTime::createFromFormat('d/m/Y', $_POST['creation_date_etape2'])->diff(new \DateTime())->y : $this->projects->balance_count;
                $this->projects->update();

                $this->companies->get($this->projects->id_company, 'id_company');
                $this->companies->name                          = $_POST['raison_sociale_etape2'];
                $this->companies->forme                         = $_POST['forme_juridique_etape2'];
                $this->companies->capital                       = $this->ficelle->cleanFormatedNumber($_POST['capital_social_etape2']);
                $this->companies->date_creation                 = \DateTime::createFromFormat('d/m/Y', $_POST['creation_date_etape2'])->format('Y-m-d');
                $this->companies->adresse1                      = $_POST['address_etape2'];
                $this->companies->city                          = $_POST['ville_etape2'];
                $this->companies->zip                           = $_POST['postal_etape2'];
                $this->companies->phone                         = $_POST['phone_etape2'];
                $this->companies->status_adresse_correspondance = isset($_POST['same_address_etape2']) && 'on' === $_POST['same_address_etape2'] ? 1 : 0;
                $this->companies->status_client                 = $_POST['enterprise_etape2'];
                $this->companies->update();

                $this->clients_adresses->get($this->companies->id_client_owner, 'id_client');
                if ($this->companies->status_adresse_correspondance == 0) {
                    $this->clients_adresses->adresse1  = $_POST['adresse_correspondance_etape2'];
                    $this->clients_adresses->ville     = $_POST['city_correspondance_etape2'];
                    $this->clients_adresses->cp        = $_POST['zip_correspondance_etape2'];
                    $this->clients_adresses->telephone = $_POST['phone_correspondance_etape2'];
                } else {
                    $this->clients_adresses->adresse1  = $_POST['address_etape2'];
                    $this->clients_adresses->ville     = $_POST['ville_etape2'];
                    $this->clients_adresses->cp        = $_POST['postal_etape2'];
                    $this->clients_adresses->telephone = $_POST['phone_etape2'];
                }
                $this->clients_adresses->update();

                $this->clients->get($this->companies->id_client_owner, 'id_client');
                $this->clients->email     = $_POST['email_etape2'];
                $this->clients->civilite  = $_POST['civilite_etape2'];
                $this->clients->nom       = $this->ficelle->majNom($_POST['nom_etape2']);
                $this->clients->prenom    = $this->ficelle->majNom($_POST['prenom_etape2']);
                $this->clients->fonction  = $_POST['fonction_etape2'];
                $this->clients->telephone = $_POST['phone_new_etape2'];
                $this->clients->naissance = empty($_POST['date_naissance_gerant']) ? '0000-00-00' : date('Y-m-d', strtotime(str_replace('/', '-', $_POST['date_naissance_gerant'])));
                $this->clients->update();
            } elseif ($_POST['etape'] == 3) {
                /** @var projects $oProject */
                $oProject = $this->loadData('projects');
                $oProject->get($_POST['id_project'], 'id_project');
                $oProject->amount               = $this->ficelle->cleanFormatedNumber($_POST['montant_etape3']);
                $oProject->period               = $_POST['duree_etape3'];
                $oProject->title                = $_POST['titre_etape3'];
                $oProject->objectif_loan        = $_POST['objectif_etape3'];
                $oProject->presentation_company = $_POST['presentation_etape3'];
                $oProject->means_repayment      = $_POST['moyen_etape3'];
                $oProject->comments             = $_POST['comments_etape3'];
                $oProject->update();
            } elseif ($_POST['etape'] == 4.1) {
                /** @var projects $oProject */
                $oProject = $this->loadData('projects');

                if ($oProject->get($_POST['id_project'], 'id_project')) {
                    /** @var company_rating $oCompanyRating */
                    $oCompanyRating = $this->loadData('company_rating');
                    $aRatings       = $oCompanyRating->getHistoryRatingsByType($oProject->id_company_rating_history);
                    $bAddHistory    = false;

                    foreach ($_POST['ratings'] as $sRating => $mValue) {
                        switch ($sRating) {
                            case 'date_dernier_privilege':
                            case 'date_tresorerie':
                                if (false === empty($mValue)) {
                                    $mValue = date('Y-m-d', strtotime(str_replace('/', '-', $mValue)));
                                }
                                break;
                            case 'montant_tresorerie':
                                $mValue = $this->ficelle->cleanFormatedNumber($mValue);
                                break;
                        }

                        if (false === isset($aRatings[$sRating]) || $aRatings[$sRating] != $mValue) {
                            $bAddHistory = true;
                            $aRatings[$sRating] = $mValue;
                        }
                    }

                    if ($bAddHistory) {
                        /** @var company_rating_history $oCompanyRatingHistory */
                        $oCompanyRatingHistory = $this->loadData('company_rating_history');
                        $oCompanyRatingHistory->id_company = $oProject->id_company;
                        $oCompanyRatingHistory->id_user    = $_SESSION['user']['id_user'];
                        $oCompanyRatingHistory->action     = \company_rating_history::ACTION_USER;
                        $oCompanyRatingHistory->create();

                        $oProject->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;

                        foreach ($aRatings as $sRating => $mValue) {
                            $oCompanyRating->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
                            $oCompanyRating->type                      = $sRating;
                            $oCompanyRating->value                     = $mValue;
                            $oCompanyRating->create();
                        }
                    }

                    $oProject->ca_declara_client                    = $this->ficelle->cleanFormatedNumber($_POST['ca_declara_client']);
                    $oProject->resultat_exploitation_declara_client = $this->ficelle->cleanFormatedNumber($_POST['resultat_exploitation_declara_client']);
                    $oProject->fonds_propres_declara_client         = $this->ficelle->cleanFormatedNumber($_POST['fonds_propres_declara_client']);
                    $oProject->update();
                }
            } elseif ($_POST['etape'] == 4.2) {
                /** @var projects $oProject */
                $oProject = $this->loadData('projects');
                /** @var companies_bilans $oCompanyAnnualAccounts */
                $oCompanyAnnualAccounts = $this->loadData('companies_bilans');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager $companyBalanceSheetManager */
                $companyBalanceSheetManager = $this->get('unilend.service.company_balance_sheet_manager');

                if (isset($_POST['box'], $_POST['id_project']) && $oProject->get($_POST['id_project'], 'id_project')) {
                    foreach ($_POST['box'] as $balanceSheetId => $boxes){
                        $oCompanyAnnualAccounts->get($balanceSheetId);
                        foreach($boxes as $box => $value) {
                            $value = $this->ficelle->cleanFormatedNumber($value);
                            $companyBalanceSheetManager->saveBalanceSheetDetails($oCompanyAnnualAccounts, $box, $value);
                        }
                        $companyBalanceSheetManager->calculateDebtsAssetsFromBalance($oCompanyAnnualAccounts->id_bilan);
                    }
                }
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $oProject->id_project);
                die;
            }
        }
    }

    public function _create_client()
    {
        $this->autoFireView = false;

        $this->projects         = $this->loadData('projects');
        $this->companies        = $this->loadData('companies');
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');

        if (isset($_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            // On verifie que ce soit bien un mail
            if ($_POST['email'] != '') {
                // si client existe deja
                if ($this->clients->get($_POST['id_client'], 'id_client')) {
                    if ($this->clients->counter('email = "' . $_POST['email'] . '" AND id_client <> ' . $this->clients->id_client) > 0) {
                        $this->clients->email = $_POST['email'] . '-' . $_POST['id_project'];
                    } else {
                        $this->clients->email = $_POST['email'];

                        $this->companies->get($this->projects->id_company, 'id_company');
                        $this->companies->email_facture = $this->clients->email;

                        $this->companies->update();
                        $this->clients->update();
                    }

                } // Si il existe pas on le créer
                else {
                    // On créer le client
                    $this->clients->id_langue = 'fr';

                    // Si le mail existe deja on enregistre pas le mail
                    if ($this->clients->counter('email = "' . $_POST['email'] . '"') > 0) {
                        $this->clients->email = $_POST['email'] . '-' . $_POST['id_project'];
                    } else {
                        $this->clients->email = $_POST['email'];
                    }
                    $this->clients->id_client = $this->clients->create();

                    $this->clients_adresses->id_client = $this->clients->id_client;
                    $this->clients_adresses->create();

                    // On recup l'entreprise et on attribut le client a celle ci
                    $this->companies->get($this->projects->id_company, 'id_company');
                    $this->companies->id_client_owner = $this->clients->id_client;
                    $this->companies->email_facture   = $this->clients->email;
                    $this->companies->update();
                }

                echo json_encode([
                    'id_client' => $this->clients->id_client,
                    'error'     => 'ok'
                ]);
            } else {
                echo json_encode(['id_client' => '0', 'error' => 'nok']);
            }
        }
    }

    public function _valid_create()
    {
        $this->autoFireView = false;

        $this->projects  = $this->loadData('projects');
        $this->companies = $this->loadData('companies');
        $this->clients   = $this->loadData('clients');
        $oSettings       = $this->loadData('settings');

        if (isset($_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');

            $this->mail_template->get('confirmation-depot-de-dossier', 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

            $oSettings->get('Facebook', 'type');
            $lien_fb = $oSettings->value;

            $oSettings->get('Twitter', 'type');
            $lien_tw = $oSettings->value;

            $varMail = array(
                'prenom'               => $this->clients->prenom,
                'raison_sociale'       => $this->companies->name,
                'lien_reprise_dossier' => $this->surl . '/depot_de_dossier/reprise/' . $this->projects->hash,
                'lien_fb'              => $lien_fb,
                'lien_tw'              => $lien_tw,
                'surl'                 => $this->surl,
                'url'                  => $this->url,
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('confirmation-depot-de-dossier', $varMail);
            $message->setTo($this->clients->email);
            $mailer = $this->get('mailer');
            $mailer->send($message);

            $this->clients->password = password_hash($this->ficelle->generatePassword(8), PASSWORD_DEFAULT);
            $this->clients->status   = 1;
            $this->clients->update();
        }
    }

    public function _generer_mdp()
    {
        $this->autoFireView = false;
        $this->clients      = $this->loadData('clients');
        /** @var \settings $oSettings */
        $oSettings          = $this->loadData('settings');

        if (isset($_POST['id_client']) && $this->clients->get($_POST['id_client'], 'id_client')) {
            $pass = $this->ficelle->generatePassword(8);
            $this->clients->changePassword($this->clients->email, $pass);

            $oSettings->get('Facebook', 'type');
            $lien_fb = $oSettings->value;

            $oSettings->get('Twitter', 'type');
            $lien_tw = $oSettings->value;

            $varMail = array(
                'surl'     => $this->surl,
                'url'      => $this->furl,
                'login'    => $this->clients->email,
                'prenom_p' => $this->clients->prenom,
                'mdp'      => 'Mot de passe : ' . $pass,
                'lien_fb'  => $lien_fb,
                'lien_tw'  => $lien_tw
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('generation-mot-de-passe', $varMail);
            $message->setTo($this->clients->email);
            $mailer = $this->get('mailer');
            $mailer->send($message);
        }
    }

    // supprime le bid dans la gestion du preteur et raffiche sa liste de bid mis a jour
    public function _deleteBidPreteur()
    {
        $this->autoFireView = true;

        /** @var \lenders_accounts $lender */
        $lender = $this->loadData('lenders_accounts');
        /** @var \bids $bids */
        $bids = $this->loadData('bids');
        /** @var \transactions $transactions */
        $transactions = $this->loadData('transactions');
        /** @var \wallets_lines $wallets_lines */
        $wallets_lines = $this->loadData('wallets_lines');
        /** @var \projects projects */
        $this->projects = $this->loadData('projects');

        if (isset($_POST['id_lender'], $_POST['id_bid']) && $bids->get($_POST['id_bid'], 'id_bid') && $lender->get($_POST['id_lender'], 'id_lender_account')) {
            $serialize = serialize($_POST);
            $this->users_history->histo(4, 'Bid en cours delete', $_SESSION['user']['id_user'], $serialize);

            $wallets_lines->get($bids->id_lender_wallet_line, 'id_wallet_line');

            $transactions->delete($wallets_lines->id_transaction, 'id_transaction');
            $wallets_lines->delete($wallets_lines->id_wallet_line, 'id_wallet_line');
            $bids->delete($bids->id_bid, 'id_bid');

            $this->lBids = $bids->select('id_lender_account = ' . $_POST['id_lender'] . ' AND status = 0', 'added DESC');
        }
    }

    public function _loadMouvTransac()
    {
        $this->transactions = $this->loadData('transactions');
        $this->clients      = $this->loadData('clients');
        $this->echeanciers  = $this->loadData('echeanciers');
        $this->projects     = $this->loadData('projects');
        $this->companies    = $this->loadData('companies');

        if (isset($_POST['year'], $_POST['id_client']) && $this->clients->get($_POST['id_client'], 'id_client')) {
            /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
            $translator = $this->get('translator');

            $this->lesStatuts = array(
                \transactions_types::TYPE_LENDER_SUBSCRIPTION            => $translator->trans('preteur-profile_versement-initial'),
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT      => $translator->trans('preteur-profile_alimentation-cb'),
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT    => $translator->trans('preteur-profile_alimentation-virement'),
                \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL       => 'Remboursement de capital',
                \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS     => 'Remboursement d\'intérêts',
                \transactions_types::TYPE_DIRECT_DEBIT                   => $translator->trans('preteur-profile_alimentation-prelevement'),
                \transactions_types::TYPE_LENDER_WITHDRAWAL              => $translator->trans('preteur-profile_retrait'),
                \transactions_types::TYPE_LENDER_REGULATION              => 'Régularisation prêteur',
                \transactions_types::TYPE_WELCOME_OFFER                  => 'Offre de bienvenue',
                \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION     => 'Retrait offre de bienvenue',
                \transactions_types::TYPE_SPONSORSHIP_SPONSORED_REWARD   => $translator->trans('preteur-operations-vos-operations_gain-filleul'),
                \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD     => $translator->trans('preteur-operations-vos-operations_gain-parrain'),
                \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT => $translator->trans('preteur-operations-vos-operations_remboursement-anticipe'),
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT   => $translator->trans('preteur-operations-vos-operations_remboursement-anticipe-preteur'),
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT      => $translator->trans('preteur-operations-vos-operations_remboursement-recouvrement-preteur'),
                \transactions_types::TYPE_LENDER_BALANCE_TRANSFER        => $translator->trans('preteur-operations-vos-operations_balance-transfer')
            );

            $this->lTrans = $this->transactions->select('type_transaction IN (' . implode(', ', array_keys($this->lesStatuts)) . ') AND status = ' . \transactions::STATUS_VALID . ' AND id_client = ' . $this->clients->id_client . ' AND YEAR(date_transaction) = ' . $_POST['year'], 'added DESC');
        }

        $this->setView('../preteurs/transactions');
    }

    public function _check_status_dossier()
    {
        $this->autoFireView = false;

        $this->projects  = $this->loadData('projects');
        $this->companies = $this->loadData('companies');
        $this->clients   = $this->loadData('clients');

        if (isset($_POST['status'], $_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            $form_ok = true;

            if ($this->projects->amount <= 0 || $this->projects->period <= 0) {
                $form_ok = false;
            } elseif (! $this->companies->get($this->projects->id_company, 'id_company')) {
                $form_ok = false;
            } elseif (! $this->clients->get($this->companies->id_client_owner, 'id_client')) {
                $form_ok = false;
            }

            if ($form_ok == true) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                $oProjectManager = $this->get('unilend.service.project_manager');
                $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], $_POST['status'], $this->projects);

                /** @var \projects_status_history $oProjectStatusHistory */
                $oProjectStatusHistory = $this->loadData('projects_status_history');
                $oProjectStatusHistory->loadLastProjectHistory($this->projects->id_project);

                /** @var \projects_status $oProjectStatus */
                $oProjectStatus = $this->loadData('projects_status');
                $oProjectStatus->get($oProjectStatusHistory->id_project_status);

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
                $projectStatusManager = $this->get('unilend.service.project_status_manager');
                $aPossibleStatus      = $projectStatusManager->getPossibleStatus($this->projects);

                if (count($aPossibleStatus) > 0) {
                    $select = '<select name="status" id="status" class="select">';
                    foreach ($aPossibleStatus as $s) {
                        $select .= '<option ' . ($this->projects->status == $s['status'] ? 'selected' : '') . ' value="' . $s['status'] . '">' . $s['label'] . '</option>';
                    }
                    $select .= '</select>';
                } else {
                    $select = '<input type="hidden" name="status" id="status" value="' . $this->projects->status . '" />';
                    $select .= $oProjectStatus->label;
                }

                if ($this->projects->status != \projects_status::REJETE) {
                    $etape_6  = '
                    <div class="tab_title" id="title_etape6">Etape 6</div>
                    <div class="tab_content" id="etape6">
                        <form method="post" name="dossier_etape6" id="dossier_etape6" action="" target="_parent">
                            <table class="form tableNotes" style="width: 100%;">
                                <tr>
                                    <th style="vertical-align:top;"><label for="performance_fianciere">Performance financière</label></th>
                                    <td>
                                        <span id="performance_fianciere"></span> / 10
                                    </td>
                                    <th style="vertical-align:top;"><label for="marche_opere">Marché opéré</label></th>
                                    <td style="vertical-align:top;">
                                        <span id="marche_opere"></span> / 10
                                    </td>
                                    <th><label for="dirigeance">Dirigeance</label></th>
                                    <td><input tabindex="6" id="dirigeance" class="input_court cal_moyen" type="text" name="dirigeance" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                    <th><label for="indicateur_risque_dynamique">Indicateur de risque dynamique</label></th>
                                    <td><input tabindex="7" id="indicateur_risque_dynamique" class="input_court cal_moyen" type="text" name="indicateur_risque_dynamique" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="vertical-align:top;">
                                        <table>
                                            <tr>
                                                <th><label for="structure">Structure</label></th>
                                                <td><input tabindex="1" class="input_court cal_moyen" type="text" name="structure" id="structure" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                            </tr>
                                            <tr>
                                                <th><label for="rentabilite">Rentabilité</label></th>
                                                <td><input tabindex="2" class="input_court cal_moyen" type="text" name="rentabilite" id="rentabilite" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                            </tr>
                                            <tr>
                                                <th><label for="tresorerie">Trésorerie</label></th>
                                                <td><input tabindex="3" class="input_court cal_moyen" type="text" name="tresorerie" id="tresorerie" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td colspan="2" style="vertical-align:top;">
                                        <table>
                                            <tr>
                                                <th><label for="global">Global</label></th>
                                                <td><input tabindex="4" class="input_court cal_moyen" type="text" name="global" id="global" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                            </tr>
                                            <tr>
                                                <th><label for="individuel">Individuel</label></th>
                                                <td><input tabindex="5" class="input_court cal_moyen" type="text" name="individuel" id="individuel" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                                <tr class="lanote">
                                    <th colspan="8" style="text-align:center;" >Note : <span class="moyenneNote" onkeyup="nodizaines(this.value,this.id);">N/A</span></th>
                                </tr>
                                <tr>
                                    <td colspan="8" style="text-align:center;">
                                        <label for="avis" style="text-align:left;display: block;">Avis :</label><br />
                                        <textarea name="avis" tabindex="8" style="height:700px;" id="avis" class="textarea_large avis" /></textarea>
                                        <script type="text/javascript">var ckedAvis = CKEDITOR.replace(\'avis\',{ height: 700});</script>
                                    </td>
                                </tr>
                            </table>
                            <br /><br />
                            <div id="valid_etape6" class="valid_etape">Données sauvegardées</div>';

                    if ($this->projects->status == \projects_status::REVUE_ANALYSTE) {
                        $etape_6 .= '
                            <div class="btnDroite listBtn_etape6">
                                <input type="button" onclick="valid_rejete_etape6(3,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape6"  value="Sauvegarder">
                                <input type="button" onclick="valid_rejete_etape6(1,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape6" style="background:#009933;border-color:#009933;" value="Valider">
                                <a href="' . $this->lurl . '/dossiers/ajax_rejection/6/' . $this->projects->id_project . '" class="btn btnValid_rejet_etape6 btn_link thickbox" style="background:#CC0000;border-color:#CC0000;">Rejeter</a>
                            </div>';
                    }

                    $etape_6 .= '
                        </form>
                    </div>

                    <script type="text/javascript">
                    $(".cal_moyen").keyup(function() {
                        var structure   = parseFloat($("#structure").val().replace(",","."));
                        var rentabilite = parseFloat($("#rentabilite").val().replace(",","."));
                        var tresorerie  = parseFloat($("#tresorerie").val().replace(",","."));
                        var global      = parseFloat($("#global").val().replace(",","."));
                        var individuel  = parseFloat($("#individuel").val().replace(",","."));

                        structure   = Math.round(structure * 10) / 10;
                        rentabilite = Math.round(rentabilite * 10) / 10;
                        tresorerie  = Math.round(tresorerie * 10) / 10;
                        global      = Math.round(global * 10) / 10;
                        individuel  = Math.round(individuel * 10) / 10;

                        var performance_fianciere = (structure + rentabilite + tresorerie) / 3;
                        performance_fianciere = Math.round(performance_fianciere * 10) / 10;

                        var marche_opere = (global + individuel) / 2;
                        marche_opere = Math.round(marche_opere * 10) / 10;

                        var dirigeance = parseFloat($("#dirigeance").val().replace(",","."));
                        var indicateur_risque_dynamique = parseFloat($("#indicateur_risque_dynamique").val().replace(",","."));

                        dirigeance = Math.round(dirigeance * 10) / 10;
                        indicateur_risque_dynamique = Math.round(indicateur_risque_dynamique * 10) / 10;

                        moyenne = Math.round((performance_fianciere * 0.2 + marche_opere * 0.2 + dirigeance * 0.2 + indicateur_risque_dynamique * 0.4) * 10) / 10;

                        $("#marche_opere").html(marche_opere);
                        $("#performance_fianciere").html(performance_fianciere);
                        $(".moyenneNote").html(moyenne + " / 10");
                    });
                    </script>';
                } else {
                    $etape_6 = '';

                    /** @var \projects_status_history_details $oHistoryDetails */
                    $oHistoryDetails                              = $this->loadData('projects_status_history_details');
                    $oHistoryDetails->id_project_status_history   = $oProjectStatusHistory->id_project_status_history;
                    $oHistoryDetails->commercial_rejection_reason = $_POST['rejection_reason'];
                    $oHistoryDetails->create();

                    /** @var \project_rejection_reason $oRejectionReason */
                    $oRejectionReason = $this->loadData('project_rejection_reason');
                    $oRejectionReason->get($_POST['rejection_reason']);

                    /** @var \settings $oSettings */
                    $oSettings = $this->loadData('settings');

                    $oSettings->get('Facebook', 'type');
                    $lien_fb = $oSettings->value;

                    $oSettings->get('Twitter', 'type');
                    $lien_tw = $oSettings->value;

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->furl,
                        'prenom_e'               => $this->clients->prenom,
                        'link_compte_emprunteur' => $this->furl,
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $varMail);
                    $message->setTo($this->clients->email);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }

                if (isset($oRejectionReason)) {
                    $select .= ' (' . $oRejectionReason->label . ')';
                }

                echo json_encode(array('liste' => $select, 'etape_6' => $etape_6));
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    public function _valid_rejete_etape6()
    {
        $this->autoFireView = false;

        $this->projects       = $this->loadData('projects');
        $this->projects_notes = $this->loadData('projects_notes');
        $this->companies      = $this->loadData('companies');
        $this->clients        = $this->loadData('clients');

        if (isset($_POST['status']) && isset($_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            $form_ok = true;
            if ($_POST['status'] == 1) {
                if (! isset($_POST['structure']) || $_POST['structure'] == 0 || $_POST['structure'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['rentabilite']) || $_POST['rentabilite'] == 0 || $_POST['rentabilite'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['tresorerie']) || $_POST['tresorerie'] == 0 || $_POST['tresorerie'] > 10) {
                    $form_ok = false;
                }

                if (! isset($_POST['performance_fianciere']) || $_POST['performance_fianciere'] == 0 || $_POST['performance_fianciere'] > 10) {
                    $form_ok = false;
                }

                if (! isset($_POST['individuel']) || $_POST['individuel'] == 0 || $_POST['individuel'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['global']) || $_POST['global'] == 0 || $_POST['global'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['marche_opere']) || $_POST['marche_opere'] == 0 || $_POST['marche_opere'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['dirigeance']) || $_POST['dirigeance'] == 0 || $_POST['dirigeance'] > 10) {
                    $form_ok = false;
                }
                if (! isset($_POST['indicateur_risque_dynamique']) || $_POST['indicateur_risque_dynamique'] == 0 || $_POST['indicateur_risque_dynamique'] > 10) {
                    $form_ok = false;
                }
            }
            if (! isset($_POST['avis']) && $_POST['status'] == 1 || strlen($_POST['avis']) < 50 && $_POST['status'] == 1) {
                $form_ok = false;
            }
            if (! $this->companies->get($this->projects->id_company, 'id_company')) {
                $form_ok = false;
            }
            if (! $this->clients->get($this->companies->id_client_owner, 'id_client')) {
                $form_ok = false;
            }

            if ($form_ok == true) {
                // on check si existe deja
                if ($this->projects_notes->get($_POST['id_project'], 'id_project')) {
                    $update = true;
                } else {
                    $update = false;
                }

                $this->projects_notes->structure                   = number_format($_POST['structure'], 1, '.', '');
                $this->projects_notes->rentabilite                 = number_format($_POST['rentabilite'], 1, '.', '');
                $this->projects_notes->tresorerie                  = number_format($_POST['tresorerie'], 1, '.', '');
                $this->projects_notes->performance_fianciere       = number_format($_POST['performance_fianciere'], 1, '.', '');
                $this->projects_notes->individuel                  = number_format($_POST['individuel'], 1, '.', '');
                $this->projects_notes->global                      = number_format($_POST['global'], 1, '.', '');
                $this->projects_notes->marche_opere                = number_format($_POST['marche_opere'], 1, '.', '');
                $this->projects_notes->dirigeance                  = number_format($_POST['dirigeance'], 1, '.', '');
                $this->projects_notes->indicateur_risque_dynamique = number_format($_POST['indicateur_risque_dynamique'], 1, '.', '');
                $this->projects_notes->note                        = round($this->projects_notes->performance_fianciere * 0.2 + $this->projects_notes->marche_opere * 0.2 + $this->projects_notes->dirigeance * 0.2 + $this->projects_notes->indicateur_risque_dynamique * 0.4, 1);
                $this->projects_notes->avis                        = $_POST['avis'];

                $this->projects_notes->structure_comite                   = empty($this->projects_notes->structure_comite) ? $this->projects_notes->structure : $this->projects_notes->structure_comite;
                $this->projects_notes->rentabilite_comite                 = empty($this->projects_notes->rentabilite_comite) ? $this->projects_notes->rentabilite : $this->projects_notes->rentabilite_comite;
                $this->projects_notes->tresorerie_comite                  = empty($this->projects_notes->tresorerie_comite) ? $this->projects_notes->tresorerie : $this->projects_notes->tresorerie_comite;
                $this->projects_notes->performance_fianciere_comite       = empty($this->projects_notes->performance_fianciere_comite) ? $this->projects_notes->performance_fianciere : $this->projects_notes->performance_fianciere_comite;
                $this->projects_notes->individuel_comite                  = empty($this->projects_notes->individuel_comite) ? $this->projects_notes->individuel : $this->projects_notes->individuel_comite;
                $this->projects_notes->global_comite                      = empty($this->projects_notes->global_comite) ? $this->projects_notes->global : $this->projects_notes->global_comite;
                $this->projects_notes->marche_opere_comite                = empty($this->projects_notes->marche_opere_comite) ? $this->projects_notes->marche_opere : $this->projects_notes->marche_opere_comite;
                $this->projects_notes->dirigeance_comite                  = empty($this->projects_notes->dirigeance_comite) ? $this->projects_notes->dirigeance : $this->projects_notes->dirigeance_comite;
                $this->projects_notes->indicateur_risque_dynamique_comite = empty($this->projects_notes->indicateur_risque_dynamique_comite) ? $this->projects_notes->indicateur_risque_dynamique : $this->projects_notes->indicateur_risque_dynamique_comite;
                $this->projects_notes->note_comite                        = empty($this->projects_notes->note_comite) ? $this->projects_notes->note : $this->projects_notes->note_comite;

                if ($update == true) {
                    $this->projects_notes->update();
                } else {
                    $this->projects_notes->id_project = $this->projects->id_project;
                    $this->projects_notes->create();
                }

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                $oProjectManager = $this->get('unilend.service.project_manager');

                if ($_POST['status'] == 1) {
                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::COMITE, $this->projects);
                } elseif ($_POST['status'] == 2) {
                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::REJET_ANALYSTE, $this->projects);

                    /** @var \projects_status_history $oProjectStatusHistory */
                    $oProjectStatusHistory = $this->loadData('projects_status_history');
                    $oProjectStatusHistory->loadLastProjectHistory($this->projects->id_project);

                    /** @var \projects_status_history_details $oHistoryDetails */
                    $oHistoryDetails                            = $this->loadData('projects_status_history_details');
                    $oHistoryDetails->id_project_status_history = $oProjectStatusHistory->id_project_status_history;
                    $oHistoryDetails->analyst_rejection_reason  = $_POST['rejection_reason'];
                    $oHistoryDetails->create();

                    /** @var \project_rejection_reason $oRejectionReason */
                    $oRejectionReason = $this->loadData('project_rejection_reason');
                    $oRejectionReason->get($_POST['rejection_reason']);

                    /** @var \settings $oSettings */
                    $oSettings = $this->loadData('settings');
                    $oSettings->get('Facebook', 'type');
                    $lien_fb = $oSettings->value;

                    $oSettings->get('Twitter', 'type');
                    $lien_tw = $oSettings->value;

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->furl,
                        'prenom_e'               => $this->clients->prenom,
                        'link_compte_emprunteur' => $this->furl,
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $varMail);
                    $message->setTo($this->clients->email);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }

                $currentProjectStatus = $this->loadData('projects_status');
                $currentProjectStatus->get($this->projects->status, 'status');

                $select = '<input type="hidden" name="status" id="status" value="' . $this->projects->status . '" />';
                $select .= $currentProjectStatus->label;

                if (isset($oRejectionReason)) {
                    $select .= ' (' . $oRejectionReason->label . ')';
                }

                if ($this->projects->status != \projects_status::REJET_ANALYSTE) {
                    $start = '';
                    if ($this->projects_notes->note_comite >= 0) {
                        $start = '2 étoiles';
                    }
                    if ($this->projects_notes->note_comite >= 2) {
                        $start = '2,5 étoiles';
                    }
                    if ($this->projects_notes->note_comite >= 4) {
                        $start = '3 étoiles';
                    }
                    if ($this->projects_notes->note_comite >= 5.5) {
                        $start = '3,5 étoiles';
                    }
                    if ($this->projects_notes->note_comite >= 6.5) {
                        $start = '4 étoiles';
                    }
                    if ($this->projects_notes->note_comite >= 7.5) {
                        $start = '4,5 étoiles';
                    }
                    if ($this->projects_notes->note_comite >= 8.5) {
                        $start = '5 étoiles';
                    }
                    $etape_7 = '
                    <div class="tab_title" id="title_etape7">Etape 7</div>
                    <div class="tab_content" id="etape7">
                        <table class="form tableNotes" style="width: 100%;">
                            <tr>
                                <th><label for="performance_fianciere_comite">Performance financière</label></th>
                                <td><span id="performance_fianciere_comite">' . $this->projects_notes->performance_fianciere_comite . '</span> / 10</td>
                                <th><label for="marche_opere_comite">Marché opéré</label></th>
                                <td><span id="marche_opere_comite">' . $this->projects_notes->marche_opere_comite . '</span> / 10</td>
                                <th><label for="dirigeance_comite">Dirigeance</label></th>
                                <td><input tabindex="14" id="dirigeance_comite" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->dirigeance_comite . '" name="dirigeance_comite" maxlength="4" onkeyup="nodizaines(this.value,this.id);"> / 10</td>
                                <th><label for="indicateur_risque_dynamique_comite">Indicateur de risque dynamique</label></th>
                                <td><input tabindex="15" id="indicateur_risque_dynamique_comite" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->indicateur_risque_dynamique_comite . '" name="indicateur_risque_dynamique" maxlength="4" onkeyup="nodizaines(this.value,this.id);"> / 10</td>
                            </tr>

                            <tr>
                                <td colspan="2" style="vertical-align:top;">
                                    <table>
                                        <tr>
                                            <th><label for="structure_comite">Structure</label></th>
                                            <td><input tabindex="9" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->structure_comite . '" name="structure2" id="structure_comite" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                        </tr>
                                        <tr>
                                            <th><label for="rentabilite_comite">Rentabilité</label></th>
                                            <td><input tabindex="10" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->rentabilite_comite . '" name="rentabilite_comite" id="rentabilite_comite" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                        </tr>
                                        <tr>
                                            <th><label for="tresorerie_comite">Trésorerie</label></th>
                                            <td><input tabindex="11" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->tresorerie_comite . '" name="tresorerie_comite" id="tresorerie_comite" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                        </tr>

                                    </table>
                                </td>
                                <td colspan="2" style="vertical-align:top;">
                                    <table>
                                        <tr>
                                            <th><label for="global_comite">Global</label></th>
                                            <td><input tabindex="12" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->global_comite . '" name="global_comite" id="global_comite" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                        </tr>
                                        <tr>
                                            <th><label for="individuel_comite">Individuel</label></th>
                                            <td><input tabindex="13" class="input_court cal_moyen" type="text" value="' . $this->projects_notes->individuel_comite . '" name="individuel_comite" id="individuel_comite" maxlength="4" onkeyup="nodizaines(this.value,this.id);"/> / 10</td>
                                        </tr>

                                    </table>
                                </td>
                                <td colspan="4"></td>
                            </tr>

                            <tr class="lanote">
                                <th colspan="8" style="text-align:center;" >Note : <span class="moyenneNote_comite">' . $this->projects_notes->note_comite . ' / 10 (soit ' . $start . ')</span></th>
                            </tr>

                            <tr>
                                <td colspan="8" style="text-align:center;">
                                <label for="avis_comite" style="text-align:left;display: block;">Avis comité:</label><br />
                                <textarea name="avis_comite" tabindex="16" style="height:700px;" id="avis_comite" class="textarea_large avis_comite">' . $this->projects_notes->avis_comite . '</textarea>
                                 <script type="text/javascript">var ckedAvis_comite = CKEDITOR.replace(\'avis_comite\',{ height: 700});</script>
                                </td>
                            </tr>
                        </table>

                        <br /><br />
                        <div id="valid_etape7" class="valid_etape">Données sauvegardées</div>
                        <div class="btnDroite">
                            <input type="button" onclick="valid_rejete_etape7(3,' . $this->projects->id_project . ')" class="btn"  value="Sauvegarder">
                        ';
                    if ($this->projects->status == \projects_status::COMITE) {
                        $etape_7 .= '
                            <input type="button" onclick="valid_rejete_etape7(1,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape7" style="background:#009933;border-color:#009933;" value="Valider">
                            <a href="' . $this->lurl . '/dossiers/ajax_rejection/7/' . $this->projects->id_project . '" class="btn btnValid_rejet_etape7 btn_link thickbox" style="background:#CC0000;border-color:#CC0000;">Rejeter</a>
                            <input type="button" onclick="valid_rejete_etape7(4,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape7" value="Plus d\'informations">';
                    }

                    $etape_7 .=
                        '</div>

                    </div>
                    <script type="text/javascript">
                        $(".cal_moyen").keyup(function() {
                            var structure   = parseFloat($("#structure_comite").val().replace(",","."));
                            var rentabilite = parseFloat($("#rentabilite_comite").val().replace(",","."));
                            var tresorerie  = parseFloat($("#tresorerie_comite").val().replace(",","."));
                            var global      = parseFloat($("#global_comite").val().replace(",","."));
                            var individuel  = parseFloat($("#individuel_comite").val().replace(",","."));

                            structure   = Math.round(structure * 10) / 10;
                            rentabilite = Math.round(rentabilite * 10) / 10;
                            tresorerie  = Math.round(tresorerie * 10) / 10;
                            global      = Math.round(global * 10) / 10;
                            individuel  = Math.round(individuel * 10) / 10;

                            var performance_fianciere = (structure + rentabilite + tresorerie) / 3;
                            performance_fianciere = Math.round(performance_fianciere * 10) / 10;

                            var marche_opere = (global + individuel) / 2;
                            marche_opere = Math.round(marche_opere * 10) / 10;

                            var dirigeance = parseFloat($("#dirigeance_comite").val().replace(",","."));
                            var indicateur_risque_dynamique = parseFloat($("#indicateur_risque_dynamique_comite").val().replace(",","."));

                            dirigeance = Math.round(dirigeance * 10) / 10;
                            indicateur_risque_dynamique = Math.round(indicateur_risque_dynamique * 10) / 10;

                            moyenne = Math.round((performance_fianciere * 0.2 + marche_opere * 0.2 + dirigeance * 0.2 + indicateur_risque_dynamique * 0.4) * 10) / 10;

                            $("#marche_opere_comite").html(marche_opere);
                            $("#performance_fianciere_comite").html(performance_fianciere);
                            var start = "";
                            if (moyenne >= 0) {
                                start = "2 étoiles";
                            }
                            if (moyenne >= 2) {
                                start = "2,5 étoiles";
                            }
                            if (moyenne >= 4) {
                                start = "3 étoiles";
                            }
                            if (moyenne >= 5.5) {
                                start = "3,5 étoiles";
                            }
                            if (moyenne >= 6.5) {
                                start = "4 étoiles";
                            }
                            if (moyenne >= 7.5) {
                                start = "4,5 étoiles";
                            }
                            if (moyenne >= 8.5) {
                                start = "5 étoiles";
                            }
                            $(".moyenneNote_comite").html(moyenne + " / 10" + " (soit " + start + ")");
                        });
                    </script>
                    ';
                } else {
                    $etape_7 = '';
                }

                echo json_encode(array('liste' => $select, 'etape_7' => $etape_7));
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    public function _valid_rejete_etape7()
    {
        $this->autoFireView = false;

        $this->projects       = $this->loadData('projects');
        $this->projects_notes = $this->loadData('projects_notes');
        $this->companies      = $this->loadData('companies');
        $this->clients        = $this->loadData('clients');

        // on check si on a les posts
        if (isset($_POST['status']) && isset($_POST['id_project']) && $this->projects->get($_POST['id_project'], 'id_project')) {
            $form_ok = true;

            if ($_POST['status'] == 1) {
                if (false === isset($_POST['structure_comite']) || $_POST['structure_comite'] == 0 || $_POST['structure_comite'] > 10) {
                    $form_ok = false;
                }
                if (false === isset($_POST['rentabilite_comite']) || $_POST['rentabilite_comite'] == 0 || $_POST['rentabilite_comite'] > 10) {
                    $form_ok = false;
                }
                if (false === isset($_POST['tresorerie_comite']) || $_POST['tresorerie_comite'] == 0 || $_POST['tresorerie_comite'] > 10) {
                    $form_ok = false;
                }

                if (false === isset($_POST['performance_fianciere_comite']) || $_POST['performance_fianciere_comite'] == 0 || $_POST['performance_fianciere_comite'] > 10) {
                    $form_ok = false;
                }

                if (false === isset($_POST['individuel_comite']) || $_POST['individuel_comite'] == 0 || $_POST['individuel_comite'] > 10) {
                    $form_ok = false;
                }
                if (false === isset($_POST['global_comite']) || $_POST['global_comite'] == 0 || $_POST['global_comite'] > 10) {
                    $form_ok = false;
                }
                if (false === isset($_POST['marche_opere_comite']) || $_POST['marche_opere_comite'] == 0 || $_POST['marche_opere_comite'] > 10) {
                    $form_ok = false;
                }
                if (false === isset($_POST['dirigeance_comite']) || $_POST['dirigeance_comite'] == 0 || $_POST['dirigeance_comite'] > 10) {
                    $form_ok = false;
                }
                if (false === isset($_POST['indicateur_risque_dynamique_comite']) || $_POST['indicateur_risque_dynamique_comite'] == 0 || $_POST['indicateur_risque_dynamique_comite'] > 10) {
                    $form_ok = false;
                }
            }

            if (false === isset($_POST['avis_comite']) && $_POST['status'] == 1 || strlen($_POST['avis_comite']) < 50 && $_POST['status'] == 1) {
                $form_ok = false;
            }

            if (false === $this->companies->get($this->projects->id_company, 'id_company')) {
                $form_ok = false;
            }
            if (false === $this->clients->get($this->companies->id_client_owner, 'id_client')) {
                $form_ok = false;
            }

            if ($form_ok == true) {
                // on check si existe deja
                if ($this->projects_notes->get($_POST['id_project'], 'id_project')) {
                    $update = true;
                } else {
                    $update = false;
                }

                $this->projects_notes->structure_comite                   = number_format($_POST['structure_comite'], 1, '.', '');
                $this->projects_notes->rentabilite_comite                 = number_format($_POST['rentabilite_comite'], 1, '.', '');
                $this->projects_notes->tresorerie_comite                  = number_format($_POST['tresorerie_comite'], 1, '.', '');
                $this->projects_notes->performance_fianciere_comite       = number_format($_POST['performance_fianciere_comite'], 1, '.', '');
                $this->projects_notes->individuel_comite                  = number_format($_POST['individuel_comite'], 1, '.', '');
                $this->projects_notes->global_comite                      = number_format($_POST['global_comite'], 1, '.', '');
                $this->projects_notes->marche_opere_comite                = number_format($_POST['marche_opere_comite'], 1, '.', '');
                $this->projects_notes->dirigeance_comite                  = number_format($_POST['dirigeance_comite'], 1, '.', '');
                $this->projects_notes->indicateur_risque_dynamique_comite = number_format($_POST['indicateur_risque_dynamique_comite'], 1, '.', '');
                $this->projects_notes->note_comite                        = round($this->projects_notes->performance_fianciere_comite * 0.2 + $this->projects_notes->marche_opere_comite * 0.2 + $this->projects_notes->dirigeance_comite * 0.2 + $this->projects_notes->indicateur_risque_dynamique_comite * 0.4, 1);
                $this->projects_notes->avis_comite                        = $_POST['avis_comite'];

                // on enregistre
                if ($update == true) {
                    $this->projects_notes->update();
                } else {
                    $this->projects_notes->id_project = $this->projects->id_project;
                    $this->projects_notes->create();
                }

                // etoiles
                // A = 5
                // B = 4.5
                // C = 4
                // D = 3.5
                // E = 3
                // F = 2.5
                // G = 2
                // H = 1.5
                // I = 1
                // J = 0

                if ($this->projects_notes->note_comite >= 0 && $this->projects_notes->note_comite < 2) {
                    $lettre = 'I';
                } elseif ($this->projects_notes->note_comite >= 2 && $this->projects_notes->note_comite < 4) {
                    $lettre = 'G';
                } elseif ($this->projects_notes->note_comite >= 4 && $this->projects_notes->note_comite < 5.5) {
                    $lettre = 'E';
                } elseif ($this->projects_notes->note_comite >= 5.5 && $this->projects_notes->note_comite < 6.5) {
                    $lettre = 'D';
                } elseif ($this->projects_notes->note_comite >= 6.5 && $this->projects_notes->note_comite < 7.5) {
                    $lettre = 'C';
                } elseif ($this->projects_notes->note_comite >= 7.5 && $this->projects_notes->note_comite < 8.5) {
                    $lettre = 'B';
                } elseif ($this->projects_notes->note_comite >= 8.5 && $this->projects_notes->note_comite <= 10) {
                    $lettre = 'A';
                }

                $this->projects->risk = $lettre;
                $this->projects->update();

                $btn_etape7   = '';
                $content_risk = '';

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                $oProjectManager = $this->get('unilend.service.project_manager');

                if ($_POST['status'] == 1) {
                    $aProjects = $this->projects->select('id_company = ' . $this->projects->id_company);

                    /** @var \projects_status_history $oProjectStatusHistory */
                    $oProjectStatusHistory = $this->loadData('projects_status_history');

                    $aExistingStatus = array();
                    foreach ($aProjects as $aProject) {
                        $aStatusHistory = $oProjectStatusHistory->getHistoryDetails($aProject['id_project']);
                        foreach ($aStatusHistory as $aStatus) {
                            $aExistingStatus[] = $aStatus['status'];
                        }
                    }

                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::PREP_FUNDING, $this->projects);

                    $latitude  = (float) $this->companies->latitude;
                    $longitude = (float) $this->companies->longitude;

                    if (empty($latitude) && empty($longitude)) {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LocationManager $location */
                        $location    = $this->get('unilend.service.location_manager');
                        $coordinates = $location->getCompanyCoordinates($this->companies);

                        if ($coordinates) {
                            $this->companies->latitude  = $coordinates['latitude'];
                            $this->companies->longitude = $coordinates['longitude'];
                            $this->companies->update();
                        }
                    }

                    if (false === in_array(\projects_status::PREP_FUNDING, $aExistingStatus)) {
                        $this->sendEmailBorrowerArea('ouverture-espace-emprunteur-plein', $this->clients);
                    }

                    $content_risk = '
                        <th><label for="risk">Niveau de risque* :</label></th>
                        <td>
                            <select name="risk" id="risk" class="select" style="width:160px;background-color:#AAACAC;">
                                <option value="">Choisir</option>
                                <option ' . ($this->projects->risk == 'A' ? 'selected' : '') . ' value="A">5 étoiles</option>
                                <option ' . ($this->projects->risk == 'B' ? 'selected' : '') . ' value="B">4,5 étoiles</option>
                                <option ' . ($this->projects->risk == 'C' ? 'selected' : '') . ' value="C">4 étoiles</option>
                                <option ' . ($this->projects->risk == 'D' ? 'selected' : '') . ' value="D">3,5 étoiles</option>
                                <option ' . ($this->projects->risk == 'E' ? 'selected' : '') . ' value="E">3 étoiles</option>
                                <option ' . ($this->projects->risk == 'F' ? 'selected' : '') . ' value="F">2,5 étoiles</option>
                                <option ' . ($this->projects->risk == 'G' ? 'selected' : '') . ' value="G">2 étoiles</option>
                                <option ' . ($this->projects->risk == 'H' ? 'selected' : '') . ' value="H">1,5 étoiles</option>
                            </select>
                        </td>
                    ';
                } elseif ($_POST['status'] == 2) {
                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::REJET_COMITE, $this->projects);

                    /** @var \projects_status_history $oProjectStatusHistory */
                    $oProjectStatusHistory = $this->loadData('projects_status_history');
                    $oProjectStatusHistory->loadLastProjectHistory($this->projects->id_project);

                    /** @var \projects_status_history_details $oHistoryDetails */
                    $oHistoryDetails                            = $this->loadData('projects_status_history_details');
                    $oHistoryDetails->id_project_status_history = $oProjectStatusHistory->id_project_status_history;
                    $oHistoryDetails->comity_rejection_reason   = $_POST['rejection_reason'];
                    $oHistoryDetails->create();

                    /** @var \project_rejection_reason $oRejectionReason */
                    $oRejectionReason = $this->loadData('project_rejection_reason');
                    $oRejectionReason->get($_POST['rejection_reason']);

                    /** @var \settings $oSettings */
                    $oSettings = $this->loadData('settings');
                    $oSettings->get('Facebook', 'type');
                    $lien_fb = $oSettings->value;
                    $oSettings->get('Twitter', 'type');
                    $lien_tw = $oSettings->value;

                    $varMail = array(
                        'surl'                   => $this->surl,
                        'url'                    => $this->furl,
                        'prenom_e'               => $this->clients->prenom,
                        'link_compte_emprunteur' => $this->furl,
                        'lien_fb'                => $lien_fb,
                        'lien_tw'                => $lien_tw
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $varMail);
                    $message->setTo($this->clients->email);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);

                } elseif ($_POST['status'] == 4) {
                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::REVUE_ANALYSTE, $this->projects);

                    $btn_etape7 = '
                        <input type="button" onclick="valid_rejete_etape6(3,' . $this->projects->id_project . ')" class="btn"  value="Sauvegarder">
                        <input type="button" onclick="valid_rejete_etape6(1,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape6" style="background:#009933;border-color:#009933;" value="Valider">
                        <a href="' . $this->lurl . '/dossiers/ajax_rejection/6/' . $this->projects->id_project . '" class="btn btnValid_rejet_etape6 btn_link thickbox" style="background:#CC0000;border-color:#CC0000;">Rejeter</a>
                        <input type="button" onclick="valid_rejete_etape6(2,' . $this->projects->id_project . ')" class="btn btnValid_rejet_etape6" style="background:#CC0000;border-color:#CC0000;" value="Rejeter">
                    ';
                }

                if (false === empty($this->projects->risk) && false === empty($this->projects->period)
                    && false === in_array($this->projects->status, [projects_status::REJETE, projects_status::REJET_ANALYSTE, projects_status::REJET_COMITE] )) {
                    try {
                        $this->projects->id_rate = $oProjectManager->getProjectRateRangeId($this->projects);
                        $this->projects->update();
                    } catch (\Exception $exception) {
                        echo json_encode(array('liste' => '', 'btn_etape6' => '', 'content_risk' => '', 'error' => $exception->getMessage()));
                        return;
                    }
                }

                /** @var \projects_status $oProjectStatus */
                $oProjectStatus = $this->loadData('projects_status');
                $select = '<input type="hidden" name="current_status" value="' . $this->projects->status . '">';

                if ($this->projects->status == \projects_status::PREP_FUNDING) {
                    $select .= '<select name="status" id="status" class="select">';
                    foreach ([\projects_status::PREP_FUNDING, \projects_status::A_FUNDER] as $status) {
                        $oProjectStatus->get($status, 'status');
                        $select .= '<option' . ($this->projects->status == $oProjectStatus->status ? ' selected' : '') . ' value="' . $this->projects->status . '">' . $oProjectStatus->label . '</option>';
                    }
                    $select .= '</select>';
                } else {
                    $oProjectStatus->get($this->projects->status, 'status');

                    $select .= '<input type="hidden" name="status" id="status" value="' . $this->projects->status . '" />';
                    $select .= $oProjectStatus->label;

                    if (isset($oRejectionReason)) {
                        $select .= ' (' . $oRejectionReason->label . ')';
                    }
                }

                echo json_encode(array('liste' => $select, 'btn_etape6' => $btn_etape7, 'content_risk' => $content_risk));
            } else {
                echo 'nok';
            }
        } else {
            echo 'nok';
        }
    }

    public function _session_content_email_completude()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_client']) && isset($_POST['content']) && isset($_POST['liste'])) {
            $_SESSION['content_email_completude'][$_POST['id_client']] = '<ul>' . $this->ficelle->speChar2HtmlEntities($_POST['liste']) . '</ul>' . ($_POST['content'] != '' ? '<br>' : '') . nl2br(htmlentities($_POST['content']));
            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    public function _session_project_completude()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_project']) && isset($_POST['content']) && isset($_POST['list'])) {
            $_SESSION['project_submission_files_list'][$_POST['id_project']] = '<ul>' . $this->ficelle->speChar2HtmlEntities($_POST['list']) . '</ul>' . nl2br($_POST['content']);
            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    public function _check_force_pass()
    {
        $this->autoFireView = false;
        $this->tab_result   = $this->ficelle->testpassword($_POST['pass']);

        if ($this->tab_result['score'] < 50) {
            $this->couleur = "red";
            $this->wording = "Faible";
        } elseif ($this->tab_result['score'] < 100) {
            $this->couleur = "orange";
            $this->wording = "Moyen";
        } elseif ($this->tab_result['score'] <= 500) {
            $this->couleur = "green";
            $this->wording = "Fort";
        } elseif ($this->tab_result['score'] > 500) {
            $this->couleur = "green";
            $this->wording = "Tr&egrave;s fort";
        }

        echo '
            <p class="password-info" >Indicateur de protection :
                <span style="color:' . $this->couleur . '">' . $this->wording . '</span>
            </p>
        ';
        die;
    }

    public function _ibanExist()
    {

        $companies = $this->loadData('companies');
        $list      = array();
        foreach ($companies->select('id_client_owner != "' . $this->bdd->escape_string($_POST['id']) . '" AND iban = "' . $this->bdd->escape_string($_POST['iban']) . '"') as $company) {
            $list[] = $company['id_company'] . ': ' . $company['name'];
        }
        if (count($list) != 0) {
            echo implode(' / ', $list);
        } else {
            echo "none";
        }
        die;
    }

    public function _ibanExistV2()
    {
        $this->autoFireView = false;

        $companies = $this->loadData('companies');
        $list      = array();
        foreach (
            $companies->select('
                id_client_owner != "' . $this->bdd->escape_string($_POST['id']) . '"
                AND iban = "' . $this->bdd->escape_string($_POST['iban']) . '"
                AND bic = "' . $this->bdd->escape_string($_POST['bic']) . '"'
            ) as $company
        ) {
            $list[] = $company['id_company'];
        }
        if (count($list) != 0) {
            echo implode('-', $list);
        } else {
            echo "none";
        }
        die;
    }

    public function _get_cities()
    {
        $this->autoFireView = false;
        $aCities = array();
        if (isset($_GET['term']) && '' !== trim($_GET['term'])) {
            $_GET  = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
            $oVilles = $this->loadData('villes');

            $bBirthPlace = false;
            if (isset($this->params[0]) && 'birthplace' === $this->params[0]) {
                $bBirthPlace = true;
            }

            if ($bBirthPlace) {
                $aResults = $oVilles->lookupCities($_GET['term'], array('ville', 'cp'), true);
            } else {
                $aResults = $oVilles->lookupCities($_GET['term']);
            }
            if (false === empty($aResults)) {
                if ($bBirthPlace) {
                    foreach ($aResults as $aItem) {

                        // unique insee code
                        $aCities[$aItem['insee']] = array(
                            'label' => $aItem['ville'] . ' (' . $aItem['num_departement'] . ')',
                            'value' => $aItem['insee']
                        );
                    }
                    $aCities = array_values($aCities);
                } else {
                    foreach ($aResults as $aItem) {
                        $aCities[] = array(
                            'label' => $aItem['ville'] . ' (' . $aItem['cp'] . ')',
                            'value' => $aItem['insee']
                        );
                    }
                }
            }
        }

        echo json_encode($aCities);
    }

    public function _updateClientFiscalAddress()
    {
        $this->autoFireView = false;

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $sResult = 'nok';

        if (isset($this->params[0]) && isset($this->params[1])) {
            if ('0' == $this->params[1]) {
                /** @var clients_adresses $oClientAddress */
                $oClientAddress = $this->loadData('clients_adresses');

                if ($oClientAddress->get($this->params[0], 'id_client')) {

                    $oClientAddress->cp_fiscal    = $_POST['zip'];
                    $oClientAddress->ville_fiscal = $_POST['city'];
                    $oClientAddress->update();
                    $sResult = 'ok';
                }
            } elseif ('1' == $this->params[1]) {
                /** @var companies $oCompanies */
                $oCompanies = $this->loadData('companies');
                if ($oCompanies->get($this->params[0], 'id_client_owner')) {

                    $oCompanies->zip = $_POST['zip'];
                    $oCompanies->city = $_POST['city'];
                    $oCompanies->update();
                    $sResult = 'ok';
                }
            }
        }

        echo $sResult;
    }

    public function _patchClient()
    {
        $this->autoFireView = false;
        $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        /** @var clients $oClient */
        $oClient = $this->loadData('clients');

        $sResult = 'nok';

        if (isset($this->params[0]) && $oClient->get($this->params[0])) {
            foreach ($_POST as $item => $value) {
                $oClient->$item = $value;
            }
            $oClient->update();
            $sResult = 'ok';
        }

        echo $sResult;
    }

    public function _send_email_borrower_area()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_client'], $_POST['type'])) {
            $oClients = $this->loadData('clients');
            $oClients->get($_POST['id_client'], 'id_client');

            switch ($_POST['type']) {
                case 'open':
                    $sTypeEmail = 'ouverture-espace-emprunteur';
                    break;
                case 'initialize':
                    $sTypeEmail = 'mot-de-passe-oublie-emprunteur';
                    break;
            }
            $this->sendEmailBorrowerArea($sTypeEmail, $oClients);
        }
    }

    private function sendEmailBorrowerArea($sTypeEmail, clients $oClients)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->loadData('settings');
        $oSettings->get('Facebook', 'type');
        $sFacebookURL = $this->settings->value;
        $oSettings->get('Twitter', 'type');
        $sTwitterURL = $this->settings->value;

        /** @var \temporary_links_login $oTemporaryLink */
        $oTemporaryLink = $this->loadData('temporary_links_login');
        $sTemporaryLink = $this->surl . '/espace_emprunteur/securite/' . $oTemporaryLink->generateTemporaryLink($oClients->id_client, \temporary_links_login::PASSWORD_TOKEN_LIFETIME_LONG);

        $aVariables = array(
            'surl'                   => $this->surl,
            'url'                    => $this->url,
            'link_compte_emprunteur' => $sTemporaryLink,
            'lien_fb'                => $sFacebookURL,
            'lien_tw'                => $sTwitterURL,
            'prenom'                 => $oClients->prenom
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($sTypeEmail, $aVariables);
        $message->setTo($oClients->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }
}
