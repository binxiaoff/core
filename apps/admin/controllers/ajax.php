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

    public function _valid_etapes()
    {
        $this->autoFireView = false;

        if (isset($_POST['id_project']) && isset($_POST['etape'])) {
            $this->projects         = $this->loadData('projects');
            $this->companies        = $this->loadData('companies');
            $this->clients          = $this->loadData('clients');
            $this->clients_adresses = $this->loadData('clients_adresses');

            $serialize = serialize($_POST);
            $this->users_history->histo(8, 'dossier edit etapes', $_SESSION['user']['id_user'], $serialize);

            if ($_POST['etape'] == 1) {
                $this->projects->get($_POST['id_project'], 'id_project');
                $this->projects->amount = $this->ficelle->cleanFormatedNumber($_POST['montant_etape1']);
                $this->projects->period = (0 < (int) $_POST['duree_etape1']) ? (int) $_POST['duree_etape1'] : $this->projects->period;
                $this->projects->update();

                $this->companies->get($this->projects->id_company, 'id_company');
                $this->companies->siren = $_POST['siren_etape1'];
                $this->companies->update();

                $this->clients->get($this->companies->id_client_owner);
                $this->clients->source = $_POST['source_etape1'];
                $this->clients->update();
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager $projectRequestManager */
                $projectRequestManager = $this->get('unilend.service.project_request_manager');
                $result = $projectRequestManager->checkProjectRisk($this->projects, $_SESSION['user']['id_user']);

                if (true === is_array($result) && \projects_status::NON_ELIGIBLE_REASON_UNKNOWN_SIREN === $result['motive']) {
                    echo 'Siren inconu';
                    return;
                } elseif (empty($this->companies->code_naf)) {
                    echo 'Problème lors de la récupération des données de la société';
                    return;
                }
                echo 'OK';
                return;
            } elseif ($_POST['etape'] == 2) {
                $this->projects->get($_POST['id_project'], 'id_project');
                $this->projects->id_prescripteur = ('true' === $_POST['has_prescripteur']) ? $_POST['id_prescripteur'] : 0;
                $this->projects->balance_count   = empty($this->projects->balance_count) && false === empty($_POST['creation_date_etape2']) ? \DateTime::createFromFormat('d/m/Y', $_POST['creation_date_etape2'])->diff(new \DateTime())->y : $this->projects->balance_count;
                $this->projects->update();

                $this->companies->get($this->projects->id_company, 'id_company');
                $this->companies->name                          = $_POST['raison_sociale_etape2'];
                $this->companies->forme                         = $_POST['forme_juridique_etape2'];
                $this->companies->capital                       = $this->ficelle->cleanFormatedNumber($_POST['capital_social_etape2']);
                $this->companies->date_creation                 = empty($_POST['creation_date_etape2']) ? '' : \DateTime::createFromFormat('d/m/Y', $_POST['creation_date_etape2'])->format('Y-m-d');
                $this->companies->adresse1                      = $_POST['address_etape2'];
                $this->companies->city                          = $_POST['ville_etape2'];
                $this->companies->zip                           = $_POST['postal_etape2'];
                $this->companies->phone                         = $_POST['phone_etape2'];
                $this->companies->status_adresse_correspondance = isset($_POST['same_address_etape2']) && 'on' === $_POST['same_address_etape2'] ? 1 : 0;
                $this->companies->status_client                 = $_POST['enterprise_etape2'];
                $this->companies->latitude                      = (float) str_replace(',', '.', $_POST['latitude']);
                $this->companies->longitude                     = (float) str_replace(',', '.', $_POST['longitude']);
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
                /** @var projects $project */
                $project = $this->loadData('projects');
                $project->get($_POST['id_project'], 'id_project');

                if (isset($_FILES['photo_projet']) && false === empty($_FILES['photo_projet']['name'])) {
                    $this->upload->setUploadDir($this->path, 'public/default/images/dyn/projets/source/');
                    $this->upload->setExtValide(array('jpeg', 'JPEG', 'jpg', 'JPG'));

                    $imagick     = new \Imagick($_FILES['photo_projet']['tmp_name']);
                    $imageConfig = $this->getParameter('image_resize');

                    if ($imagick->getImageWidth() > $imageConfig['projets']['width'] || $imagick->getImageHeight() > $imageConfig['projets']['height']) {
                        $error = 'Erreur upload photo : taille max dépassée (' . $imageConfig['projets']['width'] . 'x' . $imageConfig['projets']['height'] . ')';
                    } elseif ($this->upload->doUpload('photo_projet', '', true)) {
                        // Delete previous image of the name was different from the new one
                        if (false === empty($this->projects->photo_projet) && $this->projects->photo_projet != $this->upload->getName()) {
                            @unlink($this->path . 'public/default/images/dyn/projets/source/' . $this->projects->photo_projet);
                        }
                        $project->photo_projet = $this->upload->getName();
                    } else {
                        $error = 'Erreur upload photo : ' . $this->upload->getErrorType();
                    }
                }

                $project->comments             = $project->create_bo ? $_POST['comments_etape3'] : '';
                $project->objectif_loan        = $_POST['objectif_etape3'];
                $project->nature_project       = $_POST['nature_project'];
                $project->presentation_company = $_POST['presentation_etape3'];
                $project->means_repayment      = $_POST['moyen_etape3'];
                $project->update();

                if (isset($error)) {
                    echo json_encode([
                        'error'   => true,
                        'message' => $error
                    ]);
                } else {
                    echo json_encode([
                        'success' => true
                    ]);
                }
            } elseif ($_POST['etape'] == 4.1) {
                /** @var projects $oProject */
                $oProject = $this->loadData('projects');

                if ($oProject->get($_POST['id_project'], 'id_project') && $oProject->status <= \projects_status::COMITY_REVIEW) {
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

                        if (false === isset($aRatings[$sRating]) || $aRatings[$sRating]['value'] != $mValue) {
                            $bAddHistory = true;
                            $aRatings[$sRating]['value'] = $mValue;
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

                        foreach ($aRatings as $sRating => $aRating) {
                            $oCompanyRating->id_company_rating_history = $oCompanyRatingHistory->id_company_rating_history;
                            $oCompanyRating->type                      = $sRating;
                            $oCompanyRating->value                     = $aRating['value'];
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
                        $companyBalanceSheetManager->getIncomeStatement($oCompanyAnnualAccounts);
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

        /** @var \projects $project */
        $project = $this->loadData('projects');
        /** @var \companies $company */
        $company = $this->loadData('companies');
        /** @var \clients $client */
        $client = $this->loadData('clients');

        if (
            false === isset($_POST['status'], $_POST['id_project'])
            || false === $project->get($_POST['id_project'], 'id_project')
            || false === $company->get($project->id_company, 'id_company')
            || false === $client->get($company->id_client_owner, 'id_client')
            || $project->period <= 0
            || $project->amount <= 0
        ) {
            echo 'nok';
            return;
        }

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        $projectManager->addProjectStatus($_SESSION['user']['id_user'], $_POST['status'], $project);

        if ($project->status == \projects_status::COMMERCIAL_REJECTION) {
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->loadData('projects_status_history');
            $projectStatusHistory->loadLastProjectHistory($project->id_project);

            /** @var \projects_status_history_details $historyDetails */
            $historyDetails                              = $this->loadData('projects_status_history_details');
            $historyDetails->id_project_status_history   = $projectStatusHistory->id_project_status_history;
            $historyDetails->commercial_rejection_reason = $_POST['rejection_reason'];
            $historyDetails->create();

            /** @var \settings $settings */
            $settings = $this->loadData('settings');
            $settings->get('Facebook', 'type');
            $facebookLink = $settings->value;

            $settings->get('Twitter', 'type');
            $twitterLink = $settings->value;

            $keywords = array(
                'surl'                   => $this->surl,
                'url'                    => $this->furl,
                'prenom_e'               => $client->prenom,
                'link_compte_emprunteur' => $this->furl,
                'lien_fb'                => $facebookLink,
                'lien_tw'                => $twitterLink
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $keywords);
            $message->setTo($client->email);
            $mailer = $this->get('mailer');
            $mailer->send($message);
        }

        echo 'ok';
    }

    public function _valid_rejete_etape6()
    {
        $this->autoFireView = false;

        /** @var \projects $project */
        $project = $this->loadData('projects');
        /** @var \projects_notes $projectRating */
        $projectRating = $this->loadData('projects_notes');
        /** @var \companies $company */
        $company = $this->loadData('companies');
        /** @var \clients $client */
        $client = $this->loadData('clients');

        if (
            false === isset($_POST['id_project'], $_POST['status'])
            || false === $project->get($_POST['id_project'], 'id_project')
            || false === $company->get($project->id_company, 'id_company')
            || false === $client->get($company->id_client_owner, 'id_client')
            || $_POST['status'] == 1 && (
                empty($_POST['structure']) || $_POST['structure'] > 10
                || empty($_POST['rentabilite']) || $_POST['rentabilite'] > 10
                || empty($_POST['tresorerie']) || $_POST['tresorerie'] > 10
                || empty($_POST['performance_fianciere']) || $_POST['performance_fianciere'] > 10
                || empty($_POST['individuel']) || $_POST['individuel'] > 10
                || empty($_POST['global']) || $_POST['global'] > 10
                || empty($_POST['marche_opere']) || $_POST['marche_opere'] > 10
                || empty($_POST['dirigeance']) || $_POST['dirigeance'] > 10
                || empty($_POST['indicateur_risque_dynamique']) || $_POST['indicateur_risque_dynamique'] > 10
                || empty($_POST['avis']) || strlen($_POST['avis']) < 50
            )
        ) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            return;
        }

        $update = $projectRating->get($_POST['id_project'], 'id_project');

        $projectRating->structure                   = round(str_replace(',', '.', $_POST['structure']), 1);
        $projectRating->rentabilite                 = round(str_replace(',', '.', $_POST['rentabilite']), 1);
        $projectRating->tresorerie                  = round(str_replace(',', '.', $_POST['tresorerie']), 1);
        $projectRating->performance_fianciere       = round(str_replace(',', '.', $_POST['performance_fianciere']), 1);
        $projectRating->individuel                  = round(str_replace(',', '.', $_POST['individuel']), 1);
        $projectRating->global                      = round(str_replace(',', '.', $_POST['global']), 1);
        $projectRating->marche_opere                = round(str_replace(',', '.', $_POST['marche_opere']), 1);
        $projectRating->dirigeance                  = round(str_replace(',', '.', $_POST['dirigeance']), 1);
        $projectRating->indicateur_risque_dynamique = round(str_replace(',', '.', $_POST['indicateur_risque_dynamique']), 1);
        $projectRating->note                        = round($projectRating->performance_fianciere * 0.2 + $projectRating->marche_opere * 0.2 + $projectRating->dirigeance * 0.2 + $projectRating->indicateur_risque_dynamique * 0.4, 1);
        $projectRating->avis                        = $_POST['avis'];

        $projectRating->structure_comite                   = empty($projectRating->structure_comite) ? $projectRating->structure : $projectRating->structure_comite;
        $projectRating->rentabilite_comite                 = empty($projectRating->rentabilite_comite) ? $projectRating->rentabilite : $projectRating->rentabilite_comite;
        $projectRating->tresorerie_comite                  = empty($projectRating->tresorerie_comite) ? $projectRating->tresorerie : $projectRating->tresorerie_comite;
        $projectRating->performance_fianciere_comite       = empty($projectRating->performance_fianciere_comite) ? $projectRating->performance_fianciere : $projectRating->performance_fianciere_comite;
        $projectRating->individuel_comite                  = empty($projectRating->individuel_comite) ? $projectRating->individuel : $projectRating->individuel_comite;
        $projectRating->global_comite                      = empty($projectRating->global_comite) ? $projectRating->global : $projectRating->global_comite;
        $projectRating->marche_opere_comite                = empty($projectRating->marche_opere_comite) ? $projectRating->marche_opere : $projectRating->marche_opere_comite;
        $projectRating->dirigeance_comite                  = empty($projectRating->dirigeance_comite) ? $projectRating->dirigeance : $projectRating->dirigeance_comite;
        $projectRating->indicateur_risque_dynamique_comite = empty($projectRating->indicateur_risque_dynamique_comite) ? $projectRating->indicateur_risque_dynamique : $projectRating->indicateur_risque_dynamique_comite;
        $projectRating->note_comite                        = empty($projectRating->note_comite) ? $projectRating->note : $projectRating->note_comite;

        if ($update == true) {
            $projectRating->update();
        } else {
            $projectRating->id_project = $project->id_project;
            $projectRating->create();
        }

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');

        if ($_POST['status'] == 1) {
            $projectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::COMITY_REVIEW, $project);
        } elseif ($_POST['status'] == 2) {
            $projectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::ANALYSIS_REJECTION, $project);

            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->loadData('projects_status_history');
            $projectStatusHistory->loadLastProjectHistory($project->id_project);

            /** @var \projects_status_history_details $historyDetails */
            $historyDetails                            = $this->loadData('projects_status_history_details');
            $historyDetails->id_project_status_history = $projectStatusHistory->id_project_status_history;
            $historyDetails->analyst_rejection_reason  = $_POST['rejection_reason'];
            $historyDetails->create();

            /** @var \settings $settings */
            $settings = $this->loadData('settings');
            $settings->get('Facebook', 'type');
            $facebookLink = $settings->value;

            $settings->get('Twitter', 'type');
            $twitterLink = $settings->value;

            $keywords = array(
                'surl'                   => $this->surl,
                'url'                    => $this->furl,
                'prenom_e'               => $client->prenom,
                'link_compte_emprunteur' => $this->furl,
                'lien_fb'                => $facebookLink,
                'lien_tw'                => $twitterLink
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $keywords);
            $message->setTo($client->email);
            $mailer = $this->get('mailer');
            $mailer->send($message);
        }

        echo json_encode(['success' => true]);
    }

    public function _valid_rejete_etape7()
    {
        $this->autoFireView = false;

        /** @var \projects $project */
        $project = $this->loadData('projects');
        /** @var \projects_notes $projectRating */
        $projectRating = $this->loadData('projects_notes');
        /** @var \companies $company */
        $company = $this->loadData('companies');
        /** @var \clients $client */
        $client = $this->loadData('clients');

        if (
            false === isset($_POST['id_project'], $_POST['status'])
            || false === $project->get($_POST['id_project'], 'id_project')
            || false === $company->get($project->id_company, 'id_company')
            || false === $client->get($company->id_client_owner, 'id_client')
            || $_POST['status'] == 1 && (
                empty($_POST['structure_comite']) || $_POST['structure_comite'] > 10
                || empty($_POST['rentabilite_comite']) || $_POST['rentabilite_comite'] > 10
                || empty($_POST['tresorerie_comite']) || $_POST['tresorerie_comite'] > 10
                || empty($_POST['performance_fianciere_comite']) || $_POST['performance_fianciere_comite'] > 10
                || empty($_POST['individuel_comite']) || $_POST['individuel_comite'] > 10
                || empty($_POST['global_comite']) || $_POST['global_comite'] > 10
                || empty($_POST['marche_opere_comite']) || $_POST['marche_opere_comite'] > 10
                || empty($_POST['dirigeance_comite']) || $_POST['dirigeance_comite'] > 10
                || empty($_POST['indicateur_risque_dynamique_comite']) || $_POST['indicateur_risque_dynamique_comite'] > 10
                || empty($_POST['avis_comite']) || strlen($_POST['avis_comite']) < 50
            )
        ) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            return;
        }

        $update = $projectRating->get($_POST['id_project'], 'id_project');

        $projectRating->structure_comite                   = round(str_replace(',', '.', $_POST['structure_comite']), 1);
        $projectRating->rentabilite_comite                 = round(str_replace(',', '.', $_POST['rentabilite_comite']), 1);
        $projectRating->tresorerie_comite                  = round(str_replace(',', '.', $_POST['tresorerie_comite']), 1);
        $projectRating->performance_fianciere_comite       = round(str_replace(',', '.', $_POST['performance_fianciere_comite']), 1);
        $projectRating->individuel_comite                  = round(str_replace(',', '.', $_POST['individuel_comite']), 1);
        $projectRating->global_comite                      = round(str_replace(',', '.', $_POST['global_comite']), 1);
        $projectRating->marche_opere_comite                = round(str_replace(',', '.', $_POST['marche_opere_comite']), 1);
        $projectRating->dirigeance_comite                  = round(str_replace(',', '.', $_POST['dirigeance_comite']), 1);
        $projectRating->indicateur_risque_dynamique_comite = round(str_replace(',', '.', $_POST['indicateur_risque_dynamique_comite']), 1);
        $projectRating->note_comite                        = round($projectRating->performance_fianciere_comite * 0.2 + $projectRating->marche_opere_comite * 0.2 + $projectRating->dirigeance_comite * 0.2 + $projectRating->indicateur_risque_dynamique_comite * 0.4, 1);
        $projectRating->avis_comite                        = $_POST['avis_comite'];

        if ($update == true) {
            $projectRating->update();
        } else {
            $projectRating->id_project = $project->id_project;
            $projectRating->create();
        }

        if ($projectRating->note_comite >= 8.5 && $projectRating->note_comite <= 10) {
            $riskRating = 'A';
        } elseif ($projectRating->note_comite >= 7.5 && $projectRating->note_comite < 8.5) {
            $riskRating = 'B';
        } elseif ($projectRating->note_comite >= 6.5 && $projectRating->note_comite < 7.5) {
            $riskRating = 'C';
        } elseif ($projectRating->note_comite >= 5.5 && $projectRating->note_comite < 6.5) {
            $riskRating = 'D';
        } elseif ($projectRating->note_comite >= 4 && $projectRating->note_comite < 5.5) {
            $riskRating = 'E';
        } elseif ($projectRating->note_comite >= 2 && $projectRating->note_comite < 4) {
            $riskRating = 'G';
        } else {
            $riskRating = 'I';
        }

        $project->risk = $riskRating;
        $project->update();

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');

        if ($_POST['status'] == 1) {
            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->loadData('projects_status_history');

            $existingStatus  = [];
            $companyProjects = $project->select('id_company = ' . $project->id_company);

            foreach ($companyProjects as $companyProject) {
                $statusHistory = $projectStatusHistory->getHistoryDetails($companyProject['id_project']);
                foreach ($statusHistory as $status) {
                    $existingStatus[] = $status['status'];
                }
            }

            if (false === in_array(\projects_status::PREP_FUNDING, $existingStatus)) {
                $this->sendEmailBorrowerArea('ouverture-espace-emprunteur-plein', $client);
            }

            $projectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::PREP_FUNDING, $project);
        } elseif ($_POST['status'] == 2) {
            $projectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::COMITY_REJECTION, $project);

            /** @var \projects_status_history $projectStatusHistory */
            $projectStatusHistory = $this->loadData('projects_status_history');
            $projectStatusHistory->loadLastProjectHistory($project->id_project);

            /** @var \projects_status_history_details $historyDetails */
            $historyDetails                            = $this->loadData('projects_status_history_details');
            $historyDetails->id_project_status_history = $projectStatusHistory->id_project_status_history;
            $historyDetails->comity_rejection_reason   = $_POST['rejection_reason'];
            $historyDetails->create();

            /** @var \settings $settings */
            $settings = $this->loadData('settings');
            $settings->get('Facebook', 'type');
            $facebookLink = $settings->value;

            $settings->get('Twitter', 'type');
            $twitterLink = $settings->value;

            $keywords = array(
                'surl'                   => $this->surl,
                'url'                    => $this->furl,
                'prenom_e'               => $client->prenom,
                'link_compte_emprunteur' => $this->furl,
                'lien_fb'                => $facebookLink,
                'lien_tw'                => $twitterLink
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $keywords);
            $message->setTo($client->email);
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } elseif ($_POST['status'] == 4) {
            $projectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::ANALYSIS_REVIEW, $project);
        }

        if (false === empty($project->risk) && false === empty($project->period)) {
            try {
                $project->id_rate = $projectManager->getProjectRateRange($project);
                $project->update();
            } catch (\Exception $exception) {
                echo json_encode(['success' => false, 'error' => $exception->getMessage()]);
                return;
            }
        }

        echo json_encode(['success' => true]);
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
