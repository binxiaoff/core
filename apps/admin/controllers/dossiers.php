<?php

use Unilend\librairies\Altares;

class dossiersController extends bootstrap
{
    public $Command;

    /**
     * @var int Count project in searchDossiers
     */
    public $iCountProjects;

    /**
     * @var string for block risk note and comments
     */
    public $bReadonlyRiskNote;

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('dossiers');

        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        $this->projects_status = $this->loadData('projects_status');
        $this->projects        = $this->loadData('projects');

        $this->lProjects_status = $this->projects_status->select('', ' status ASC ');
        $this->aAnalysts        = $this->users->select('status = 1 AND id_user_type = 2');
        $this->aSalesPersons    = $this->users->select('status = 1 AND id_user_type = 3');

        $this->oUserAnalyst     = $this->loadData('users');
        $this->oUserSalesPerson = $this->loadData('users');

        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->fundingTimeValues = explode(',', $this->settings->value);

        if (isset($_POST['form_search_dossier'])) {
            if ($_POST['date1'] != '') {
                $d1    = explode('/', $_POST['date1']);
                $date1 = $d1[2] . '-' . $d1[1] . '-' . $d1[0];
            } else {
                $date1 = '';
            }

            if ($_POST['date2'] != '') {
                $d2    = explode('/', $_POST['date2']);
                $date2 = $d2[2] . '-' . $d2[1] . '-' . $d2[0];
            } else {
                $date2 = '';
            }
            $iNbStartPagination = (isset($_POST['nbLignePagination'])) ? (int) $_POST['nbLignePagination'] : 0;
            $this->nb_lignes    = (isset($this->nb_lignes)) ? (int) $this->nb_lignes : 100;
            $this->lProjects    = $this->projects->searchDossiers($date1, $date2, $_POST['montant'], $_POST['duree'], $_POST['status'], $_POST['analyste'], $_POST['siren'], $_POST['id'], $_POST['raison-sociale'], null, $_POST['commercial'], $iNbStartPagination, $this->nb_lignes);
        } elseif (isset($this->params[0])) {
            $this->lProjects = $this->projects->searchDossiers('', '', '', '', $this->params[0]);
        }
        $this->iCountProjects = (isset($this->lProjects) && is_array($this->lProjects)) ? array_shift($this->lProjects) : 0;
    }

    public function _edit()
    {
        $this->projects                        = $this->loadData('projects');
        $this->projects_notes                  = $this->loadData('projects_notes');
        $this->project_cgv                     = $this->loadData('project_cgv');
        $this->companies                       = $this->loadData('companies');
        $this->companies_actif_passif          = $this->loadData('companies_actif_passif');
        $this->company_balance                 = $this->loadData('company_balance');
        $this->company_balance_type            = $this->loadData('company_balance_type');
        $this->companies_bilans                = $this->loadData('companies_bilans');
        $this->clients                         = $this->loadData('clients');
        $this->clients_adresses                = $this->loadData('clients_adresses');
        $this->projects_comments               = $this->loadData('projects_comments');
        $this->projects_status                 = $this->loadData('projects_status');
        $this->current_projects_status         = $this->loadData('projects_status');
        $this->projects_status_history         = $this->loadData('projects_status_history');
        $this->current_projects_status_history = $this->loadData('projects_status_history');
        $this->projects_last_status_history    = $this->loadData('projects_last_status_history');
        $this->loans                           = $this->loadData('loans');
        $this->projects_pouvoir                = $this->loadData('projects_pouvoir');
        $this->lenders_accounts                = $this->loadData('lenders_accounts');
        $this->echeanciers                     = $this->loadData('echeanciers');
        $this->notifications                   = $this->loadData('notifications');
        $this->clients_gestion_mails_notif     = $this->loadData('clients_gestion_mails_notif');
        $this->clients_gestion_notifications   = $this->loadData('clients_gestion_notifications');
        $this->prescripteurs                   = $this->loadData('prescripteurs');
        $this->clients_prescripteurs           = $this->loadData('clients');
        $this->companies_prescripteurs         = $this->loadData('companies');
        $this->settings                        = $this->loadData('settings');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager                       = $this->get('unilend.service.project_manager');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->settings->get('Durée des prêts autorisées', 'type');
            $this->dureePossible = explode(',', $this->settings->value);

            if (false === in_array($this->projects->period, array(0, 1000000)) && false === in_array($this->projects->period, $this->dureePossible)) {
                array_push($this->dureePossible, $this->projects->period);
                sort($this->dureePossible);
            }

            $this->settings->get('Liste deroulante secteurs', 'type');
            $this->lSecteurs = explode(';', $this->settings->value);

            $this->settings->get('Cabinet de recouvrement', 'type');
            $this->cab = $this->settings->value;

            $this->settings->get('Heure debut periode funding', 'type');
            $this->debutFunding = $this->settings->value;

            $this->settings->get('Heure fin periode funding', 'type');
            $this->finFunding = $this->settings->value;

            $this->settings->get('TVA', 'type');
            $this->fVATRate = $this->settings->value;

            $debutFunding        = explode(':', $this->debutFunding);
            $this->HdebutFunding = $debutFunding[0];

            $finFunding        = explode(':', $this->finFunding);
            $this->HfinFunding = $finFunding[0];

            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');
            $this->clients_adresses->get($this->companies->id_client_owner, 'id_client');
            $this->projects_notes->get($this->projects->id_project, 'id_project');
            $this->project_cgv->get($this->projects->id_project, 'id_project');

            $this->projects_last_status_history->get($this->projects->id_project, 'id_project');
            $this->current_projects_status_history->get($this->projects_last_status_history->id_project_status_history, 'id_project_status_history');
            $this->projects_status->get($this->current_projects_status_history->id_project_status);
            $this->current_projects_status->getLastStatut($this->projects->id_project);

            $this->bHasAdvisor       = false;
            $this->bReadonlyRiskNote = $this->current_projects_status->status >= \projects_status::EN_FUNDING;

            if ($this->projects->id_prescripteur > 0 && $this->prescripteurs->get($this->projects->id_prescripteur, 'id_prescripteur')) {
                $this->clients_prescripteurs->get($this->prescripteurs->id_client, 'id_client');
                $this->companies_prescripteurs->get($this->prescripteurs->id_entite, 'id_company');
                $this->bHasAdvisor = true;
            }

            if ($this->companies->status_adresse_correspondance == 1) {
                $this->adresse = $this->companies->adresse1;
                $this->city    = $this->companies->city;
                $this->zip     = $this->companies->zip;
                $this->phone   = $this->companies->phone;
            } else {
                $this->adresse = $this->clients_adresses->adresse1;
                $this->city    = $this->clients_adresses->ville;
                $this->zip     = $this->clients_adresses->cp;
                $this->phone   = $this->clients_adresses->telephone;
            }

            $this->aAnnualAccountsDates = array();
            $this->aAnalysts            = $this->users->select('status = 1 AND id_user_type = 2');
            $this->aSalesPersons        = $this->users->select('status = 1 AND id_user_type = 3');
            $this->aEmails              = $this->projects_status_history->select('content != "" AND id_project = ' . $this->projects->id_project, 'id_project_status_history DESC');
            $this->lProjects_comments   = $this->projects_comments->select('id_project = ' . $this->projects->id_project, 'added DESC');
            $this->lProjects_status     = $this->projects_status->getPossibleStatus($this->projects->id_project, $this->projects_status_history);
            $this->aAllAnnualAccounts   = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'cloture_exercice_fiscal DESC');

            if (empty($this->projects->id_dernier_bilan)) {
                $this->lbilans = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'cloture_exercice_fiscal DESC', 0, 3);

                if (false === empty($this->lbilans)) {
                    $this->projects->id_dernier_bilan = $this->lbilans[0]['id_bilan'];
                    $this->projects->update();
                }
            } else {
                $this->lbilans = $this->companies_bilans->select('id_company = ' . $this->companies->id_company . ' AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $this->projects->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);
            }

            if (empty($this->lbilans)) {
                $this->lCompanies_actif_passif = array();
                $this->aBalanceSheets          = array();
            } else {
                $aAnnualAccountsIds            = array_column($this->lbilans, 'id_bilan');
                $sAnnualAccountsIds            = implode(', ', $aAnnualAccountsIds);
                $this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_bilan IN (' . $sAnnualAccountsIds . ')', 'FIELD(id_bilan, ' . $sAnnualAccountsIds . ') ASC');
                $this->aBalanceSheets          = $this->company_balance->getBalanceSheetsByAnnualAccount($aAnnualAccountsIds);

                if (count($this->lCompanies_actif_passif) < count($this->lbilans)) {
                    foreach (array_diff(array_column($this->lbilans, 'id_bilan'), array_column($this->lCompanies_actif_passif, 'id_bilan')) as $iAnnualAccountsId) {
                        $oAssetsDebts                                     = new \companies_actif_passif($this->bdd);
                        $oAssetsDebts->id_bilan                           = $iAnnualAccountsId;
                        $oAssetsDebts->immobilisations_corporelles        = 0;
                        $oAssetsDebts->immobilisations_incorporelles      = 0;
                        $oAssetsDebts->immobilisations_financieres        = 0;
                        $oAssetsDebts->stocks                             = 0;
                        $oAssetsDebts->creances_clients                   = 0;
                        $oAssetsDebts->disponibilites                     = 0;
                        $oAssetsDebts->valeurs_mobilieres_de_placement    = 0;
                        $oAssetsDebts->capitaux_propres                   = 0;
                        $oAssetsDebts->provisions_pour_risques_et_charges = 0;
                        $oAssetsDebts->amortissement_sur_immo             = 0;
                        $oAssetsDebts->dettes_financieres                 = 0;
                        $oAssetsDebts->dettes_fournisseurs                = 0;
                        $oAssetsDebts->autres_dettes                      = 0;
                        $oAssetsDebts->create();
                    }
                    $this->lCompanies_actif_passif = $this->companies_actif_passif->select('id_bilan IN (' . $sAnnualAccountsIds . ')', 'FIELD(id_bilan, ' . $sAnnualAccountsIds . ') ASC');
                }

                foreach ($this->lbilans as $aAnnualAccounts) {
                    $oEndDate   = new \DateTime($aAnnualAccounts['cloture_exercice_fiscal']);
                    $oStartDate = new \DateTime($aAnnualAccounts['cloture_exercice_fiscal']);
                    $oStartDate->sub(new \DateInterval('P' . $aAnnualAccounts['duree_exercice_fiscal'] . 'M'))->add(new \DateInterval('P1D'));
                    $this->aAnnualAccountsDates[$aAnnualAccounts['id_bilan']] = array(
                        'start' => $oStartDate,
                        'end'   => $oEndDate
                    );
                }
            }

            $this->bCanEditStatus = false;
            if ($this->users->get($_SESSION['user']['id_user'], 'id_user')) {
                $this->loadData('users_types');
                if (in_array($this->users->id_user_type, array(\users_types::TYPE_ADMIN, \users_types::TYPE_ANALYSTE, \users_types::TYPE_COMMERCIAL))) {
                    $this->bCanEditStatus = true;
                }
            }

            $this->attachment_type  = $this->loadData('attachment_type');
            $this->aAttachmentTypes = $this->attachment_type->getAllTypesForProjects($this->language);
            $this->aAttachments     = $this->projects->getAttachments();

            $this->completude_wording = array();
            $aAttachmentTypes         = $this->attachment_type->getAllTypesForProjects($this->language, false);
            $oTextes                  = $this->loadData('textes');
            $aTranslations            = $oTextes->selectFront('projet', $this->language);

            foreach ($this->attachment_type->changeLabelWithDynamicContent($aAttachmentTypes) as $aAttachment) {
                if ($aAttachment['id'] == \attachment_type::PHOTOS_ACTIVITE) {
                    $this->completude_wording[] = $aAttachment['label'] . ' ' . $aTranslations['completude-photos'];
                } else {
                    $this->completude_wording[] = $aAttachment['label'];
                }
            }
            $this->completude_wording[] = $aTranslations['completude-charge-affaires'];

            if (isset($_POST['problematic_status']) && $this->current_projects_status->status != $_POST['problematic_status']) {
                $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], $_POST['problematic_status'], $this->projects);

                $this->updateProblematicStatus($_POST['problematic_status']);
            }

            if ($this->projects_status->status == projects_status::PREP_FUNDING) {
                $fPredictAmountAutoBid = $this->get('unilend.service.autobid_settings_manager')->predictAmount($this->projects->risk, $this->projects->period);
                $this->fPredictAutoBid = round(($fPredictAmountAutoBid / $this->projects->amount) * 100, 1);
            }

            if (isset($_POST['last_annual_accounts'])) {
                $this->projects->id_dernier_bilan = $_POST['last_annual_accounts'];
                $this->projects->update();

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            } elseif (isset($_POST['change_annual_accounts_info']) && $this->companies_bilans->get($_POST['id_annual_accounts'])) {
                $this->companies_bilans->cloture_exercice_fiscal = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['cloture_exercice_fiscal'])));
                $this->companies_bilans->duree_exercice_fiscal   = (int) $_POST['duree_exercice_fiscal'];
                $this->companies_bilans->update();

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            } elseif (isset($_POST['add_annual_accounts'])) {
                $aLastAnnualAccounts                                 = current($this->aAllAnnualAccounts);
                $oClosingDate = new \DateTime($aLastAnnualAccounts['cloture_exercice_fiscal']);
                $this->companies_bilans->id_company                  = $this->projects->id_company;
                $this->companies_bilans->cloture_exercice_fiscal     = $oClosingDate->add(new \DateInterval('P12M'))->format('Y-m-d');
                $this->companies_bilans->duree_exercice_fiscal       = 12;
                $this->companies_bilans->ca                          = 0;
                $this->companies_bilans->resultat_brute_exploitation = 0;
                $this->companies_bilans->resultat_exploitation       = 0;
                $this->companies_bilans->investissements             = 0;
                $this->companies_bilans->create();

                $this->companies_actif_passif->id_bilan = $this->companies_bilans->id_bilan;
                $this->companies_actif_passif->create();

                $this->company_balance->id_bilan = $this->companies_bilans->id_bilan;
                $this->company_balance->create();

                $this->projects->id_dernier_bilan = $this->companies_bilans->id_bilan;
                $this->projects->update();

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            } elseif (isset($this->params[1]) && $this->params[1] == 'altares') {
                if (false === empty($this->companies->siren)) {
                    $this->loadData('companies_actif_passif'); // Used in order to generate CRUD
                    $this->loadData('companies_bilans'); // Used in order to generate CRUD
                    $this->loadData('company_balance'); // Used in order to generate CRUD
                    $this->loadData('company_balance_type'); // Used in order to generate CRUD
                    $this->loadData('company_rating'); // Used in order to generate CRUD
                    $this->loadData('company_rating_history'); // Used in order to generate CRUD

                    $oAltares = new Altares();
                    $oResult  = $oAltares->getEligibility($this->companies->siren);

                    if ($oResult->exception == '' && isset($oResult->myInfo) && is_object($oResult->myInfo)) {
                        if (false === empty($oResult->myInfo->codeRetour)) {
                            $this->projects->retour_altares = $oResult->myInfo->codeRetour;
                            $this->projects->update();
                        }

                        $oAltares->setCompanyData($this->companies, $oResult->myInfo);

                        $oCompanyCreationDate = new \DateTime($this->companies->date_creation);
                        $oInterval            = $oCompanyCreationDate->diff(new \DateTime());

                        if ($oResult->myInfo->eligibility === 'Non' || $oInterval->days < \projects::MINIMUM_CREATION_DAYS_PROSPECT) {
                            $_SESSION['freeow']['title']   = 'Données Altares';
                            $_SESSION['freeow']['message'] = 'Société non éligible';
                        } else {
                            $oAltares->setProjectData($this->projects, $oResult->myInfo);
                            $oAltares->setCompanyBalance($this->companies);

                            $_SESSION['freeow']['title']   = 'Données Altares';
                            $_SESSION['freeow']['message'] = 'Données Altares récupéré !';
                        }
                    } else {
                        $_SESSION['freeow']['title']   = 'Données Altares';
                        $_SESSION['freeow']['message'] = 'Données Altares erreur !';
                    }
                } else {
                    $_SESSION['freeow']['title']   = 'Données Altares';
                    $_SESSION['freeow']['message'] = 'Numéro de SIREN non renseigné';
                }

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            }


            if (isset($_POST['rejection_reason'])) {
                /** @var \projects_status_history $oProjectStatusHistory */
                $oProjectStatusHistory = $this->loadData('projects_status_history');
                $oProjectStatusHistory->loadLastProjectHistory($this->projects->id_project);

                /** @var \projects_status_history_details $oProjectsStatusHistoryDetails */
                $oProjectsStatusHistoryDetails = $this->loadData('projects_status_history_details');

                $bCreate = (false === $oProjectsStatusHistoryDetails->get($oProjectStatusHistory->id_project_status_history, 'id_project_status_history'));

                if ($oProjectStatusHistory->loadLastProjectHistory($this->projects->id_project)) {
                    switch ($this->projects_status->status) {
                        case \projects_status::REJETE:
                            $oProjectsStatusHistoryDetails->commercial_rejection_reason = $_POST['rejection_reason'];
                            break;
                        case \projects_status::REJET_ANALYSTE:
                            $oProjectsStatusHistoryDetails->analyst_rejection_reason = $_POST['rejection_reason'];
                            break;
                        case \projects_status::REJET_COMITE:
                            $oProjectsStatusHistoryDetails->comity_rejection_reason = $_POST['rejection_reason'];
                            break;
                    }

                    if ($bCreate) {
                        $oProjectsStatusHistoryDetails->id_project_status_history = $oProjectStatusHistory->id_project_status_history;
                        $oProjectsStatusHistoryDetails->create();
                    } else {
                        $oProjectsStatusHistoryDetails->update();
                    }
                }
            }

            if (isset($_POST['send_form_dossier_resume'])) {
                // On check avant la validation que la date de publication & date de retrait sont OK sinon on bloque(KLE)
                /* La date de publication doit être au minimum dans 5min et la date de retrait à plus de 5min (pas de contrainte) */
                $dates_valide = false;
                if (false === empty($_POST['date_publication'])) {
                    $oPublicationDate                = \DateTime::createFromFormat('d/m/Y H:i', $_POST['date_publication'] . ' ' . $_POST['date_publication_heure'] . ':' . $_POST['date_publication_minute']);
                    $oEndOfPublicationDate           = \DateTime::createFromFormat('d/m/Y H:i', $_POST['date_retrait'] . ' ' . $_POST['date_retrait_heure'] . ':' . $_POST['date_retrait_minute']);
                    $oPublicationLimitationDate      = new \DateTime('NOW + 5 minutes');
                    $oEndOfPublicationLimitationDate = new \DateTime('NOW + 1 hour');

                    if ($oPublicationDate > $oPublicationLimitationDate && $oEndOfPublicationDate > $oEndOfPublicationLimitationDate) {
                        $dates_valide = true;
                    }
                }

                if (false === $dates_valide && in_array(\projects_status::A_FUNDER, array($_POST['status'], $this->current_projects_status->status))) {
                    $this->retour_dates_valides = 'La date de publication du dossier doit être au minimum dans 5min et la date de retrait dans plus d\'1h';
                } else {
                    $_SESSION['freeow']['title']   = 'Sauvegarde du résumé';
                    $_SESSION['freeow']['message'] = '';

                    $serialize = serialize(array('id_project' => $this->projects->id_project, 'post' => $_POST));
                    $this->users_history->histo(10, 'dossier edit Resume & actions', $_SESSION['user']['id_user'], $serialize);

                    if (isset($_FILES['photo_projet']) && $_FILES['photo_projet']['name'] != '') {
                        $this->upload->setUploadDir($this->path, 'public/default/images/dyn/projets/source/');
                        $this->upload->setExtValide(array('jpeg', 'JPEG', 'jpg', 'JPG'));

                        $oImagick = new \Imagick($_FILES['photo_projet']['tmp_name']);
                        $imageConfig = $this->getParameter('image_resize');
                        if (
                            $oImagick->getImageWidth() > $imageConfig['projets']['width']
                            || $oImagick->getImageHeight() > $imageConfig['projets']['height']
                        ) {
                            $_SESSION['freeow']['message'] .= 'Erreur upload photo : taille max dépassée (' . $imageConfig['projets']['width'] . 'x' . $imageConfig['projets']['height'] . ')<br>';
                        } elseif ($this->upload->doUpload('photo_projet', '', true)) {
                            // Delete previous image of the name was different from the new one
                            if (! empty($this->projects->photo_projet) && $this->projects->photo_projet != $this->upload->getName()) {
                                @unlink($this->path . 'public/default/images/dyn/projets/source/' . $this->projects->photo_projet);
                            }
                            $this->projects->photo_projet = $this->upload->getName();
                        } else {
                            $_SESSION['freeow']['message'] .= 'Erreur upload photo : ' . $this->upload->getErrorType() . '<br>';
                        }
                    }

                    if (isset($_FILES['upload_pouvoir']) && $_FILES['upload_pouvoir']['name'] != '') {
                        $this->upload->setUploadDir($this->path, 'protected/pdf/pouvoir/');
                        if ($this->upload->doUpload('upload_pouvoir')) {
                            if ($this->projects_pouvoir->name != '') {
                                @unlink($this->path . 'protected/pdf/pouvoir/' . $this->projects->photo_projet);
                            }
                            $this->projects_pouvoir->name          = $this->upload->getName();
                            $this->projects_pouvoir->id_project    = $this->projects->id_project;
                            $this->projects_pouvoir->id_universign = 'no_universign';
                            $this->projects_pouvoir->url_pdf       = '/pdf/pouvoir/' . $this->clients->hash . '/' . $this->projects->id_project;
                            $this->projects_pouvoir->status        = 1;
                            $this->projects_pouvoir->create();
                        } else {
                            $_SESSION['freeow']['message'] .= 'Erreur upload pouvoir : ' . $this->upload->getErrorType() . '<br>';
                        }
                    }

                    if (
                        $_POST['commercial'] > 0
                        && $_POST['commercial'] != $this->projects->id_commercial
                        && $this->current_projects_status->status < \projects_status::EN_ATTENTE_PIECES
                    ) {
                        $_POST['status'] = \projects_status::EN_ATTENTE_PIECES;
                    }

                    if (
                        $_POST['analyste'] > 0
                        && $_POST['analyste'] != $this->projects->id_analyste
                        && $this->current_projects_status->status < \projects_status::REVUE_ANALYSTE
                    ) {
                        $_POST['status'] = \projects_status::REVUE_ANALYSTE;
                    }

                    $this->projects->title           = $_POST['title'];
                    $this->projects->title_bo        = $_POST['title_bo'];
                    $this->projects->period          = $_POST['duree'];
                    $this->projects->nature_project  = $_POST['nature_project'];
                    $this->projects->amount          = str_replace(' ', '', str_replace(',', '.', $_POST['montant']));
                    $this->projects->target_rate     = '-';
                    $this->projects->id_analyste     = $_POST['analyste'];
                    $this->projects->id_commercial   = $_POST['commercial'];
                    $this->projects->display         = $_POST['display_project'];
                    $this->projects->id_project_need = $_POST['need'];

                    if ($this->current_projects_status->status >= \projects_status::PREP_FUNDING) {
                        $this->projects->risk = $_POST['risk'];
                    }

                    if ($this->current_projects_status->status <= \projects_status::A_FUNDER) {
                        $this->projects->slug = $this->ficelle->generateSlug($this->projects->title . '-' . $this->projects->id_project);
                    }

                    $this->projects->update();

                    if ($this->current_projects_status->status >= \projects_status::PREP_FUNDING) {
                        if (isset($_POST['date_publication']) && ! empty($_POST['date_publication'])) {
                            $this->projects->date_publication      = $this->dates->formatDateFrToMysql($_POST['date_publication']);
                            $this->projects->date_publication_full = $this->projects->date_publication . ' ' . $_POST['date_publication_heure'] . ':' . $_POST['date_publication_minute'] . ':0';
                        }
                        if (isset($_POST['date_retrait']) && ! empty($_POST['date_retrait'])) {
                            $this->projects->date_retrait      = $this->dates->formatDateFrToMysql($_POST['date_retrait']);
                            $this->projects->date_retrait_full = $this->projects->date_retrait . ' ' . $_POST['date_retrait_heure'] . ':' . $_POST['date_retrait_minute'] . ':0';
                        }
                    }

                    if ($this->current_projects_status->status != $_POST['status']) {
                        if ($_POST['status'] == \projects_status::PREP_FUNDING) {
                            $aProjects       = $this->projects->select('id_company = ' . $this->projects->id_company);
                            $aExistingStatus = array();

                            foreach ($aProjects as $aProject) {
                                $aStatusHistory = $this->projects_status_history->getHistoryDetails($aProject['id_project']);

                                foreach ($aStatusHistory as $aStatus) {
                                    $aExistingStatus[] = $aStatus['status'];
                                }
                            }

                            $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::PREP_FUNDING, $this->projects);

                            if (false === in_array(\projects_status::PREP_FUNDING, $aExistingStatus)) {
                                $this->sendEmailBorrowerArea('ouverture-espace-emprunteur-plein');
                            }
                        } elseif (in_array($_POST['status'], array(\projects_status::A_FUNDER, \projects_status::EN_FUNDING, \projects_status::FUNDE))) {
                            $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], $_POST['status'], $this->projects);

                            $companies        = $this->loadData('companies');
                            $clients          = $this->loadData('clients');
                            $clients_adresses = $this->loadData('clients_adresses');

                            $companies->get($this->projects->id_company, 'id_company');
                            $clients->get($companies->id_client_owner, 'id_client');
                            $clients_adresses->get($companies->id_client_owner, 'id_client');

                            $mess = '<ul>';

                            if ($this->projects->title == '') {
                                $mess .= '<li>Titre projet</li>';
                            }
                            if ($this->projects->title_bo == '') {
                                $mess .= '<li>Titre projet BO</li>';
                            }
                            if ($this->projects->period == '0') {
                                $mess .= '<li>Periode projet</li>';
                            }
                            if ($this->projects->amount == '0') {
                                $mess .= '<li>Montant projet</li>';
                            }
                            if ($companies->name == '') {
                                $mess .= '<li>Nom entreprise</li>';
                            }
                            if ($companies->forme == '') {
                                $mess .= '<li>Forme juridique</li>';
                            }
                            if ($companies->siren == '') {
                                $mess .= '<li>SIREN entreprise</li>';
                            }
                            if ($companies->siret == '') {
                                $mess .= '<li>SIRET entreprise</li>';
                            }
                            if ($companies->iban == '') {
                                $mess .= '<li>IBAN entreprise</li>';
                            }
                            if ($companies->bic == '') {
                                $mess .= '<li>BIC entreprise</li>';
                            }
                            if ($companies->tribunal_com == '') {
                                $mess .= '<li>Tribunal de commerce entreprise</li>';
                            }
                            if ($companies->capital == '0') {
                                $mess .= '<li>Capital entreprise</li>';
                            }
                            if ($companies->date_creation == '0000-00-00') {
                                $mess .= '<li>Date creation entreprise</li>';
                            }
                            if ($companies->sector == 0) {
                                $mess .= '<li>Secteur entreprise</li>';
                            }
                            if ($clients->nom == '') {
                                $mess .= '<li>Nom emprunteur</li>';
                            }
                            if ($clients->prenom == '') {
                                $mess .= '<li>Prenom emprunteur</li>';
                            }
                            if ($clients->fonction == '') {
                                $mess .= '<li>Fonction emprunteur</li>';
                            }
                            if ($clients->telephone == '') {
                                $mess .= '<li>Telephone emprunteur</li>';
                            }
                            if ($clients->email == '') {
                                $mess .= '<li>Email emprunteur</li>';
                            }
                            if ($clients_adresses->adresse1 == '') {
                                $mess .= '<li>Adresse emprunteur</li>';
                            }
                            if ($clients_adresses->cp == '') {
                                $mess .= '<li>CP emprunteur</li>';
                            }
                            if ($clients_adresses->ville == '') {
                                $mess .= '<li>Ville emprunteur</li>';
                            }

                            $mess .= '</ul>';

                            if (strlen($mess) > 9) {
                                $this->settings->get('DebugAlertesBusiness', 'type');
                                $to = $this->settings->value;
                                $subject = '[Rappel] Donnees projet manquantes';
                                $message = '
                                <html>
                                <head>
                                  <title>[Rappel] Donnees projet manquantes</title>
                                </head>
                                <body>
                                    <p>Un projet qui vient d\'etre publie ne dispose pas de toutes les donnees necessaires</p>
                                    <p>Listes des informations manquantes sur le projet ' . $this->projects->id_project . ' : </p>
                                    ' . $mess . '
                                </body>
                                </html>';

                                $headers = 'MIME-Version: 1.0' . "\r\n";
                                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                                $headers .= 'From: Unilend <equipeit@unilend.fr>' . "\r\n";
                                mail($to, $subject, $message, $headers);
                            }
                        } else {
                            $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], $_POST['status'], $this->projects);
                        }
                    }

                    $this->companies->siren           = $_POST['siren'];
                    $this->companies->siret           = $_POST['siret'];
                    $this->companies->name            = $_POST['societe'];
                    $this->companies->sector          = $_POST['sector'];
                    $this->companies->id_client_owner = $_POST['id_client'];
                    $this->companies->code_naf        = $_POST['code_naf'];
                    $this->companies->libelle_naf     = $_POST['libelle_naf'];
                    $this->companies->tribunal_com    = $_POST['tribunal_com'];
                    $this->companies->activite        = $_POST['activite'];
                    $this->companies->lieu_exploi     = $_POST['lieu_exploi'];

                    if ($this->companies->status_adresse_correspondance == 1) {
                        $this->companies->adresse1 = $_POST['adresse'];
                        $this->companies->city     = $_POST['city'];
                        $this->companies->zip      = $_POST['zip'];
                        $this->companies->phone    = $_POST['phone'];
                    } else {
                        $this->clients_adresses->adresse1  = $_POST['adresse'];
                        $this->clients_adresses->ville     = $_POST['city'];
                        $this->clients_adresses->cp        = $_POST['zip'];
                        $this->clients_adresses->telephone = $_POST['phone'];
                    }

                    $this->clients->get($this->companies->id_client_owner, 'id_client');
                    $this->clients->prenom = $this->ficelle->majNom($_POST['prenom']);
                    $this->clients->nom    = $this->ficelle->majNom($_POST['nom']);

                    $this->projects->update();
                    $this->companies->update();
                    $this->clients->update();
                    $this->clients_adresses->update();

                    if (isset($_POST['pret_refuse']) && $_POST['pret_refuse'] == 1) {
                        $loans         = $this->loadData('loans');
                        $transactions  = $this->loadData('transactions');
                        $lenders       = $this->loadData('lenders_accounts');
                        $clients       = $this->loadData('clients');
                        $wallets_lines = $this->loadData('wallets_lines');
                        $companies     = $this->loadData('companies');
                        $projects      = $this->loadData('projects');
                        $echeanciers   = $this->loadData('echeanciers');

                        $this->settings->get('Facebook', 'type');
                        $lien_fb = $this->settings->value;

                        $this->settings->get('Twitter', 'type');
                        $lien_tw = $this->settings->value;

                        $nb_loans = $loans->getNbPreteurs($this->projects->id_project);

                        $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::PRET_REFUSE, $this->projects);

                        $lesloans = $loans->select('id_project = ' . $this->projects->id_project);
                        $companies->get($this->projects->id_company, 'id_company');

                        //on supp l'écheancier du projet pour ne pas avoir de doublon d'affichage sur le front (BT 18600)
                        $echeanciers->delete($this->projects->id_project, 'id_project');

                        foreach ($lesloans as $l) {
                            // On regarde si on a pas deja un remb pour ce bid
                            if ($transactions->get($l['id_loan'], 'id_loan_remb') == false) {
                                // On recup lender
                                $projects->get($l['id_project'], 'id_project');
                                // On recup lender
                                $lenders->get($l['id_lender'], 'id_lender_account');
                                // on recup les infos du lender
                                $clients->get($lenders->id_client_owner, 'id_client');

                                // On change le satut des loans du projet refusé
                                $loans->get($l['id_loan'], 'id_loan');
                                $loans->status = 1;
                                $loans->update();

                                // On redonne l'argent aux preteurs
                                // On enregistre la transaction
                                $transactions->id_client        = $lenders->id_client_owner;
                                $transactions->montant          = $l['amount'];
                                $transactions->id_langue        = 'fr';
                                $transactions->id_loan_remb     = $l['id_loan'];
                                $transactions->date_transaction = date('Y-m-d H:i:s');
                                $transactions->status           = '1';
                                $transactions->etat             = '1';
                                $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                $transactions->type_transaction = 2;
                                $transactions->transaction      = 2; // transaction virtuelle
                                $transactions->id_transaction   = $transactions->create();

                                // on enregistre la transaction dans son wallet
                                $wallets_lines->id_lender                = $l['id_lender'];
                                $wallets_lines->type_financial_operation = 20;
                                $wallets_lines->id_transaction           = $transactions->id_transaction;
                                $wallets_lines->status                   = 1;
                                $wallets_lines->type                     = 2;
                                $wallets_lines->amount                   = $l['amount'];
                                $wallets_lines->id_wallet_line           = $wallets_lines->create();

                                //**************************************//
                                //*** ENVOI DU MAIL FUNDE EMPRUNTEUR ***//
                                //**************************************//

                                $varMail = array(
                                    'surl'              => $this->surl,
                                    'url'               => $this->furl,
                                    'prenom_p'          => $clients->prenom,
                                    'valeur_bid'        => $this->ficelle->formatNumber($l['amount'] / 100, 0),
                                    'nom_entreprise'    => $companies->name,
                                    'nb_preteurMoinsUn' => ($nb_loans - 1),
                                    'motif_virement'    => $this->clients->getLenderPattern($this->clients->id_client),
                                    'lien_fb'           => $lien_fb,
                                    'lien_tw'           => $lien_tw
                                );

                                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-pret-refuse', $varMail);
                                $message->setTo($clients->email);
                                $mailer = $this->get('mailer');
                                $mailer->send($message);
                            }
                        }
                    }

                    $_SESSION['freeow']['message'] .= 'Modifications enregistrées avec suucès';

                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                    die;
                }
            }

            // Modification de la date de retrait
            if (isset($_POST['send_form_date_retrait'])) {
                $form_ok = true;

                if (! isset($_POST['date_de_retrait'])) {
                    $form_ok = false;
                }
                if (! isset($_POST['date_retrait_heure'])) {
                    $form_ok = false;
                } elseif ($_POST['date_retrait_heure'] < 0) {
                    $form_ok = false;
                }

                if (! isset($_POST['date_retrait_minute'])) {
                    $form_ok = false;
                } elseif ($_POST['date_retrait_minute'] < 0) {
                    $form_ok = false;
                }

                if ($this->current_projects_status->status > \projects_status::EN_FUNDING) {
                    $form_ok = false;
                }

                if ($form_ok == true) {
                    $date = explode('/', $_POST['date_de_retrait']);
                    $date = $date[2] . '-' . $date[1] . '-' . $date[0];

                    $dateComplete = $date . ' ' . $_POST['date_retrait_heure'] . ':' . $_POST['date_retrait_minute'] . ':00';
                    // on check si la date est superieur a la date actuelle
                    if (strtotime($dateComplete) > time()) {
                        $this->projects->date_retrait_full = $dateComplete;
                        $this->projects->date_retrait      = $date;
                        $this->projects->update();
                    }
                }
            }

            /** @var \project_need $oProjectNeed */
            $oProjectNeed = $this->loadData('project_need');
            $this->aNeeds = $oProjectNeed->getTree();

            if (in_array($this->projects_status->status, array(\projects_status::REJETE, \projects_status::REJET_ANALYSTE, \projects_status::REJET_COMITE))) {
                /** @var \projects_status_history_details $oProjectsStatusHistoryDetails */
                $oProjectsStatusHistoryDetails = $this->loadData('projects_status_history_details');

                /** @var \project_rejection_reason $oRejectionReason */
                $oRejectionReason = $this->loadData('project_rejection_reason');

                /** @var \projects_last_status_history $oProjectsLastStatusHistory */
                $oProjectsLastStatusHistory = $this->loadData('projects_last_status_history');
                $oProjectsLastStatusHistory->get($this->projects->id_project, 'id_project');

                $this->sRejectionReason = '';
                if (
                    $oProjectsStatusHistoryDetails->get($oProjectsLastStatusHistory->id_project_status_history, 'id_project_status_history')
                    && (
                        $oProjectsStatusHistoryDetails->commercial_rejection_reason > 0 && $oRejectionReason->get($oProjectsStatusHistoryDetails->commercial_rejection_reason)
                        || $oProjectsStatusHistoryDetails->comity_rejection_reason > 0 && $oRejectionReason->get($oProjectsStatusHistoryDetails->comity_rejection_reason)
                        || $oProjectsStatusHistoryDetails->analyst_rejection_reason > 0 && $oRejectionReason->get($oProjectsStatusHistoryDetails->analyst_rejection_reason)
                    )
                ) {
                    $this->sRejectionReason = $oRejectionReason->label;
                }
            }

            $this->aCompanyProjects      = $this->companies->getProjectsBySIREN();
            $this->iCompanyProjectsCount = count($this->aCompanyProjects);
            $this->fCompanyOwedCapital   = $this->companies->getOwedCapitalBySIREN();
            $this->bIsProblematicCompany = $this->companies->countProblemsBySIREN() > 0;

            $this->aRatings = array();
            if (false === empty($this->projects->id_company_rating_history)) {
                /** @var company_rating $oCompanyRating */
                $oCompanyRating = $this->loadData('company_rating');
                $this->aRatings = $oCompanyRating->getHistoryRatingsByType($this->projects->id_company_rating_history);

                if (
                    (false === isset($this->aRatings['xerfi']) || false === isset($this->aRatings['xerfi_unilend']))
                    && false === empty($this->companies->code_naf)
                ) {
                    /** @var xerfi $oXerfi */
                    $oXerfi = $this->loadData('xerfi');

                    if (false === $oXerfi->get($this->companies->code_naf)) {
                        $sXerfiScore   = 'N/A';
                        $sXerfiUnilend = 'PAS DE DONNEES';
                    } elseif ('' === $oXerfi->score) {
                        $sXerfiScore   = 'N/A';
                        $sXerfiUnilend = $oXerfi->unilend_rating;
                    } else {
                        $sXerfiScore   = $oXerfi->score;
                        $sXerfiUnilend = $oXerfi->unilend_rating;
                    }

                    if (false === isset($this->aRatings['xerfi'])) {
                        $oCompanyRating->id_company_rating_history = $this->projects->id_company_rating_history;
                        $oCompanyRating->type                      = 'xerfi';
                        $oCompanyRating->value                     = $sXerfiScore;
                        $oCompanyRating->create();
                    }

                    if (false === isset($this->aRatings['xerfi_unilend'])) {
                        $oCompanyRating->id_company_rating_history = $this->projects->id_company_rating_history;
                        $oCompanyRating->type                      = 'xerfi_unilend';
                        $oCompanyRating->value                     = $sXerfiUnilend;
                        $oCompanyRating->create();
                    }

                    $this->aRatings = $oCompanyRating->getHistoryRatingsByType($this->projects->id_company_rating_history);
                }
            }

            $this->recup_info_remboursement_anticipe($this->projects->id_project);
        } else {
            header('Location: ' . $this->lurl . '/dossiers');
            die;
        }
    }

    protected function sumBalances(array $aBalances, $aBalanceSheet)
    {
        $fTotal = 0.0;
        foreach ($aBalances as $sBalance) {
            $fTotal += $aBalanceSheet[$sBalance];
        }
        return $fTotal;
    }

    private function updateProblematicStatus($iStatus)
    {
        $this->projects_status_history->loadLastProjectHistory($this->projects->id_project);

        /** @var \projects_status_history_details $projectStatusHistoryDetails */
        $projectStatusHistoryDetails                            = $this->loadData('projects_status_history_details');
        $projectStatusHistoryDetails->id_project_status_history = $this->projects_status_history->id_project_status_history;
        $projectStatusHistoryDetails->date                      = isset($_POST['decision_date']) ? date('Y-m-d', strtotime(str_replace('/', '-', $_POST['decision_date']))) : null;
        $projectStatusHistoryDetails->receiver                  = isset($_POST['receiver']) ? $_POST['receiver'] : '';
        $projectStatusHistoryDetails->mail_content              = isset($_POST['mail_content']) ? $_POST['mail_content'] : '';
        $projectStatusHistoryDetails->site_content              = isset($_POST['site_content']) ? $_POST['site_content'] : '';
        $projectStatusHistoryDetails->create();

        // Disable automatic refund
        $this->projects->remb_auto = 1;
        $this->projects->update();

        $projects_remb        = $this->loadData('projects_remb');
        $aAutomaticRepayments = $projects_remb->select('status = 0 AND id_project = ' . $this->projects->id_project);

        if (is_array($aAutomaticRepayments)) {
            foreach ($aAutomaticRepayments as $aAutomaticRepayment) {
                $projects_remb->get($aAutomaticRepayment['id_project_remb'], 'id_project_remb');
                $projects_remb->status = \projects_remb::STATUS_AUTOMATIC_REFUND_DISABLED;
                $projects_remb->update();
            }
        }

        // Disable automatic debits
        if (in_array($iStatus, array(\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT))) {
            $prelevements  = $this->loadData('prelevements');
            $aDirectDebits = $prelevements->select('id_project = ' . $this->projects->id_project . ' AND status = 0 AND type_prelevement = 1 AND date_execution_demande_prelevement > NOW()');

            if (is_array($aDirectDebits)) {
                foreach ($aDirectDebits as $aDirectDebit) {
                    $prelevements->get($aDirectDebit['id_prelevement']);
                    $prelevements->status = \prelevements::STATUS_TEMPORARILY_BLOCKED;
                    $prelevements->update();
                }
            }
        }

        $this->sendProblemStatusEmailBorrower($iStatus);

        if (false === empty($_POST['send_email'])) {
            $this->sendProblemStatusEmailLender($iStatus, $projectStatusHistoryDetails);
        }

        header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
        die;
    }

    private function sendProblemStatusEmailBorrower($iStatus)
    {
        $aReplacements = array();

        switch ($iStatus) {
            case \projects_status::PROBLEME:
                $sMailType = 'emprunteur-projet-statut-probleme';
                break;
            case \projects_status::PROBLEME_J_X:
                $sMailType = 'emprunteur-projet-statut-probleme-j-x';
                break;
            case \projects_status::RECOUVREMENT:
                $sMailType = 'emprunteur-projet-statut-recouvrement';
                break;
            case \projects_status::PROCEDURE_SAUVEGARDE:
                $sMailType = 'emprunteur-projet-statut-procedure-sauvegarde';
                break;
            case \projects_status::REDRESSEMENT_JUDICIAIRE:
                $sMailType = 'emprunteur-projet-statut-redressement-judiciaire';
                break;
            case \projects_status::LIQUIDATION_JUDICIAIRE:
                $sMailType = 'emprunteur-projet-statut-liquidation-judiciaire';
                break;
            default:
                return;
        }

        $this->settings->get('Facebook', 'type');
        $sFacebookURL = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $sTwitterURL = $this->settings->value;

        $this->settings->get('Virement - BIC', 'type');
        $sBIC = $this->settings->value;

        $this->settings->get('Virement - IBAN', 'type');
        $sIBAN = $this->settings->value;

        $this->settings->get('Téléphone emprunteur', 'type');
        $sBorrowerPhoneNumber = $this->settings->value;

        $this->settings->get('Adresse emprunteur', 'type');
        $sBorrowerEmail = $this->settings->value;

        $oPaymentSchedule = $this->loadData('echeanciers_emprunteur');
        $oPaymentSchedule->get($this->projects->id_project, 'ordre = 1 AND id_project');

        if (in_array($iStatus, array(\projects_status::PROBLEME, \projects_status::PROBLEME_J_X))) {
            $aNextRepayment = $oPaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND date_echeance_emprunteur > "' . date('Y-m-d') . '"', 'date_echeance_emprunteur ASC', 0, 1);
            $oNow           = new \DateTime();
            $aReplacements['delai_regularisation'] = $oNow->diff(new \DateTime($aNextRepayment[0]['date_echeance_emprunteur']))->days;
        }

        if (in_array($iStatus, array(\projects_status::RECOUVREMENT, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE))) {
            $oLenderRepaymentSchedule = $this->loadData('echeanciers');
            $aReplacements['CRD'] = $this->ficelle->formatNumber($oLenderRepaymentSchedule->sum('id_project = ' . $this->projects->id_project . ' AND status = 0', 'capital'), 2);

            if (\projects_status::RECOUVREMENT == $iStatus) {
                $aReplacements['mensualites_impayees'] = $this->ficelle->formatNumber($oLenderRepaymentSchedule->sum('id_project = ' . $this->projects->id_project . ' AND status = 0 AND date_echeance < "' . date('Y-m-d') . '"', 'capital'), 2);
            }
        }

        $aFundingDate = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'id_project_status_history ASC', 0, 1);
        $iFundingTime = strtotime($aFundingDate[0]['added']);

        $aReplacements = $aReplacements + array(
                'url'                  => $this->furl,
                'surl'                 => $this->surl,
                'civilite_e'           => $this->clients->civilite,
                'nom_e'                => $this->clients->nom,
                'prenom_e'             => $this->clients->prenom,
                'entreprise'           => $this->companies->name,
                'montant_emprunt'      => $this->ficelle->formatNumber($this->projects->amount, 0),
                'mensualite_e'         => $this->ficelle->formatNumber(($oPaymentSchedule->montant + $oPaymentSchedule->commission + $oPaymentSchedule->tva) / 100),
                'num_dossier'          => $this->projects->id_project,
                'nb_preteurs'          => $this->loans->getNbPreteurs($this->projects->id_project),
                'date_financement'     => htmlentities($this->dates->tableauMois['fr'][date('n', $iFundingTime)], null, 'UTF-8') . date(' Y', $iFundingTime), // @todo intl
                'lien_pouvoir'         => $this->furl . '/pdf/pouvoir/' . $this->clients->hash . '/' . $this->projects->id_project,
                'societe_recouvrement' => $this->cab,
                'bic_sfpmei'           => $sBIC,
                'iban_sfpmei'          => $sIBAN,
                'tel_emprunteur'       => $sBorrowerPhoneNumber,
                'email_emprunteur'     => $sBorrowerEmail,
                'lien_fb'              => $sFacebookURL,
                'lien_tw'              => $sTwitterURL,
                'annee'                => date('Y')
            );

        $this->mail_template->get($sMailType, 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');
        $aReplacements['sujet'] = $this->mail_template->subject;

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($sMailType, $aReplacements);
        $message->setTo($this->clients->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function sendProblemStatusEmailLender($iStatus, $projectStatusHistoryDetails)
    {
        $this->settings->get('Facebook', 'type');
        $sFacebookURL = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $sTwitterURL = $this->settings->value;

        $aCommonReplacements = array(
            'url'                    => $this->furl,
            'surl'                   => $this->surl,
            'lien_fb'                => $sFacebookURL,
            'lien_tw'                => $sTwitterURL,
            'societe_recouvrement'   => $this->cab,
            'contenu_mail'           => nl2br($projectStatusHistoryDetails->mail_content),
            'coordonnees_mandataire' => nl2br($projectStatusHistoryDetails->receiver)
        );

        switch ($iStatus) {
            case \projects_status::PROBLEME:
                $iNotificationType = \notifications::TYPE_PROJECT_PROBLEM;
                $sEmailTypePerson  = 'preteur-projet-statut-probleme';
                $sEmailTypeSociety = 'preteur-projet-statut-probleme';
                break;
            case \projects_status::PROBLEME_J_X:
                $iNotificationType = \notifications::TYPE_PROJECT_PROBLEM_REMINDER;
                $sEmailTypePerson  = 'preteur-projet-statut-probleme-j-x';
                $sEmailTypeSociety = 'preteur-projet-statut-probleme-j-x';
                break;
            case \projects_status::RECOUVREMENT:
                $iNotificationType = \notifications::TYPE_PROJECT_RECOVERY;
                $sEmailTypePerson  = 'preteur-projet-statut-recouvrement';
                $sEmailTypeSociety = 'preteur-projet-statut-recouvrement';
                break;
            case \projects_status::PROCEDURE_SAUVEGARDE:
                $iNotificationType = \notifications::TYPE_PROJECT_PRECAUTIONARY_PROCESS;
                $sEmailTypePerson  = 'preteur-projet-statut-procedure-sauvegarde';
                $sEmailTypeSociety = 'preteur-projet-statut-procedure-sauvegarde';
                break;
            case \projects_status::REDRESSEMENT_JUDICIAIRE:
                $iNotificationType  = \notifications::TYPE_PROJECT_RECEIVERSHIP;
                $aCollectiveProcess = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status IN (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::PROCEDURE_SAUVEGARDE . ')', 'id_project_status_history ASC', 0, 1);

                if (empty($aCollectiveProcess)) {
                    $sEmailTypePerson  = 'preteur-projet-statut-redressement-judiciaire';
                    $sEmailTypeSociety = 'preteur-projet-statut-redressement-judiciaire';
                } else {
                    $sEmailTypePerson  = 'preteur-projet-statut-redressement-judiciaire-post-procedure';
                    $sEmailTypeSociety = 'preteur-projet-statut-redressement-judiciaire-post-procedure';
                }
                break;
            case \projects_status::LIQUIDATION_JUDICIAIRE:
                $iNotificationType  = \notifications::TYPE_PROJECT_COMPULSORY_LIQUIDATION;
                $aCollectiveProcess = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status IN (SELECT id_project_status FROM projects_status WHERE status IN (' . \projects_status::PROCEDURE_SAUVEGARDE . ', ' . \projects_status::REDRESSEMENT_JUDICIAIRE . '))', 'id_project_status_history ASC', 0, 1);

                if (empty($aCollectiveProcess)) {
                    $sEmailTypePerson  = 'preteur-projet-statut-liquidation-judiciaire';
                    $sEmailTypeSociety = 'preteur-projet-statut-liquidation-judiciaire';
                } else {
                    $sEmailTypePerson  = 'preteur-projet-statut-liquidation-judiciaire-post-procedure';
                    $sEmailTypeSociety = 'preteur-projet-statut-liquidation-judiciaire-post-procedure';
                }
                break;
            case \projects_status::DEFAUT:
                $iNotificationType = \notifications::TYPE_PROJECT_FAILURE;
                $sEmailTypePerson  = 'preteur-projet-statut-defaut-personne-physique';
                $sEmailTypeSociety = 'preteur-projet-statut-defaut-personne-morale';

                $aCompulsoryLiquidation = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::LIQUIDATION_JUDICIAIRE . ')', 'id_project_status_history ASC', 0, 1);
                $aCommonReplacements['date_annonce_liquidation_judiciaire'] = date('d/m/Y', strtotime($aCompulsoryLiquidation[0]['added']));
                break;
        }

        $aRepaymentStatus = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'id_project_status_history ASC', 0, 1);
        $aCommonReplacements['annee_projet'] = date('Y', strtotime($aRepaymentStatus[0]['added']));

        if (in_array($iStatus, array(\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE))) {
            $oMaxClaimsSendingDate = new \DateTime($projectStatusHistoryDetails->date);
            $aCommonReplacements['date_max_envoi_declaration_creances'] = date('d/m/Y', $oMaxClaimsSendingDate->add(new \DateInterval('P2M'))->getTimestamp());
        }

        $aLenderLoans = $this->loans->getProjectLoansByLender($this->projects->id_project);

        if (is_array($aLenderLoans)) {
            foreach ($aLenderLoans as $aLoans) {
                $this->lenders_accounts->get($aLoans['id_lender'], 'id_lender_account');
                $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

                $fTotalPayedBack = 0.0;
                $iLoansCount     = $aLoans['cnt'];
                $fLoansAmount    = $aLoans['amount'];

                foreach ($this->echeanciers->select('id_loan IN (' . $aLoans['loans'] . ') AND id_project = ' . $this->projects->id_project . ' AND status = 1') as $aPayment) {
                    $fTotalPayedBack += $aPayment['montant'] / 100 - $aPayment['prelevements_obligatoires'] - $aPayment['retenues_source'] - $aPayment['csg'] - $aPayment['prelevements_sociaux'] - $aPayment['contributions_additionnelles'] - $aPayment['prelevements_solidarite'] - $aPayment['crds'];
                }

                $this->notifications->type       = $iNotificationType;
                $this->notifications->id_lender  = $aLoans['id_lender'];
                $this->notifications->id_project = $this->projects->id_project;
                $this->notifications->amount     = $fLoansAmount;
                $this->notifications->id_bid     = 0;
                $this->notifications->create();

                if (
                    in_array($iStatus, array(\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT))
                    || $this->clients_gestion_notifications->getNotif($this->clients->id_client, \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM, 'immediatement')
                ) {
                    $this->clients_gestion_mails_notif->id_client       = $this->clients->id_client;
                    $this->clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM;
                    $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                    $this->clients_gestion_mails_notif->id_transaction  = 0;
                    $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                    $this->clients_gestion_mails_notif->id_loan         = 0;
                    $this->clients_gestion_mails_notif->immediatement   = 1;
                    $this->clients_gestion_mails_notif->create();

                    $aNextRepayment = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND date_echeance > "' . date('Y-m-d') . '"', 'date_echeance ASC', 0, 1);

                    $aReplacements = $aCommonReplacements + array(
                            'prenom_p'                    => $this->clients->prenom,
                            'entreprise'                  => $this->companies->name,
                            'montant_pret'                => $this->ficelle->formatNumber($fLoansAmount / 100, 0),
                            'montant_rembourse'           => $this->ficelle->formatNumber($fTotalPayedBack),
                            'nombre_prets'                => $iLoansCount . ' ' . (($iLoansCount > 1) ? 'pr&ecirc;ts' : 'pr&ecirc;t'), // @todo intl
                            'date_prochain_remboursement' => $this->dates->formatDate($aNextRepayment[0]['date_echeance'], 'd/m/Y'), // @todo intl
                            'CRD'                         => $this->ficelle->formatNumber($fLoansAmount / 100 - $fTotalPayedBack)
                        );

                    $sMailType = (in_array($this->clients->type, array(1, 3))) ? $sEmailTypePerson : $sEmailTypeSociety;
                    $locale  = $this->getParameter('locale');
                    $this->mail_template->get($sMailType, 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $locale . '" AND type');

                    $aReplacements['sujet'] = $this->mail_template->subject;

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($sMailType, $aReplacements);
                    $message->setTo($this->clients->email);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }
            }
        }
    }

    public function _export()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        /** @var \projects $oProject */
        $this->oProject = $this->loadData('projects');

        if (empty($this->params[0]) || false === $this->oProject->get($this->params[0])) {
            return;
        }

        /** @var \companies $oCompany */
        $this->oCompany = $this->loadData('companies');
        $this->oCompany->get($this->oProject->id_company);

        /** @var \companies_bilans $oAnnualAccounts */
        $oAnnualAccounts = $this->loadData('companies_bilans');

        /** @var \company_balance $oCompanyBalance */
        $oCompanyBalance = $this->loadData('company_balance');

        $this->settings->get('TVA', 'type');
        $this->fVATRate = $this->settings->value;

        /** @var company_rating $oCompanyRating */
        $oCompanyRating = $this->loadData('company_rating');

        $this->aRatings                 = $oCompanyRating->getHistoryRatingsByType($this->oProject->id_company_rating_history);
        $this->aAnnualAccounts          = $oAnnualAccounts->select('id_company = ' . $this->oCompany->id_company . ' AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $this->oProject->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);
        $aAnnualAccountsIds             = array_column($this->aAnnualAccounts, 'id_bilan');
        $this->aBalanceSheets           = $oCompanyBalance->getBalanceSheetsByAnnualAccount($aAnnualAccountsIds);
        $this->bIsProblematicCompany    = $this->oCompany->countProblemsBySIREN() > 0;
        $this->iDeclaredRevenue         = $this->oProject->ca_declara_client;
        $this->iDeclaredOperatingIncome = $this->oProject->resultat_exploitation_declara_client;
        $this->iDeclaredCapitalStock    = $this->oProject->fonds_propres_declara_client;
        $this->aCompanyProjects         = $this->oCompany->getProjectsBySIREN();
        $this->fCompanyOwedCapital      = $this->oCompany->getOwedCapitalBySIREN();

        header('Content-Type: application/csv;charset=UTF-8');
        header('Content-Disposition: attachment;filename=risque-' . $this->oProject->id_project . '.csv');

        ob_start();
        $this->fireView();
        $sCSV = ob_get_contents();
        ob_end_clean();

        echo "\xEF\xBB\xBF";
        echo $sCSV;die;
    }

    public function _ajax_rejection()
    {
        $this->hideDecoration();

        /** @var \project_rejection_reason $oProjectRejectionReason */
        $oProjectRejectionReason = $this->loadData('project_rejection_reason');
        $this->aRejectionReasons = $oProjectRejectionReason->select();
        $this->iStep             = $this->params[0];
        $this->iProjectId        = $this->params[1];

    }

    public function _changeClient()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->clients = $this->loadData('clients');

        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->lClients = $this->clients->select('nom LIKE "%' . $this->params[0] . '%" OR prenom LIKE "%' . $this->params[0] . '%"');
        }
    }

    public function _addMemo()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // Chargement des datas
        $this->projects_comments = $this->loadData('projects_comments');

        if (isset($this->params[0]) && isset($this->params[1]) && $this->projects_comments->get($this->params[1], 'id_project_comment')) {
            $this->type = 'edit';
        } else {
            $this->type = 'add';
        }
    }

    public function _file()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->projects = $this->loadData('projects');

        if (isset($_POST['send_etape5']) && isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            // Histo user //
            $serialize = serialize(array('id_project' => $this->params[0], 'files' => $_FILES));
            $this->users_history->histo(9, 'dossier edit etapes 5', $_SESSION['user']['id_user'], $serialize);

            $this->tablResult = array();

            foreach ($_FILES as $field => $file) {
                //We made the field name = attachment type id
                $iAttachmentType = $field;
                if ('' !== $file['name'] && $this->uploadAttachment($this->projects->id_project, $field, $iAttachmentType)) {
                    $this->tablResult['fichier_' . $iAttachmentType] = 'ok';
                }
            }
            $this->result = json_encode($this->tablResult);
        }
    }

    public function _remove_file()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireView   = false;
        $this->autoFireDebug  = false;

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $aResult = array();

        if (isset($_POST['attachment_id'])) {
            $iAttachmentId = $_POST['attachment_id'];

            if ($this->removeAttachment($iAttachmentId)) {
                $aResult[$iAttachmentId] = 'ok';
            }
        }

        echo json_encode($aResult);
    }

    public function _tab_email()
    {
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireView   = false;
        $this->autoFireDebug  = false;

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $sResult = 'nok';

        if (isset($_POST['project_id']) && isset($_POST['flag'])) {
            $this->projects = $this->loadData('projects');
            if ($this->projects->get($_POST['project_id'], 'id_project')) {
                $this->projects->stop_relances = $_POST['flag'];
                $this->projects->update();
                $sResult = 'ok';
            }
        }

        echo $sResult;
    }

    public function _add()
    {
        $this->clients          = $this->loadData('clients');
        $this->clients_adresses = $this->loadData('clients_adresses');
        $this->companies        = $this->loadData('companies');
        $this->projects         = $this->loadData('projects');
        $this->projects_status  = $this->loadData('projects_status');

        if (isset($_POST['send_create_etape1'])) {
            if (isset($_POST['id_client']) && $this->clients->get($_POST['id_client'], 'id_client')) {
                header('Location: ' . $this->lurl . '/dossiers/add/create_etape2/' . $_POST['id_client']);
                die;
            } else {
                header('Location: ' . $this->lurl . '/dossiers/add/create_etape2');
                die;
            }
        }

        if (isset($this->params[0]) && $this->params[0] == 'create_etape2') {
            if (false === isset($this->params[1]) || false === $this->clients->get($this->params[1], 'id_client')) {
                $this->clients_adresses = $this->loadData('clients_adresses');

                $this->clients->create();

                $this->clients_adresses->id_client = $this->clients->id_client;
                $this->clients_adresses->create();
            }

            if (false === $this->companies->get($this->clients->id_client, 'id_client_owner')) {
                $this->companies->id_client_owner = $this->clients->id_client;
                $this->companies->create();
            }

            $this->projects->id_company = $this->companies->id_company;
            $this->projects->create_bo  = 1; // on signale que le projet a été créé en Bo
            $this->projects->create();

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
            $oProjectManager = $this->get('unilend.service.project_manager');
            $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::A_TRAITER, $this->projects);

            $serialize = serialize(array('id_project' => $this->projects->id_project));
            $this->users_history->histo(7, 'dossier create', $_SESSION['user']['id_user'], $serialize);

            header('Location: ' . $this->lurl . '/dossiers/add/' . $this->projects->id_project);
            die;
        } elseif (isset($this->params[0])) {
            $this->prescripteurs           = $this->loadData('prescripteurs');
            $this->clients_prescripteurs   = $this->loadData('clients');
            $this->companies_prescripteurs = $this->loadData('companies');

            $this->projects->get($this->params[0]);
            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');
            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            if (isset($this->params[1]) && $this->params[1] === 'altares') {
                $oAltares = new Altares();
                $oResult  = $oAltares->getEligibility($this->companies->siren);
                $oAltares->setCompanyData($this->companies, $oResult->myInfo);
                $oAltares->setProjectData($this->projects, $oResult->myInfo);
                $oAltares->setCompanyBalance($this->companies);

                header('Location: ' . $this->lurl . '/dossiers/add/' . $this->projects->id_project);
                die;
            }

            $this->bHasAdvisor = false;

            if (
                $this->projects->id_prescripteur > 0
                && $this->prescripteurs->get($this->projects->id_prescripteur, 'id_prescripteur')
            ) {
                $this->clients_prescripteurs->get($this->prescripteurs->id_client, 'id_client');
                $this->companies_prescripteurs->get($this->prescripteurs->id_entite, 'id_company');
                $this->bHasAdvisor = true;
            }
        }

        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->dureePossible = explode(',', $this->settings->value);
    }

    public function _funding()
    {
        $this->projects  = $this->loadData('projects');
        $this->companies = $this->loadData('companies');
        $this->bids      = $this->loadData('bids');

        $this->lProjects = $this->projects->selectProjectsByStatus(\projects_status::EN_FUNDING);
    }

    public function _remboursements()
    {
        $this->setView('remboursements');
        $this->pageTitle = 'Remboursements';
        $this->listing(array(\projects_status::FUNDE, \projects_status::REMBOURSEMENT));
    }

    public function _no_remb()
    {
        $this->setView('remboursements');
        $this->pageTitle = 'Incidents de remboursement';
        $this->listing(array(\projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT));
    }

    private function listing(array $aStatus)
    {
        $this->projects               = $this->loadData('projects');
        $this->companies              = $this->loadData('companies');
        $this->clients                = $this->loadData('clients');
        $this->echeanciers            = $this->loadData('echeanciers');
        $this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');

        if (isset($_POST['form_search_remb'])) {
            $this->lProjects = $this->projects->searchDossiersByStatus($aStatus, $_POST['siren'], $_POST['societe'], $_POST['nom'], $_POST['prenom'], $_POST['projet'], $_POST['email']);
        } else {
            $this->lProjects = $this->projects->searchDossiersByStatus($aStatus);
        }
    }

    public function _detail_remb()
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 600);

        $this->projects                      = $this->loadData('projects');
        $this->projects_status               = $this->loadData('projects_status');
        $this->projects_status_history       = $this->loadData('projects_status_history');
        $this->companies                     = $this->loadData('companies');
        $this->clients                       = $this->loadData('clients');
        $this->loans                         = $this->loadData('loans');
        $this->echeanciers                   = $this->loadData('echeanciers');
        $this->echeanciers_emprunteur        = $this->loadData('echeanciers_emprunteur');
        $this->transactions                  = $this->loadData('transactions');
        $this->wallets_lines                 = $this->loadData('wallets_lines');
        $this->lenders_accounts              = $this->loadData('lenders_accounts');
        $this->notifications                 = $this->loadData('notifications');
        $this->bank_unilend                  = $this->loadData('bank_unilend');
        $this->projects_remb                 = $this->loadData('projects_remb');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $this->settings                      = $this->loadData('settings');

        $this->settings->get('Cabinet de recouvrement', 'type');
        $this->cab = $this->settings->value;

        $this->settings->get('TVA', 'type');
        $this->tva = $this->settings->value;

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');
            $this->users->get($this->projects->id_analyste, 'id_user');
            $this->projects_status->getLastStatut($this->projects->id_project);

            $this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);

            // liste des echeances emprunteur par mois
            $lRembs = $this->echeanciers_emprunteur->select('id_project = ' . $this->projects->id_project);
            // ON recup la date de statut remb
            $dernierStatut     = $this->projects_status_history->select('id_project = ' . $this->projects->id_project, 'id_project_status_history DESC', 0, 1);
            $dateDernierStatut = $dernierStatut[0]['added'];

            $this->nbRembEffet  = 0;
            $this->nbRembaVenir = 0;

            $this->totalEffet  = 0;
            $this->totalaVenir = 0;

            $this->interetEffet  = 0;
            $this->interetaVenir = 0;

            $this->capitalEffet  = 0;
            $this->capitalaVenir = 0;

            $this->commissionEffet  = 0;
            $this->commissionaVenir = 0;

            $this->tvaEffet  = 0;
            $this->tvaaVenir = 0;

            $this->nextRemb = '';

            foreach ($lRembs as $k => $r) {
                // remboursement effectué
                if ($r['status_emprunteur'] == 1) {
                    $this->nbRembEffet += 1;
                    $MontantRemb = $this->echeanciers->getMontantRembEmprunteur($r['montant'], $r['commission'], $r['tva']);
                    $this->totalEffet += $MontantRemb;
                    $this->interetEffet += $r['interets'];
                    $this->capitalEffet += $r['capital'];
                    $this->commissionEffet += $r['commission'];
                    $this->tvaEffet += $r['tva'];
                } // remb a venir
                else {
                    if ($this->nextRemb == '') {
                        $this->nextRemb = $r['date_echeance_emprunteur'];
                    }

                    $this->nbRembaVenir += 1;
                    $MontantRemb = $this->echeanciers->getMontantRembEmprunteur($r['montant'], $r['commission'], $r['tva']);
                    $this->totalaVenir += $MontantRemb;
                    $this->interetaVenir += $r['interets'];
                    $this->capitalaVenir += $r['capital'];
                    $this->commissionaVenir += $r['commission'];
                    $this->tvaaVenir += $r['tva'];
                }
            }

            // com unilend
            $this->commissionUnilend = ($this->commissionEffet + $this->commissionaVenir);

            // activer/desactiver remb auto (eclatement)
            if (isset($_POST['send_remb_auto'])) {
                if ($_POST['remb_auto'] == 1) {
                    $listdesRembauto = $this->projects_remb->select('id_project = ' . $this->projects->id_project . ' AND status = ' . \projects_remb::STATUS_PENDING . ' AND DATE(date_remb_preteurs) >= "' . date('Y-m-d') . '"');

                    foreach ($listdesRembauto as $rembauto) {
                        $this->projects_remb->get($rembauto['id_project_remb'], 'id_project_remb');
                        $this->projects_remb->status = \projects_remb::STATUS_AUTOMATIC_REFUND_DISABLED;
                        $this->projects_remb->update();
                    }
                } elseif ($_POST['remb_auto'] == 0) {
                    $listdesRembauto = $this->projects_remb->select('id_project = ' . $this->projects->id_project . ' AND status = ' . \projects_remb::STATUS_AUTOMATIC_REFUND_DISABLED . ' AND LEFT(date_remb_preteurs,10) >= "' . date('Y-m-d') . '" AND date_remb_preteurs_reel = "0000-00-00 00:00:00"');

                    foreach ($listdesRembauto as $rembauto) {
                        $this->projects_remb->get($rembauto['id_project_remb'], 'id_project_remb');
                        $this->projects_remb->status = \projects_remb::STATUS_PENDING;
                        $this->projects_remb->update();
                    }
                }

                $this->projects->remb_auto = $_POST['remb_auto'];
                $this->projects->update();
            }
            // CTA On rembourse les preteurs pour le mois en cours
            if (isset($this->params[1]) && $this->params[1] == 'remb') {
                // On recup le param
                $settingsControleRemb = $this->loadData('settings');
                $settingsControleRemb->get('Controle cron remboursements auto', 'type');

                // on rentre dans le cron si statut égale 1
                if ($settingsControleRemb->value == 1) {
                    // On passe le statut a zero pour signaler qu'on est en cours de traitement
                    $settingsControleRemb->value = 0;
                    $settingsControleRemb->update();

                    /////////////////////
                    // Remb emprunteur //
                    /////////////////////
                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    // On parcourt les remb emprunteurs
                    $lEcheancesRembEmprunteur = $this->echeanciers_emprunteur->select('id_project = ' . $this->projects->id_project . ' AND status_emprunteur = 1', 'ordre ASC');
                    // On ne passe qu'une fois par clic - BT 17882
                    $deja_passe = false;

                    if ($lEcheancesRembEmprunteur != false) {
                        foreach ($lEcheancesRembEmprunteur as $RembEmpr) {
                            $montant                      = 0;
                            $capital                      = 0;
                            $interets                     = 0;
                            $commission                   = 0;
                            $tva                          = 0;
                            $prelevements_obligatoires    = 0;
                            $retenues_source              = 0;
                            $csg                          = 0;
                            $prelevements_sociaux         = 0;
                            $contributions_additionnelles = 0;
                            $prelevements_solidarite      = 0;
                            $crds                         = 0;

                            $lEcheances = $this->echeanciers->select('id_project = ' . $RembEmpr['id_project'] . ' AND status_emprunteur = 1 AND ordre = ' . $RembEmpr['ordre'] . ' AND status = 0');

                            if ($lEcheances == false) {

                            } else {
                                //BT 17882
                                if (! $deja_passe) {
                                    $deja_passe = true;
                                    foreach ($lEcheances as $e) {

                                        // on fait la somme de tout
                                        $montant += ($e['montant'] / 100);
                                        $capital += ($e['capital'] / 100);
                                        $interets += ($e['interets'] / 100);
                                        $commission += ($e['commission'] / 100);
                                        $tva += ($e['tva'] / 100);
                                        $prelevements_obligatoires += $e['prelevements_obligatoires'];
                                        $retenues_source += $e['retenues_source'];
                                        $csg += $e['csg'];
                                        $prelevements_sociaux += $e['prelevements_sociaux'];
                                        $contributions_additionnelles += $e['contributions_additionnelles'];
                                        $prelevements_solidarite += $e['prelevements_solidarite'];
                                        $crds += $e['crds'];

                                        // Remb net preteur
                                        $rembNet = ($e['montant'] / 100) - $e['prelevements_obligatoires'] - $e['retenues_source'] - $e['csg'] - $e['prelevements_sociaux'] - $e['contributions_additionnelles'] - $e['prelevements_solidarite'] - $e['crds'];

                                        if ($this->transactions->get($e['id_echeancier'], 'id_echeancier') == false) {
                                            $this->lenders_accounts->get($e['id_lender'], 'id_lender_account');
                                            $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

                                            $this->echeanciers->get($e['id_echeancier'], 'id_echeancier');
                                            $this->echeanciers->status             = 1; // remboursé
                                            $this->echeanciers->status_email_remb  = 1; // remboursé
                                            $this->echeanciers->date_echeance_reel = date('Y-m-d H:i:s');
                                            $this->echeanciers->update();

                                            $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                                            $this->transactions->montant          = ($rembNet * 100);
                                            $this->transactions->id_echeancier    = $e['id_echeancier']; // id de l'echeance remb
                                            $this->transactions->id_langue        = 'fr';
                                            $this->transactions->date_transaction = date('Y-m-d H:i:s');
                                            $this->transactions->status           = '1';
                                            $this->transactions->etat             = '1';
                                            $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                            $this->transactions->type_transaction = 5; // remb enchere
                                            $this->transactions->transaction      = 2; // transaction virtuelle
                                            $this->transactions->create();

                                            $this->wallets_lines->id_lender                = $e['id_lender'];
                                            $this->wallets_lines->type_financial_operation = 40;
                                            $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                                            $this->wallets_lines->status                   = 1; // non utilisé
                                            $this->wallets_lines->type                     = 2; // transaction virtuelle
                                            $this->wallets_lines->amount                   = $rembNet * 100;
                                            $this->wallets_lines->create();

                                            $this->notifications->type       = \notifications::TYPE_REPAYMENT;
                                            $this->notifications->id_lender  = $this->lenders_accounts->id_lender_account;
                                            $this->notifications->id_project = $this->projects->id_project;
                                            $this->notifications->amount     = $rembNet * 100;
                                            $this->notifications->create();

                                            $this->clients_gestion_mails_notif->id_client       = $this->lenders_accounts->id_client_owner;
                                            $this->clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_REPAYMENT;
                                            $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                                            $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                                            $this->clients_gestion_mails_notif->id_transaction  = $this->transactions->id_transaction;
                                            $this->clients_gestion_mails_notif->create();

                                            if ($this->projects_status->status == \projects_status::RECOUVREMENT) {
                                                $this->companies->get($this->projects->id_company, 'id_company');

                                                $varMail = array(
                                                    'surl'             => $this->surl,
                                                    'url'              => $this->furl,
                                                    'prenom_p'         => $this->clients->prenom,
                                                    'cab_recouvrement' => $this->cab,
                                                    'mensualite_p'     => $this->ficelle->formatNumber($rembNet),
                                                    'nom_entreprise'   => $this->companies->name,
                                                    'solde_p'          => $this->transactions->getSolde($this->clients->id_client),
                                                    'link_echeancier'  => $this->furl,
                                                    'motif_virement'   => $this->clients->getLenderPattern($this->clients->id_client),
                                                    'lien_fb'          => $lien_fb,
                                                    'lien_tw'          => $lien_tw
                                                );

                                                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-dossier-recouvre', $varMail);
                                                $message->setTo($this->clients->email);
                                                $mailer = $this->get('mailer');
                                                $mailer->send($message);

                                            } elseif (isset($this->params[2]) && $this->params[2] == 'regul') {
                                                $this->companies->get($this->projects->id_company, 'id_company');
                                                $nbpret = $this->loans->counter('id_lender = ' . $e['id_lender'] . ' AND id_project = ' . $e['id_project']);

                                                // euro avec ou sans "s"
                                                if ($rembNet >= 2) {
                                                    $euros = ' euros';
                                                } else {
                                                    $euros = ' euro';
                                                }
                                                $rembNetEmail = $this->ficelle->formatNumber($rembNet) . $euros;

                                                if ($this->transactions->getSolde($this->clients->id_client) >= 2) {
                                                    $euros = ' euros';
                                                } else {
                                                    $euros = ' euro';
                                                }
                                                $solde   = $this->ficelle->formatNumber($this->transactions->getSolde($this->clients->id_client)) . $euros;
                                                $timeAdd = strtotime($dateDernierStatut);
                                                $month   = $this->dates->tableauMois['fr'][date('n', $timeAdd)];

                                                $varMail = array(
                                                    'surl'                  => $this->surl,
                                                    'url'                   => $this->furl,
                                                    'prenom_p'              => $this->clients->prenom,
                                                    'mensualite_p'          => $rembNetEmail,
                                                    'mensualite_avantfisca' => ($e['montant'] / 100),
                                                    'nom_entreprise'        => $this->companies->name,
                                                    'date_bid_accepte'      => date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd),
                                                    'nbre_prets'            => $nbpret,
                                                    'solde_p'               => $solde,
                                                    'motif_virement'        => $this->clients->getLenderPattern($this->clients->id_client),
                                                    'lien_fb'               => $lien_fb,
                                                    'lien_tw'               => $lien_tw
                                                );

                                                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-regularisation-remboursement', $varMail);
                                                $message->setTo($this->clients->email);
                                                $mailer = $this->get('mailer');
                                                $mailer->send($message);

                                            } else {
                                                // envoi email remb ok maintenant ou non
                                                if ($this->clients_gestion_notifications->getNotif($this->clients->id_client, 5, 'immediatement') == true) {
                                                    $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                                                    $this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
                                                    $this->clients_gestion_mails_notif->update();

                                                    $this->companies->get($this->projects->id_company, 'id_company');

                                                    $nbpret = $this->loans->counter('id_lender = ' . $e['id_lender'] . ' AND id_project = ' . $e['id_project']);

                                                    // euro avec ou sans "s"
                                                    if ($rembNet >= 2) {
                                                        $euros = ' euros';
                                                    } else {
                                                        $euros = ' euro';
                                                    }
                                                    $rembNetEmail = $this->ficelle->formatNumber($rembNet) . $euros;

                                                    if ($this->transactions->getSolde($this->clients->id_client) >= 2) {
                                                        $euros = ' euros';
                                                    } else {
                                                        $euros = ' euro';
                                                    }
                                                    $solde   = $this->ficelle->formatNumber($this->transactions->getSolde($this->clients->id_client)) . $euros;
                                                    $timeAdd = strtotime($dateDernierStatut);
                                                    $month   = $this->dates->tableauMois['fr'][date('n', $timeAdd)];

                                                    $varMail = array(
                                                        'surl'                  => $this->surl,
                                                        'url'                   => $this->furl,
                                                        'prenom_p'              => $this->clients->prenom,
                                                        'mensualite_p'          => $rembNetEmail,
                                                        'mensualite_avantfisca' => ($e['montant'] / 100),
                                                        'nom_entreprise'        => $this->companies->name,
                                                        'date_bid_accepte'      => date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd),
                                                        'nbre_prets'            => $nbpret,
                                                        'solde_p'               => $solde,
                                                        'motif_virement'        => $this->clients->getLenderPattern($this->clients->id_client),
                                                        'lien_fb'               => $lien_fb,
                                                        'lien_tw'               => $lien_tw
                                                    );

                                                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-remboursement', $varMail);
                                                    $message->setTo($this->clients->email);
                                                    $mailer = $this->get('mailer');
                                                    $mailer->send($message);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // partie a retirer de bank unilend
                            $rembNetTotal = $montant - $prelevements_obligatoires - $retenues_source - $csg - $prelevements_sociaux - $contributions_additionnelles - $prelevements_solidarite - $crds;

                            // partie pour l'etat
                            $TotalEtat = $prelevements_obligatoires + $retenues_source + $csg + $prelevements_sociaux + $contributions_additionnelles + $prelevements_solidarite + $crds;

                            // On evite de créer une ligne qui sert a rien
                            if ($rembNetTotal != 0) {
                                $this->transactions->montant                  = 0;
                                $this->transactions->id_echeancier            = 0; // on reinitialise
                                $this->transactions->id_client                = 0; // on reinitialise
                                $this->transactions->montant_unilend          = '-' . $rembNetTotal * 100;
                                $this->transactions->montant_etat             = $TotalEtat * 100;
                                $this->transactions->id_echeancier_emprunteur = $RembEmpr['id_echeancier_emprunteur']; // id de l'echeance emprunteur
                                $this->transactions->id_langue                = 'fr';
                                $this->transactions->date_transaction         = date('Y-m-d H:i:s');
                                $this->transactions->status                   = '1';
                                $this->transactions->etat                     = '1';
                                $this->transactions->ip_client                = $_SERVER['REMOTE_ADDR'];
                                $this->transactions->type_transaction         = \transactions_types::TYPE_UNILEND_REPAYMENT;
                                $this->transactions->transaction              = 2; // transaction virtuelle
                                $this->transactions->create();

                                $this->bank_unilend->id_transaction         = $this->transactions->id_transaction;
                                $this->bank_unilend->id_project             = $this->projects->id_project;
                                $this->bank_unilend->montant                = '-' . $rembNetTotal * 100;
                                $this->bank_unilend->etat                   = $TotalEtat * 100;
                                $this->bank_unilend->type                   = 2; // remb unilend
                                $this->bank_unilend->id_echeance_emprunteur = $RembEmpr['id_echeancier_emprunteur'];
                                $this->bank_unilend->status                 = 1;
                                $this->bank_unilend->create();

                                // MAIL FACTURE REMBOURSEMENT EMPRUNTEUR //
                                $projects                = $this->loadData('projects');
                                $companies               = $this->loadData('companies');
                                $emprunteur              = $this->loadData('clients');
                                $projects_status_history = $this->loadData('projects_status_history');

                                $projects->get($e['id_project'], 'id_project');
                                $companies->get($projects->id_company, 'id_company');
                                $emprunteur->get($companies->id_client_owner, 'id_client');

                                $dateRemb = $projects_status_history->select('id_project = ' . $projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')');
                                $timeAdd  = strtotime($dateRemb[0]['added']);
                                $month    = $this->dates->tableauMois['fr'][date('n', $timeAdd)];
                                $dateRemb = date('d', $timeAdd) . ' ' . $month . ' ' . date('Y', $timeAdd);

                                $varMail = array(
                                    'surl'            => $this->surl,
                                    'url'             => $this->furl,
                                    'prenom'          => $emprunteur->prenom,
                                    'pret'            => $this->ficelle->formatNumber($projects->amount),
                                    'entreprise'      => stripslashes(trim($companies->name)),
                                    'projet-title'    => $projects->title,
                                    'compte-p'        => $this->furl,
                                    'projet-p'        => $this->furl . '/projects/detail/' . $projects->slug,
                                    'link_facture'    => $this->furl . '/pdf/facture_ER/' . $emprunteur->hash . '/' . $e['id_project'] . '/' . $e['ordre'],
                                    'datedelafacture' => $dateRemb,
                                    'mois'            => strtolower($this->dates->tableauMois['fr'][date('n')]),
                                    'annee'           => date('Y'),
                                    'montantRemb'     => $this->ficelle->formatNumber($rembNetTotal),
                                    'lien_fb'         => $lien_fb,
                                    'lien_tw'         => $lien_tw
                                );

                                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('facture-emprunteur-remboursement', $varMail);
                                $message->setTo(trim($companies->email_facture));
                                $mailer = $this->get('mailer');
                                $mailer->send($message);

                                $oInvoiceCounter            = $this->loadData('compteur_factures');
                                $oLenderRepaymentSchedule   = $this->loadData('echeanciers');
                                $oBorrowerRepaymentSchedule = $this->loadData('echeanciers_emprunteur');
                                $oInvoice                   = $this->loadData('factures');

                                $this->settings->get('Commission remboursement', 'type');
                                $fCommissionRate = $this->settings->value;

                                $aLenderRepayment = $oLenderRepaymentSchedule->select('id_project = ' . $projects->id_project . ' AND ordre = ' . $e['ordre'], '', 0, 1);

                                if ($oBorrowerRepaymentSchedule->get($projects->id_project, 'ordre = ' . $e['ordre'] . '  AND id_project')) {
                                    $oInvoice->num_facture     = 'FR-E' . date('Ymd', strtotime($aLenderRepayment[0]['date_echeance_reel'])) . str_pad($oInvoiceCounter->compteurJournalier($projects->id_project, $aLenderRepayment[0]['date_echeance_reel']), 5, '0', STR_PAD_LEFT);
                                    $oInvoice->date            = $aLenderRepayment[0]['date_echeance_reel'];
                                    $oInvoice->id_company      = $companies->id_company;
                                    $oInvoice->id_project      = $projects->id_project;
                                    $oInvoice->ordre           = $e['ordre'];
                                    $oInvoice->type_commission = \factures::TYPE_COMMISSION_REMBOURSEMENT;
                                    $oInvoice->commission      = $fCommissionRate * 100;
                                    $oInvoice->montant_ht      = $oBorrowerRepaymentSchedule->commission;
                                    $oInvoice->tva             = $oBorrowerRepaymentSchedule->tva;
                                    $oInvoice->montant_ttc     = $oBorrowerRepaymentSchedule->commission + $oBorrowerRepaymentSchedule->tva;
                                    $oInvoice->create();
                                }

                                $_SESSION['freeow']['title']   = 'Remboursement prêteur';
                                $_SESSION['freeow']['message'] = 'Les preteurs ont bien &eacute;t&eacute; rembours&eacute; !';
                            } else {
                                //En cas de double Echeance en attente, si on traite la premiere la deuxieme sera a rembNetTotal = 0
                                if (! $deja_passe) {
                                    $_SESSION['freeow']['title']   = 'Remboursement prêteur';
                                    $_SESSION['freeow']['message'] = "Aucun remboursement n'a &eacute;t&eacute; effectu&eacute; aux preteurs !";
                                }
                            }
                            if (0 < $commission) {
                                /** @var platform_account_unilend $oAccountUnilend */
                                $oAccountUnilend = $this->loadData('platform_account_unilend');
                                $oAccountUnilend->addDueDateCommssion($RembEmpr['id_echeancier_emprunteur']);
                            }
                        }
                    }

                    $lesRembEmprun = $this->bank_unilend->select('type = 1 AND status = 0 AND id_project = ' . $this->projects->id_project, 'id_unilend ASC', 0, 1); // on ajoute la restriction pour BT 17882

                    foreach ($lesRembEmprun as $r) {
                        $this->bank_unilend->get($r['id_unilend'], 'id_unilend');
                        $this->bank_unilend->status = 1;
                        $this->bank_unilend->update();
                    }

                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                    $oProjectManager                     = $this->get('unilend.service.project_manager');
                    // si le projet etait en statut Recouvrement/probleme on le repasse en remboursement  || $this->projects_status->status == 100
                    if ($this->projects_status->status == \projects_status::RECOUVREMENT) {
                        $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::REMBOURSEMENT, $this->projects);
                    }

                    $settingsControleRemb->value = 1;
                    $settingsControleRemb->update();
                } else {
                    $_SESSION['freeow']['title']   = 'Remboursement prêteur';
                    $_SESSION['freeow']['message'] = 'Impossible de rembourser les prêteurs, un remboursement automatique est en cours';
                }
                header('Location: ' . $this->lurl . '/dossiers/detail_remb/' . $this->params[0]);
                die;
            }

            // Early repayment
            if (isset($_POST['spy_remb_anticipe']) && $_POST['id_reception'] > 0 && isset($_POST['id_reception'])) {
                $id_reception        = $_POST['id_reception'];
                $montant_crd_preteur = (int) ($_POST['montant_crd_preteur'] * 100);

                $this->projects                      = $this->loadData('projects');
                $this->echeanciers                   = $this->loadData('echeanciers');
                $this->receptions                    = $this->loadData('receptions');
                $this->echeanciers_emprunteur        = $this->loadData('echeanciers_emprunteur');
                $this->transactions                  = $this->loadData('transactions');
                $this->lenders_accounts              = $this->loadData('lenders_accounts');
                $this->clients                       = $this->loadData('clients');
                $this->wallets_lines                 = $this->loadData('wallets_lines');
                $this->mail_template                 = $this->loadData('mail_template');
                $this->companies                     = $this->loadData('companies');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                $oProjectManager                     = $this->get('unilend.service.project_manager');

                $this->receptions->get($id_reception);
                $this->projects->get($this->receptions->id_project);
                $this->companies->get($this->projects->id_company, 'id_company');

                // on fait encore un dernier controle sur le montant
                if ($montant_crd_preteur == $this->receptions->montant) {
                    $this->bdd->query('
                        UPDATE echeanciers_emprunteur SET
                            status_emprunteur = 1,
                            status_ra = 1,
                            updated = NOW(),
                            date_echeance_emprunteur_reel = NOW()
                        WHERE id_project = ' . $this->projects->id_project . ' AND status_emprunteur = 0'
                    );
                    $this->bdd->query('
                        UPDATE echeanciers SET
                            status_emprunteur = 1,
                            updated = NOW(),
                            status_ra = 1,
                            date_echeance_emprunteur_reel = NOW()
                        WHERE id_project = ' . $this->projects->id_project . ' AND status_emprunteur = 0'
                    );

                    $this->prelevements = $this->loadData('prelevements');
                    $this->prelevements->delete($this->projects->id_project, 'type_prelevement = 1 AND type = 2 AND status = 0 AND id_project');

                    /** @var \remboursement_anticipe_mail_a_envoyer $earlyRepaymentEmail */
                    $earlyRepaymentEmail               = $this->loadData('remboursement_anticipe_mail_a_envoyer');
                    $earlyRepaymentEmail->id_reception = $id_reception;
                    $earlyRepaymentEmail->statut       = 0;
                    $earlyRepaymentEmail->create();

                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::REMBOURSEMENT_ANTICIPE, $this->projects);

                    $montant_total = 0;

                    foreach ($this->echeanciers->get_liste_preteur_on_project($this->projects->id_project) as $preteur) {
                        $reste_a_payer_pour_preteur = $this->echeanciers->getSumRestanteARembByProject_capital(' AND id_lender =' . $preteur['id_lender'] . ' AND id_loan = ' . $preteur['id_loan'] . ' AND status = 0 AND id_project = ' . $this->projects->id_project);

                        $this->lenders_accounts->get($preteur['id_lender'], 'id_lender_account');
                        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

                        // On enregistre la transaction
                        $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                        $this->transactions->montant          = ($reste_a_payer_pour_preteur * 100);
                        $this->transactions->id_echeancier    = 0; // pas d'id_echeance car multiple
                        $this->transactions->id_loan_remb     = $preteur['id_loan']; // <-------------- on met ici pour retrouver la jointure
                        $this->transactions->id_project       = $this->projects->id_project;
                        $this->transactions->id_langue        = 'fr';
                        $this->transactions->date_transaction = date('Y-m-d H:i:s');
                        $this->transactions->status           = '1';
                        $this->transactions->etat             = '1';
                        $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                        $this->transactions->type_transaction = 23; // remb anticipe preteur
                        $this->transactions->transaction      = 2; // transaction virtuelle
                        $this->transactions->id_transaction   = $this->transactions->create();

                        // on enregistre la transaction dans son wallet
                        $this->wallets_lines->id_lender                = $preteur['id_lender'];
                        $this->wallets_lines->type_financial_operation = 40;
                        $this->wallets_lines->id_loan                  = $preteur['id_loan']; // <-------------- on met ici pour retrouver la jointure
                        $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                        $this->wallets_lines->status                   = 1; // non utilisé
                        $this->wallets_lines->type                     = 2; // transaction virtuelle
                        $this->wallets_lines->amount                   = ($reste_a_payer_pour_preteur * 100);
                        $this->wallets_lines->id_wallet_line           = $this->wallets_lines->create();

                        $montant_total += $reste_a_payer_pour_preteur;
                    }

                    $this->bdd->query('
                        UPDATE echeanciers SET
                            status = 1,
                            updated = NOW(),
                            date_echeance_reel = NOW(),
                            date_echeance_emprunteur_reel = NOW(),
                            status_email_remb = 1
                        WHERE id_project = ' . $this->projects->id_project . ' AND status = 0'
                    );

                    // partie a retirer de bank unilend
                    // On evite de créer une ligne qui sert a rien
                    if ($montant_total != 0) {
                        // On enregistre la transaction
                        $this->transactions->montant                  = 0;
                        $this->transactions->id_echeancier            = 0; // on reinitialise
                        $this->transactions->id_client                = 0; // on reinitialise
                        $this->transactions->montant_unilend          = '-' . $montant_total * 100;
                        $this->transactions->montant_etat             = 0 * 100; // pas d'argent pour l'état
                        $this->transactions->id_echeancier_emprunteur = 0; // pas d'echeance emprunteur
                        $this->transactions->id_langue                = 'fr';
                        $this->transactions->date_transaction         = date('Y-m-d H:i:s');
                        $this->transactions->status                   = '1';
                        $this->transactions->etat                     = '1';
                        $this->transactions->ip_client                = $_SERVER['REMOTE_ADDR'];
                        $this->transactions->type_transaction         = 10; // remb unilend pour les preteurs
                        $this->transactions->transaction              = 2; // transaction virtuelle
                        $this->transactions->id_loan_remb             = 0;
                        $this->transactions->id_project               = $this->projects->id_project;
                        $this->transactions->create();

                        // bank_unilend (on retire l'argent redistribué)
                        $this->bank_unilend->id_transaction         = $this->transactions->id_transaction;
                        $this->bank_unilend->id_project             = $this->projects->id_project;
                        $this->bank_unilend->montant                = '-' . $montant_total * 100;
                        $this->bank_unilend->etat                   = 0; // pas d'argent pour l'état
                        $this->bank_unilend->type                   = 2; // remb unilend
                        $this->bank_unilend->id_echeance_emprunteur = 0; // pas d'echeance emprunteur
                        $this->bank_unilend->status                 = 1;
                        $this->bank_unilend->create();
                    }

                    $lesRembEmprun = $this->bank_unilend->select('type = 1 AND status = 0 AND id_project = ' . $this->projects->id_project, 'id_unilend ASC'); // on ajoute la restriction pour BT 17882

                    // On parcourt les remb non reversé aux preteurs dans bank unilend et on met a jour le satut pour dire que c'est remb
                    foreach ($lesRembEmprun as $r) {
                        $this->bank_unilend->get($r['id_unilend'], 'id_unilend');
                        $this->bank_unilend->status = 1;
                        $this->bank_unilend->update();
                    }

                    header('Location: ' . $this->lurl . '/dossiers/detail_remb/' . $this->projects->id_project);
                    die;
                }
            }

            $this->recup_info_remboursement_anticipe($this->projects->id_project);
        }
    }

    public function _detail_remb_preteur()
    {
        $this->clients          = $this->loadData('clients');
        $this->loans            = $this->loadData('loans');
        $this->echeanciers      = $this->loadData('echeanciers');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->projects         = $this->loadData('projects');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {

            $this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);

            $this->tauxMoyen = $this->loans->getAvgLoans($this->projects->id_project, 'rate');

            $montantHaut = 0;
            $montantBas  = 0;
            // si fundé ou remboursement

            foreach ($this->loans->select('id_project = ' . $this->projects->id_project) as $b) {
                $montantHaut += ($b['rate'] * ($b['amount'] / 100));
                $montantBas += ($b['amount'] / 100);
            }
            $this->tauxMoyen = ($montantHaut / $montantBas);

            // liste des echeances emprunteur par mois
            $lRembs = $this->echeanciers->getSumRembEmpruntByMonths($this->projects->id_project);

            $this->montant     = 0;
            $this->MontantRemb = 0;

            foreach ($lRembs as $r) {
                $this->montant += $r['montant'];

                $this->MontantRemb += $this->echeanciers->getMontantRembEmprunteur($r['montant'], $r['commission'], $r['tva']);
            }

            $this->lLenders = $this->loans->select('id_project = ' . $this->projects->id_project, 'rate ASC');
        }
    }

    public function _detail_echeance_preteur()
    {
        $this->clients                 = $this->loadData('clients');
        $this->loans                   = $this->loadData('loans');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->lenders_accounts        = $this->loadData('lenders_accounts');
        $this->projects                = $this->loadData('projects');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->receptions              = $this->loadData('receptions');

        $this->lRemb = $this->echeanciers->select('id_loan = ' . $this->params[1] . ' AND status_ra = 0', 'ordre ASC');

        // on check si on est en remb anticipé
        // ON recup la date de statut remb
        $dernierStatut = $this->projects_status_history->select('id_project = ' . $this->params[0], 'id_project_status_history DESC', 0, 1);

        $this->projects_status->get(\projects_status::REMBOURSEMENT_ANTICIPE, 'status');

        if ($dernierStatut[0]['id_project_status'] == $this->projects_status->id_project_status) {
            //récupération du montant de la transaction du CRD pour afficher la ligne en fin d'échéancier
            $this->montant_ra = $this->echeanciers->sum('id_project = ' . $this->params[0] . ' AND status_ra = 1 AND status = 1 AND id_loan = ' . $this->params[1], 'capital');
            $this->date_ra    = $dernierStatut[0]['added'];
        }
    }

    public function _echeancier_emprunteur()
    {
        $this->clients                 = $this->loadData('clients');
        $this->echeanciers_emprunteur  = $this->loadData('echeanciers_emprunteur');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->projects                = $this->loadData('projects');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->receptions              = $this->loadData('receptions');
        $this->prelevements            = $this->loadData('prelevements');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            // liste des echeances emprunteur par mois
            $this->lRemb = $this->echeanciers_emprunteur->select('id_project = ' . $this->projects->id_project . ' AND status_ra = 0', 'ordre ASC');

            $this->montantPreteur    = 0;
            $this->MontantEmprunteur = 0;
            $this->commission        = 0;
            $this->comParMois        = 0;
            $this->comTtcParMois     = 0;
            $this->tva               = 0;
            $this->totalTva          = 0;
            $this->capital           = 0;

            foreach ($this->lRemb as $r) {
                $this->montantPreteur += $r['montant'];
                $this->MontantEmprunteur += $this->echeanciers->getMontantRembEmprunteur($r['montant'], $r['commission'], $r['tva']);
                $this->commission += $r['commission'];
                $this->comParMois    = $r['commission'];
                $this->comTtcParMois = $r['commission'] + $r['tva'];
                $this->tva           = $r['tva'];
                $this->totalTva += $r['tva'];

                $this->capital += $r['capital'];
            }
            // on check si on est en remb anticipé
            // ON recup la date de statut remb
            $dernierStatut    = $this->projects_status_history->select('id_project = ' . $this->projects->id_project, 'id_project_status_history DESC', 0, 1);
            $this->montant_ra = 0;

            $this->projects_status->get(\projects_status::REMBOURSEMENT_ANTICIPE, 'status');

            if ($dernierStatut[0]['id_project_status'] == $this->projects_status->id_project_status) {
                //récupération du montant de la transaction du CRD pour afficher la ligne en fin d'échéancier
                $this->receptions->get($this->projects->id_project, 'type_remb = ' . \receptions::REPAYMENT_TYPE_EARLY . ' AND status_virement = 1 AND type = 2 AND id_project');
                $this->montant_ra = ($this->receptions->montant / 100);
                $this->date_ra    = $dernierStatut[0]['added'];

                //on ajoute ce qu'il reste au capital restant
                $this->capital += ($this->montant_ra * 100);
            }
        }
    }

    //utilisé pour récup les infos affichées dans le cadre
    private function recup_info_remboursement_anticipe($id_project)
    {
        $this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $this->echeanciers            = $this->loadData('echeanciers');
        $oBusinessDays                = $this->loadLib('jours_ouvres');

        //Récupération de la date theorique de remb ( ON AJOUTE ICI LA ZONE TAMPON DE 3 JOURS APRES LECHEANCE)
        $aLastOrder             = $this->echeanciers->getLastOrder($id_project);
        $iOrderEarlyRefund      = isset($aLastOrder['ordre']) ? $aLastOrder['ordre'] + 1 : 1;
        $sLastOrderDate         = $aLastOrder['date_echeance'];
        $iLastOrderDate         = strtotime($sLastOrderDate);
        $sBusinessDaysOrderDate = "";

        // Date 4 jours ouvrés avant $sLastOrderDate
        if ($iLastOrderDate != "" && isset($iLastOrderDate)) {
            $sBusinessDaysOrderDate = $oBusinessDays->display_jours_ouvres($iLastOrderDate, 4);
        }

        if (false === empty($aLastOrder)) {
            // on check si la date limite est pas déjà dépassé. Si oui on prend la prochaine echeance
            if ($sBusinessDaysOrderDate <= time()) {
                // Dans ce cas, on connait donc déjà la derniere echeance qui se déroulera normalement
                $this->date_derniere_echeance_normale = $this->dates->formatDateMysqltoFr_HourOut($aLastOrder['date_echeance']);

                // on va recup la date de la derniere echeance qui suit le process de base
                $aNextEcheance = $this->echeanciers->select(" id_project = " . $id_project . "
                    AND DATE_ADD(date_echeance, INTERVAL 3 DAY) > NOW()
                    AND id_lender = (SELECT id_lender
                    FROM echeanciers where id_project = " . $id_project . " LIMIT 1)
                    AND ordre = " . ($iOrderEarlyRefund + 1), 'ordre ASC', 0, 1);

                if (count($aNextEcheance) > 0) {
                    // on refait le meme process pour la nouvelle date
                    $aLastOrder             = $aNextEcheance[0];
                    $sLastOrderDate         = $aLastOrder['date_echeance'];
                    $iLastOrderDate         = strtotime($sLastOrderDate);
                    $sBusinessDaysOrderDate = $oBusinessDays->display_jours_ouvres($iLastOrderDate, 4);
                } else {
                    $this->date_next_echeance_4jouvres_avant = "Aucune &eacute;ch&eacute;ance &agrave; venir dans le futur";
                }
            } else {
                // on va recup la date de la derniere echeance qui suit le process de base
                $aRepaymentSchedule                   = $this->echeanciers->select(' id_project = ' . $id_project . ' AND ordre = ' . ($iOrderEarlyRefund + 1), 'ordre ASC', 0, 1);
                $this->date_derniere_echeance_normale = $this->dates->formatDateMysqltoFr_HourOut($aRepaymentSchedule[0]['date_echeance']);
            }
        }

        if (false === empty($sBusinessDaysOrderDate)) {
            $this->date_next_echeance_4jouvres_avant = date('d/m/Y', $sBusinessDaysOrderDate);
            $this->date_next_echeance                = $this->dates->formatDateMysqltoFr_HourOut($sLastOrderDate);
        }

        $this->montant_restant_du_emprunteur = $this->echeanciers_emprunteur->reste_a_payer_ra($id_project, $iOrderEarlyRefund);
        $this->montant_restant_du_preteur    = $this->echeanciers->reste_a_payer_ra($id_project, $iOrderEarlyRefund);
        $resultat_num                        = $this->montant_restant_du_preteur - $this->montant_restant_du_emprunteur;

        $this->ordre_echeance_ra = $iOrderEarlyRefund;

        $this->projects_status_history = $this->loadData('projects_status_history');
        $statut_projet                 = $this->projects_status_history->select('id_project = ' . $id_project, 'id_project_status_history DESC', 0, 1);

        $oEarlyRefundStatus = $this->loadData('projects_status');
        $oEarlyRefundStatus->get(\projects_status::REMBOURSEMENT_ANTICIPE, 'status');

        $this->remb_anticipe_effectue = false;

        if ($statut_projet[0]['id_project_status'] == $oEarlyRefundStatus->id_project_status) {
            $this->phrase_resultat        = "<div style='color:green;'>Remboursement anticip&eacute; effectu&eacute;</div>";
            $this->remb_anticipe_effectue = true;
        } else {
            if ($resultat_num == 0) {
                $this->phrase_resultat = "<div style='color:green;'>Remboursement possible</div>";
            } elseif ($resultat_num < 0) { // si emprunteur doit plus que les prets ==> Orange non bloquant
                $this->phrase_resultat = "<div style='color:orange;'>Remboursement possible <br />(CRD Pr&ecirc;teurs :" . $this->montant_restant_du_preteur . "€ - CRD Emprunteur :" . $this->montant_restant_du_emprunteur . "€)</div>";
            } elseif ($resultat_num > 0) { // si preteurs doivent plus que les emprunteurs ==> rouge bloquant
                $this->phrase_resultat = "<div style='color:red;'>Remboursement impossible <br />(CRD Pr&ecirc;teurs :" . $this->montant_restant_du_preteur . "€ - CRD Emprunteur :" . $this->montant_restant_du_emprunteur . "€)</div>";
            }
        }

        // on verifie si on a recu un virement anticipé pour ce projet
        $this->receptions = $this->loadData('receptions');
        $L_vrmt_anticipe  = $this->receptions->select('id_project = ' . $this->projects->id_project . ' AND status_bo IN(1, 2) AND type_remb = ' . \receptions::REPAYMENT_TYPE_EARLY . ' AND type = 2 AND status_virement = 1');

        $this->virement_recu = false;

        if (count($L_vrmt_anticipe) == 1 && $statut_projet[0]['id_project_status'] != $oEarlyRefundStatus->id_project_status) {
            $this->virement_recu    = true;
            $this->virement_recu_ok = false;

            $this->receptions->get($L_vrmt_anticipe[0]['id_reception']);
            //on check si on a toujours le montant emprunteur Vs Preteur est toujours identique et si le virement recu est égal à ce qu'on doit
            if ($resultat_num == 0 && ($this->receptions->montant / 100) >= $this->montant_restant_du_preteur) {
                $this->virement_recu_ok = true;
                $this->phrase_resultat  = "<div style='color:green;'>Virement re&ccedil;u conforme</div>";
            } elseif (($this->receptions->montant / 100) < $this->montant_restant_du_preteur) {
                $this->phrase_resultat = "<div style='color:red;'>Virement re&ccedil;u - Probl&egrave;me montant <br />(CRD Pr&ecirc;teurs :" . $this->montant_restant_du_preteur . "€ - Virement :" . ($this->receptions->montant / 100) . "€)</div>";
            }
        }

        // on check si les échéances avant le RA sont toutes payées - si on trouve quelque chose on bloque le RA
        $L_echeance_avant            = $this->echeanciers->select(" id_project = " . $id_project . " AND status = 0 AND ordre < " . $this->ordre_echeance_ra);
        $this->ra_possible_all_payed = true;
        if (count($L_echeance_avant) > 0) {
            $this->phrase_resultat       = "<div style='color:red;'>Remboursement impossible <br />Toutes les &eacute;ch&eacute;ances pr&eacute;c&eacute;dentes ne sont pas rembours&eacute;es</div>";
            $this->ra_possible_all_payed = false;
        }
    }

    public function _send_cgv_ajax()
    {
        $this->hideDecoration();

        $oClients    = $this->loadData('clients');
        $oProjects   = $this->loadData('projects');
        $oCompanies  = $this->loadData('companies');
        $oProjectCgv = $this->loadData('project_cgv');
        $oSettings   = $this->loadData('settings');

        if (false === isset($this->params[0]) || ! $oProjects->get($this->params[0], 'id_project')) {
            $this->result = 'project id invalid';
            return;
        }
        if (! $oCompanies->get($oProjects->id_company, 'id_company')) {
            $this->result = 'company id invalid';
            return;
        }
        if (! $oClients->get($oCompanies->id_client_owner, 'id_client')) {
            $this->result = 'client id invalid';
            return;
        }

        // @todo intl - for the moment, we use language but real value must be a country code
        if (false === $this->ficelle->isMobilePhoneNumber($oClients->telephone, $this->language)) {
            $this->result = 'Le numéro de téléphone du dirigeant n\'est pas un numéro de portable';
            return;
        }

        if ($oProjectCgv->get($oProjects->id_project, 'id_project')) {
            if (empty($oProjectCgv->id_tree)) {
                $oSettings->get('Lien conditions generales depot dossier', 'type');
                $iTreeId = $oSettings->value;

                if (! $iTreeId) {
                    $this->result = 'tree id invalid';
                    return;
                }

                $oProjects->id_tree = $iTreeId;

            }
            $sCgvLink = $this->surl . $oProjectCgv->getUrlPath();

            if (empty($oProjectCgv->name)) {
                $oProjectCgv->name = $oProjectCgv->generateFileName();
            }
            $oProjectCgv->update();
        } else {
            $oSettings->get('Lien conditions generales depot dossier', 'type');
            $iTreeId = $oSettings->value;

            if (! $iTreeId) {
                $this->result = 'tree id invalid';
                return;
            }

            $oProjectCgv->id_project = $oProjects->id_project;
            $oProjectCgv->id_tree    = $iTreeId;
            $oProjectCgv->name       = $oProjectCgv->generateFileName();
            $oProjectCgv->status     = project_cgv::STATUS_NO_SIGN;
            $oProjectCgv->id         = $oProjectCgv->create();
            $sCgvLink                = $this->surl . $oProjectCgv->getUrlPath();
        }

        // Recuperation du pdf du tree
        $elements = $this->tree_elements->select('id_tree = "' . $oProjectCgv->id_tree . '" AND id_element = ' . \elements::TYPE_PDF_CGU . ' AND id_langue = "' . $this->language . '"');

        if (false === isset($elements[0]['value']) || '' == $elements[0]['value']) {
            $this->result = 'element id invalid';
            return;
        }
        $sPdfPath = $this->path . 'public/default/var/fichiers/' . $elements[0]['value'];

        if (false === file_exists($sPdfPath)) {
            $this->result = 'file not found';
            return;
        }

        if (false === is_dir($this->path . project_cgv::BASE_PATH)) {
            mkdir($this->path . project_cgv::BASE_PATH);
        }
        if (false === file_exists($this->path . project_cgv::BASE_PATH . $oProjectCgv->name)) {
            copy($sPdfPath, $this->path . project_cgv::BASE_PATH . $oProjectCgv->name);
        }

        $oSettings->get('Facebook', 'type');
        $lien_fb = $oSettings;

        $oSettings->get('Twitter', 'type');
        $lien_tw = $oSettings;

        $varMail = array(
            'surl'                => $this->surl,
            'url'                 => $this->furl,
            'prenom_p'            => $oClients->prenom,
            'lien_cgv_universign' => $sCgvLink,
            'lien_tw'             => $lien_tw,
            'lien_fb'             => $lien_fb,
        );

        if (empty($oClients->email)) {
            $this->result = 'Erreur : L\'adresse mail du client est vide';
            return;
        }

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('signature-universign-de-cgv', $varMail);
        $message->setTo($oClients->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);

        $this->result = 'CGV envoyées avec succès';
    }

    public function _completude_preview()
    {
        $this->hideDecoration();

        /** @var projects $oProjects */
        $oProjects = $this->loadData('projects');
        /** @var clients $oClients */
        $oClients = $this->loadData('clients');

        if (false === isset($this->params[0]) || false === $oProjects->get($this->params[0])) {
            $this->error = 'no projects found';
            return;
        }
        /** @var companies $oCompanies */
        $oCompanies = $this->loadData('companies');
        if (false === $oCompanies->get($oProjects->id_company)) {
            $this->error = 'no company found';
            return;
        }

        $iClientId = null;
        if ($oProjects->id_prescripteur) {
            /** @var prescripteurs $oPrescripteurs */
            $oPrescripteurs = $this->loadData('prescripteurs');
            if ($oPrescripteurs->get($oProjects->id_prescripteur)) {
                $iClientId = $oPrescripteurs->id_client;
            }
        } else {
            $iClientId = $oCompanies->id_client_owner;
        }

        if ($iClientId && $oClients->get($iClientId) && $oClients->email) {
            $this->sRecipient = $oClients->email;
        } else {
            $this->error = 'no client email found';
            return;
        }
        $this->iClientId  = $iClientId;
        $this->iProjectId = $oProjects->id_project;

        $sTypeEmail = $this->selectEmailCompleteness($iClientId);
        $this->mail_template->get($sTypeEmail, 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');
    }

    public function _completude_preview_iframe()
    {
        $this->hideDecoration();

        /** @var projects $oProjects */
        $oProjects = $this->loadData('projects');
        /** @var clients $oClients */
        $oClients = $this->loadData('clients');
        /** @var companies $oCompanies */
        $oCompanies = $this->loadData('companies');
        /** @var \mail_templates $oMailTemplate */
        $oMailTemplate = $this->loadData('mail_templates');

        if (false === isset($this->params[0]) || false === $oProjects->get($this->params[0])) {
            echo 'no projects found';
            return;
        }

        if (false === isset($this->params[1]) || false === $oClients->get($this->params[1])) {
            echo 'no clients found';
            return;
        }

        if (false === $oCompanies->get($oProjects->id_company)) {
            echo 'no company found';
            return;
        }

        $sTypeEmail = $this->selectEmailCompleteness($oClients->id_client);
        $oMailTemplate->get($sTypeEmail, 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

        $varMail          = $this->getEmailVarCompletude($oProjects, $oClients, $oCompanies);
        $varMail['sujet'] = $oMailTemplate->subject;

        $tabVars = array();
        foreach ($varMail as $key => $value) {
            $tabVars['[EMV DYN]' . $key . '[EMV /DYN]'] = $value;
        }

        echo strtr($oMailTemplate->content, $tabVars);
    }

    public function _send_completude()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        if ($_POST['send_completude']) {
            /** @var projects $oProjects */
            $oProjects = $this->loadData('projects');
            /** @var clients $oClients */
            $oClients = $this->loadData('clients');
            /** @var companies $oCompanies */
            $oCompanies = $this->loadData('companies');
            /** @var \mail_templates $oMailTemplate */
            $oMailTemplate = $this->loadData('mail_templates');

            if (false === isset($_POST['id_project']) || false === $oProjects->get($_POST['id_project'])) {
                echo 'no projects found';
                return;
            }

            if (false === isset($_POST['id_client']) || false === $oClients->get($_POST['id_client'])) {
                echo 'no clients found';
                return;
            }

            if (false === $oCompanies->get($oProjects->id_company)) {
                echo 'no company found';
                return;
            }
            $sTypeEmail       = $this->selectEmailCompleteness($oClients->id_client);
            $oMailTemplate->get($sTypeEmail, 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');
            $varMail          = $this->getEmailVarCompletude($oProjects, $oClients, $oCompanies);
            $varMail['sujet'] = htmlentities($oMailTemplate->subject, null, 'UTF-8');
            $sRecipientEmail  = preg_replace('/^(.*)-[0-9]+$/', '$1', trim($oClients->email));

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($sTypeEmail, $varMail);
            $message->setTo($sRecipientEmail);
            $mailer = $this->get('mailer');
            $mailer->send($message);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
            $oProjectManager = $this->get('unilend.service.project_manager');
            $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::EN_ATTENTE_PIECES, $oProjects, 1, $varMail['liste_pieces']);

            unset($_SESSION['project_submission_files_list'][$oProjects->id_project]);

            echo 'Votre email a été envoyé';
        }
    }

    private function getEmailVarCompletude($oProjects, $oClients, $oCompanies)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->loadData('settings');

        $oSettings->get('Facebook', 'type');
        $lien_fb = $oSettings->value;

        $oSettings->get('Twitter', 'type');
        $lien_tw = $oSettings->value;

        $oSettings->get('Adresse emprunteur', 'type');
        $sBorrowerEmail = $oSettings->value;

        $oSettings->get('Téléphone emprunteur', 'type');
        $sBorrowerPhoneNumber = $oSettings->value;

        $oTemporaryLink = $this->loadData('temporary_links_login');

        return array(
            'furl'                   => $this->furl,
            'surl'                   => $this->surl,
            'adresse_emprunteur'     => $sBorrowerEmail,
            'telephone_emprunteur'   => $sBorrowerPhoneNumber,
            'prenom'                 => $oClients->prenom,
            'raison_sociale'         => $oCompanies->name,
            'lien_reprise_dossier'   => $this->furl . '/depot_de_dossier/fichiers/' . $oProjects->hash,
            'liste_pieces'           => isset($_SESSION['project_submission_files_list'][ $oProjects->id_project ]) ? $_SESSION['project_submission_files_list'][ $oProjects->id_project ] : '',
            'lien_fb'                => $lien_fb,
            'lien_tw'                => $lien_tw,
            'lien_stop_relance'      => $this->furl . '/depot_de_dossier/emails/' . $oProjects->hash,
            'link_compte_emprunteur' => $this->surl . '/espace_emprunteur/securite/' . $oTemporaryLink->generateTemporaryLink($oClients->id_client)
        );
    }

    /**
     * @param integer $iOwnerId
     * @param integer $field
     * @param integer $iAttachmentType
     * @return bool
     */
    private function uploadAttachment($iOwnerId, $field, $iAttachmentType)
    {
        if (false === isset($this->upload) || false === $this->upload instanceof upload) {
            $this->upload = $this->loadLib('upload');
        }

        if (false === isset($this->attachment) || false === $this->attachment instanceof attachment) {
            $this->attachment = $this->loadData('attachment');
        }

        if (false === isset($this->attachment_type) || false === $this->attachment_type instanceof attachment_type) {
            $this->attachment_type = $this->loadData('attachment_type');
        }

        if (false === isset($this->attachmentHelper) || false === $this->attachmentHelper instanceof attachment_helper) {
            $this->attachmentHelper = $this->loadLib('attachment_helper', array($this->attachment, $this->attachment_type, $this->path));;
        }

        //add the new name for each file
        $sNewName = '';
        if (isset($_FILES[$field]['name']) && $aFileInfo = pathinfo($_FILES[$field]['name'])) {
            $sNewName = $aFileInfo['filename'] . '_' . $iOwnerId;
        }

        $resultUpload = $this->attachmentHelper->upload($iOwnerId, attachment::PROJECT, $iAttachmentType, $field, $this->upload, $sNewName);

        return $resultUpload;
    }

    /**
     * @param $iAttachmentId
     *
     * @return mixed
     */
    private function removeAttachment($iAttachmentId)
    {
        if (false === isset($this->attachment) || false === $this->attachment instanceof attachment) {
            $this->attachment = $this->loadData('attachment');
        }

        if (false === isset($this->attachment_type) || false === $this->attachment_type instanceof attachment_type) {
            $this->attachment_type = $this->loadData('attachment_type');
        }

        if (false === isset($this->attachmentHelper) || false === $this->attachmentHelper instanceof attachment_helper) {
            $this->attachmentHelper = $this->loadLib('attachment_helper', array($this->attachment, $this->attachment_type, $this->path));
        }

        return $this->attachmentHelper->remove($iAttachmentId);
    }

    private function selectEmailCompleteness($iClientId)
    {
        $oClients = $this->loadData('clients');
        $oClients->get($iClientId);

        if (isset($oClients->secrete_question, $oClients->secrete_reponse)) {
            return 'depot-dossier-relance-status-20-1';
        } else {
            return 'depot-dossier-relance-status-20-1-avec-mdp';
        }
    }

    private function sendEmailBorrowerArea($sTypeEmail)
    {
        $oSettings = $this->loadData('settings');
        $oSettings->get('Facebook', 'type');
        $sFacebookURL = $oSettings->value;
        $oSettings->get('Twitter', 'type');
        $sTwitterURL = $oSettings->value;

        /** @var \temporary_links_login $oTemporaryLink */
        $oTemporaryLink = $this->loadData('temporary_links_login');
        $sTemporaryLink = $this->surl . '/espace_emprunteur/securite/' . $oTemporaryLink->generateTemporaryLink($this->clients->id_client);

        $aVariables = array(
            'surl'                   => $this->surl,
            'url'                    => $this->url,
            'link_compte_emprunteur' => $sTemporaryLink,
            'lien_fb'                => $sFacebookURL,
            'lien_tw'                => $sTwitterURL,
            'prenom'                 => $this->clients->prenom
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($sTypeEmail, $aVariables);
        $message->setTo($this->clients->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    public function _status()
    {
        if (false === empty($_POST)) {
            $sURL = '/dossiers/status/' . $_POST['status'];

            if (false === empty($_POST['first-range-start']) && false === empty($_POST['first-range-end'])) {
                $oStart = new \DateTime(str_replace('/', '-', $_POST['first-range-start']));
                $oEnd   = new \DateTime(str_replace('/', '-', $_POST['first-range-end']));
                $sURL .= '/' . $oStart->format('Y-m-d') . '_' . $oEnd->format('Y-m-d');

                if (false === empty($_POST['second-range-start']) && false === empty($_POST['second-range-end'])) {
                    $oStart = new \DateTime(str_replace('/', '-', $_POST['second-range-start']));
                    $oEnd   = new \DateTime(str_replace('/', '-', $_POST['second-range-end']));
                    $sURL .= '/' . $oStart->format('Y-m-d') . '_' . $oEnd->format('Y-m-d');
                }
            }

            header('Location: ' . $sURL);
            exit;
        }

        $this->loadJs('admin/vis/vis.min');
        $this->loadCss('../scripts/admin/vis/vis.min');

        /** @var \projects_status $oProjectStatus */
        $oProjectStatus  = $this->loadData('projects_status');
        $this->aStatuses = $oProjectStatus->select('', 'status ASC');

        if (
            isset($this->params[0], $this->params[1])
            && $this->params[0] == (int) $this->params[0]
            && 1 === preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})_([0-9]{4}-[0-9]{2}-[0-9]{2})/', $this->params[1], $aMatches)
        ) {
            $this->iBaseStatus      = $this->params[0];
            $this->oFirstRangeStart = new \DateTime($aMatches[1]);
            $this->oFirstRangeEnd   = new \DateTime($aMatches[2]);

            $oProjectStatus->get($this->iBaseStatus);

            /** @var \projects_status_history $oProjectStatusHistory */
            $oProjectStatusHistory = $this->loadData('projects_status_history');
            $aBaseStatus           = $oProjectStatusHistory->getStatusByDates($this->iBaseStatus, $this->oFirstRangeStart, $this->oFirstRangeEnd);
            $this->aHistory        = array(
                'label'    => $aBaseStatus[0]['label'],
                'count'    => count($aBaseStatus),
                'status'   => $oProjectStatus->status,
                'children' => $this->getStatusChildren(array_column($aBaseStatus, 'id_project_status_history'))
            );

            foreach ($this->aHistory['children'] as $iChildStatus => &$aChild) {
                if ($iChildStatus > 0) {
                    $this->aHistory['children'][$iChildStatus]['children'] = $this->getStatusChildren($aChild['id_project_status_history']);
                }
            }

            if (isset($this->params[2]) && 1 === preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})_([0-9]{4}-[0-9]{2}-[0-9]{2})/', $this->params[2], $aMatches)) {
                $this->oSecondRangeStart = new \DateTime($aMatches[1]);
                $this->oSecondRangeEnd   = new \DateTime($aMatches[2]);
                $aBaseStatus             = $oProjectStatusHistory->getStatusByDates($this->iBaseStatus, $this->oSecondRangeStart, $this->oSecondRangeEnd);
                $this->aCompareHistory   = array(
                    'label'    => $aBaseStatus[0]['label'],
                    'count'    => count($aBaseStatus),
                    'status'   => $oProjectStatus->status,
                    'children' => $this->getStatusChildren(array_column($aBaseStatus, 'id_project_status_history'))
                );

                foreach ($this->aCompareHistory['children'] as $iChildStatus => &$aChild) {
                    if ($iChildStatus > 0) {
                        $this->aCompareHistory['children'][$iChildStatus]['children'] = $this->getStatusChildren($aChild['id_project_status_history']);
                    }
                }
            }
        }
    }

    private function getStatusChildren(array $aStatusHistory)
    {
        /** @var \projects_status_history $oProjectStatusHistory */
        $oProjectStatusHistory = $this->loadData('projects_status_history');
        $aChildrenStatus       = $oProjectStatusHistory->getFollowingStatus($aStatusHistory);
        $aStatus               = array();

        array_map(function ($aElement) use (&$aStatus) {
            if (false === isset($aStatus[$aElement['status']])) {
                $aStatus[$aElement['status']] = array(
                    'count'                     => 1,
                    'label'                     => $aElement['label'],
                    'max_date'                  => $aElement['added'],
                    'total_days'                => $aElement['diff_days'],
                    'id_project_status_history' => array($aElement['id_project_status_history'])
                );
            } else {
                $aStatus[$aElement['status']]['count']++;
                $aStatus[$aElement['status']]['total_days'] += $aElement['diff_days'];
                $aStatus[$aElement['status']]['id_project_status_history'][] = $aElement['id_project_status_history'];

                if ($aElement['added'] > $aStatus[$aElement['status']]['max_date']) {
                    $aStatus[$aElement['status']]['max_date'] = $aElement['added'];
                }
            }
        }, $aChildrenStatus);

        uasort($aStatus, function($aFirstElement, $aSecondElement) {
            if ($aFirstElement['count'] === $aSecondElement['count']) {
                return 0;
            }
            return $aFirstElement['count'] > $aSecondElement['count'] ? -1 : 1;
        });

        return array_map(function ($aStatus) {
            $aStatus['avg_days'] = round($aStatus['total_days'] / $aStatus['count'], 1);
            return $aStatus;
        }, $aStatus);
    }

    public function _autocompleteCompanyName()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $aNames = array();

        if ($sTerm = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING)) {
            /** @var \companies $oCompanies */
            $oCompanies = $this->loadData('companies');
            $aNames = $oCompanies->searchByName($sTerm);
        }

        echo json_encode($aNames);
    }
}
