<?php

use \Unilend\Bundle\CoreBusinessBundle\Service\TaxManager;
use \Psr\Log\LoggerInterface;
use \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;

class dossiersController extends bootstrap
{
    /** @var \projects_status */
    protected $projects_status;
    /** @var \projects_status_history */
    protected $projects_status_history;
    /** @var \projects_notes */
    protected $projects_notes;
    /** @var \project_cgv */
    protected $project_cgv;
    /** @var \companies_actif_passif */
    protected $companies_actif_passif;
    /** @var \company_balance */
    protected $company_balance;
    /** @var \company_balance_type */
    protected $company_balance_type;
    /** @var \companies_bilans */
    protected $companies_bilans;
    /** @var \clients_adresses */
    protected $clients_adresses;
    /** @var \projects_comments */
    protected $projects_comments;
    /** @var \projects_pouvoir */
    protected $projects_pouvoir;
    /** @var \notifications */
    protected $notifications;
    /** @var \clients_gestion_mails_notif */
    protected $clients_gestion_mails_notif;
    /** @var \clients_gestion_notifications */
    protected $clients_gestion_notifications;
    /** @var \prescripteurs */
    protected $prescripteurs;
    /** @var \clients */
    protected $clients_prescripteurs;
    /** @var \companies */
    protected $companies_prescripteurs;
    /** @var int Count project in searchDossiers */
    public $iCountProjects;
    /** @var bool block risk note and comments */
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
        $this->projects                      = $this->loadData('projects');
        $this->projects_status               = $this->loadData('projects_status');
        $this->projects_status_history       = $this->loadData('projects_status_history');
        $this->projects_notes                = $this->loadData('projects_notes');
        $this->project_cgv                   = $this->loadData('project_cgv');
        $this->companies                     = $this->loadData('companies');
        $this->companies_actif_passif        = $this->loadData('companies_actif_passif');
        $this->company_balance               = $this->loadData('company_balance');
        $this->company_balance_type          = $this->loadData('company_balance_type');
        $this->companies_bilans              = $this->loadData('companies_bilans');
        $this->clients                       = $this->loadData('clients');
        $this->clients_adresses              = $this->loadData('clients_adresses');
        $this->projects_comments             = $this->loadData('projects_comments');
        $this->loans                         = $this->loadData('loans');
        $this->projects_pouvoir              = $this->loadData('projects_pouvoir');
        $this->lenders_accounts              = $this->loadData('lenders_accounts');
        $this->echeanciers                   = $this->loadData('echeanciers');
        $this->notifications                 = $this->loadData('notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->prescripteurs                 = $this->loadData('prescripteurs');
        $this->clients_prescripteurs         = $this->loadData('clients');
        $this->companies_prescripteurs       = $this->loadData('companies');
        $this->settings                      = $this->loadData('settings');
        /** @var borrowing_motive $borrowingMotive */
        $borrowingMotive                     = $this->loadData('borrowing_motive');
        $companyTaxFormType                  = $this->loadData('company_tax_form_type');
        /** @var \company_balance_type $companyBalanceDetailsType */
        $companyBalanceDetailsType           = $this->loadData('company_balance_type');

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->get('unilend.service.project_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager $productManager */
        $productManager = $this->get('unilend.service_product.product_manager');
        /** @var \Symfony\Component\Translation\Translator translator */
        $this->translator = $this->get('translator');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager $companyBalanceSheetManager */
        $companyBalanceSheetManager = $this->get('unilend.service.company_balance_sheet_manager');
        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->settings->get('Durée des prêts autorisées', 'type');
            $this->dureePossible = explode(',', $this->settings->value);
            $this->taxFormTypes =  $companyTaxFormType->select();

            $this->allTaxFormTypes = [];
            foreach ($this->taxFormTypes as $formType) {
                $this->allTaxFormTypes[$formType['label']] = $companyBalanceDetailsType->select('id_company_tax_form_type = '.$formType['id_type']);
            }
            /** @var product $product */
            $product = $this->loadData('product');

            if (false === empty($this->projects->id_product) && $product->get($this->projects->id_product)) {
                $durationMax = $productManager->getMaxEligibleDuration($product);
                $durationMin = $productManager->getMinEligibleDuration($product);

                foreach ($this->dureePossible as $index => $duration) {
                    if (is_numeric($durationMax) && $duration > $durationMax
                        || is_numeric($durationMin) && $duration < $durationMin
                    ) {
                        unset($this->dureePossible[$index]);
                    }
                }

                $this->availableContracts = array_column($productManager->getAvailableContracts($product), 'label');
            }

            if (false === in_array($this->projects->period, array(0, 1000000)) && false === in_array($this->projects->period, $this->dureePossible)) {
                array_push($this->dureePossible, $this->projects->period);
                sort($this->dureePossible);
            }

            $this->aBorrowingMotives = $borrowingMotive->select();

            $this->settings->get('Cabinet de recouvrement', 'type');
            $this->cab = $this->settings->value;

            /** @var \tax_type $taxType */
            $taxType = $this->loadData('tax_type');

            $taxRate        = $taxType->getTaxRateByCountry('fr');
            $this->fVATRate = $taxRate[\tax_type::TYPE_VAT] / 100;

            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');
            $this->clients_adresses->get($this->companies->id_client_owner, 'id_client');
            $this->projects_notes->get($this->projects->id_project, 'id_project');
            $this->project_cgv->get($this->projects->id_project, 'id_project');

            $this->projects_status->get($this->projects->status, 'status');
            $this->projects_status_history->loadLastProjectHistory($this->projects->id_project);

            $this->bHasAdvisor       = false;
            $this->bReadonlyRiskNote = $this->projects->status >= \projects_status::PREP_FUNDING;

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

            $this->latitude  = (float) $this->companies->latitude;
            $this->longitude = (float) $this->companies->longitude;

            $this->aAnnualAccountsDates = array();
            $this->aAnalysts            = $this->users->select('(status = 1 AND id_user_type = 2) OR id_user = ' . $this->projects->id_analyste);
            $this->aSalesPersons        = $this->users->select('(status = 1 AND id_user_type = 3) OR id_user = ' . $this->projects->id_commercial);
            $this->aEmails              = $this->projects_status_history->select('content != "" AND id_user > 0 AND id_project = ' . $this->projects->id_project, 'added DESC, id_project_status_history DESC');
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
                $this->aBalanceSheets          = $companyBalanceSheetManager->getBalanceSheetsByAnnualAccount($aAnnualAccountsIds);
                foreach ($aAnnualAccountsIds as $balanceId) {
                    $this->companies_bilans->get($balanceId);
                    $this->incomeStatements[$balanceId] = $companyBalanceSheetManager->getIncomeStatement($this->companies_bilans);
                }
                if (count($this->lCompanies_actif_passif) < count($this->lbilans)) {
                    foreach (array_diff(array_column($this->lbilans, 'id_bilan'), array_column($this->lCompanies_actif_passif, 'id_bilan')) as $iAnnualAccountsId) {
                        if ($this->aBalanceSheets[$iAnnualAccountsId]['form_type'] == \company_tax_form_type::FORM_2033) {
                            /** @var companies_actif_passif $oAssetsDebts */
                            $oAssetsDebts                                     = $this->loadData('companies_actif_passif');
                            $oAssetsDebts->id_bilan                           = $iAnnualAccountsId;
                            $oAssetsDebts->create();
                        }
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

            $this->attachment_type          = $this->loadData('attachment_type');
            $this->aAttachments             = $this->projects->getAttachments();
            $this->aAttachmentTypes         = $this->attachment_type->getAllTypesForProjects($this->language);
            $this->aMandatoyAttachmentTypes = [
                \attachment_type::DERNIERE_LIASSE_FISCAL,
                \attachment_type::LIASSE_FISCAL_N_1,
                \attachment_type::LIASSE_FISCAL_N_2,
                \attachment_type::RELEVE_BANCAIRE_MOIS_N,
                \attachment_type::RELEVE_BANCAIRE_MOIS_N_1,
                \attachment_type::RELEVE_BANCAIRE_MOIS_N_2,
                \attachment_type::KBIS,
                \attachment_type::RIB,
                \attachment_type::CNI_PASSPORTE_DIRIGEANT,
                \attachment_type::ETAT_PRIVILEGES_NANTISSEMENTS,
                \attachment_type::CGV
            ];

            $this->completude_wording = array();
            $aAttachmentTypes         = $this->attachment_type->getAllTypesForProjects($this->language, false);

            foreach ($this->attachment_type->changeLabelWithDynamicContent($aAttachmentTypes) as $aAttachment) {
                if ($aAttachment['id'] == \attachment_type::PHOTOS_ACTIVITE) {
                    $this->completude_wording[] = $aAttachment['label'] . ' ' . $this->translator->trans('projet_completude-photos');
                } else {
                    $this->completude_wording[] = $aAttachment['label'];
                }
            }
            $this->completude_wording[] = $this->translator->trans('projet_completude-charge-affaires');

            if (isset($_POST['problematic_status']) && $this->projects->status != $_POST['problematic_status']) {
                $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], $_POST['problematic_status'], $this->projects);

                $this->updateProblematicStatus($_POST['problematic_status']);
            }

            if (false === empty($this->projects->risk) && false === empty($this->projects->period) && $this->projects->status >= projects_status::PREP_FUNDING) {
                $fPredictAmountAutoBid = $this->get('unilend.service.autobid_settings_manager')->predictAmount($this->projects->risk, $this->projects->period);
                $this->fPredictAutoBid = round(($fPredictAmountAutoBid / $this->projects->amount) * 100, 1);

                if (false === empty($this->projects->id_rate)) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BidManager $bidManager */
                    $bidManager     = $this->get('unilend.service.bid_manager');
                    $rateRange      = $bidManager->getProjectRateRange($this->projects);
                    $this->rate_min = $rateRange['rate_min'];
                    $this->rate_max = $rateRange['rate_max'];
                }
            }

            $this->eligibleProduct = $productManager->findEligibleProducts($this->projects, true);

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
            } elseif (isset($_POST['add_annual_accounts'], $_POST['tax_form_type']) && is_numeric($_POST['tax_form_type']) && $companyTaxFormType->get($_POST['tax_form_type'])) {
                $aLastAnnualAccounts                                 = current($this->aAllAnnualAccounts);
                $oClosingDate = new \DateTime($aLastAnnualAccounts['cloture_exercice_fiscal']);
                $this->companies_bilans->id_company                  = $this->projects->id_company;
                $this->companies_bilans->cloture_exercice_fiscal     = $oClosingDate->add(new \DateInterval('P12M'))->format('Y-m-d');
                $this->companies_bilans->duree_exercice_fiscal       = 12;
                $this->companies_bilans->id_company_tax_form_type    = $_POST['tax_form_type'];
                $this->companies_bilans->ca                          = 0;
                $this->companies_bilans->resultat_brute_exploitation = 0;
                $this->companies_bilans->resultat_exploitation       = 0;
                $this->companies_bilans->investissements             = 0;
                $this->companies_bilans->create();

                if ($companyTaxFormType->label == \company_tax_form_type::FORM_2035) {
                    $this->companies_actif_passif->id_bilan = $this->companies_bilans->id_bilan;
                    $this->companies_actif_passif->create();
                }
                $this->projects->id_dernier_bilan = $this->companies_bilans->id_bilan;
                $this->projects->update();

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            } elseif (isset($_POST['submit-button'], $_POST['id_annual_accounts_remove']) && 'Supprimer' === $_POST['submit-button'] && is_numeric($_POST['id_annual_accounts_remove'])) {
                $this->companies_bilans->get($_POST['id_annual_accounts_remove']);
                $companyBalanceSheetManager->removeBalanceSheet($this->companies_bilans, $this->projects);
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            } elseif (isset($this->params[1]) && $this->params[1] == 'altares') {
                if (false === empty($this->companies->siren)) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Altares $oAltares */
                    $oAltares = $this->get('unilend.service.altares');
                    try {
                        $oAltares->setCompanyData($this->companies);
                        $oAltares->setProjectData($this->projects);
                        $oAltares->setCompanyBalance($this->companies);
                        $_SESSION['freeow']['title']   = 'Données Altares';
                        $_SESSION['freeow']['message'] = 'Données Altares récupéré !';
                    } catch (\Exception $exception) {
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

                if ($oProjectStatusHistory->loadLastProjectHistory($this->projects->id_project)) {
                    /** @var \projects_status_history_details $oProjectsStatusHistoryDetails */
                    $oProjectsStatusHistoryDetails = $this->loadData('projects_status_history_details');

                    $bCreate = (false === $oProjectsStatusHistoryDetails->get($oProjectStatusHistory->id_project_status_history, 'id_project_status_history'));

                    switch ($this->projects->status) {
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

            if (isset($_POST['pret_refuse']) && $_POST['pret_refuse'] == 1) {
                if ($this->projects->status < \projects_status::PRET_REFUSE) {
                    /** @var \loans $loans */
                    $loans = $this->loadData('loans');
                    /** @var \transactions $transactions */
                    $transactions = $this->loadData('transactions');
                    /** @var \lenders_accounts $lenders */
                    $lenders = $this->loadData('lenders_accounts');
                    /** @var \clients $clients */
                    $clients = $this->loadData('clients');
                    /** @var \wallets_lines $wallets_lines */
                    $wallets_lines = $this->loadData('wallets_lines');
                    /** @var \echeanciers $echeanciers */
                    $echeanciers = $this->loadData('echeanciers');

                    $this->settings->get('Facebook', 'type');
                    $facebookLink = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $twitterLink = $this->settings->value;

                    $lendersCount = $loans->getNbPreteurs($this->projects->id_project);

                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], \projects_status::PRET_REFUSE, $this->projects);

                    //on supp l'écheancier du projet pour ne pas avoir de doublon d'affichage sur le front (BT 18600)
                    $echeanciers->delete($this->projects->id_project, 'id_project');

                    foreach ($loans->select('id_project = ' . $this->projects->id_project) as $l) {
                        if (false === $transactions->get($l['id_loan'], 'id_loan_remb')) {
                            $lenders->get($l['id_lender'], 'id_lender_account');
                            $clients->get($lenders->id_client_owner, 'id_client');

                            $loans->get($l['id_loan'], 'id_loan');
                            $loans->status = \loans::STATUS_REJECTED;
                            $loans->update();

                            // On redonne l'argent aux preteurs
                            $transactions->id_client        = $clients->id_client;
                            $transactions->montant          = $l['amount'];
                            $transactions->id_langue        = 'fr';
                            $transactions->id_loan_remb     = $l['id_loan'];
                            $transactions->date_transaction = date('Y-m-d H:i:s');
                            $transactions->status           = \transactions::STATUS_VALID;
                            $transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                            $transactions->type_transaction = \transactions_types::TYPE_LENDER_LOAN;
                            $transactions->create();

                            $wallets_lines->id_lender                = $l['id_lender'];
                            $wallets_lines->type_financial_operation = 20;
                            $wallets_lines->id_transaction           = $transactions->id_transaction;
                            $wallets_lines->status                   = 1;
                            $wallets_lines->type                     = 2;
                            $wallets_lines->amount                   = $l['amount'];
                            $wallets_lines->create();

                            $varMail = [
                                'surl'              => $this->surl,
                                'url'               => $this->furl,
                                'prenom_p'          => $clients->prenom,
                                'valeur_bid'        => $this->ficelle->formatNumber($l['amount'] / 100, 0),
                                'nom_entreprise'    => $this->companies->name,
                                'nb_preteurMoinsUn' => $lendersCount - 1,
                                'motif_virement'    => $this->clients->getLenderPattern($clients->id_client),
                                'lien_fb'           => $facebookLink,
                                'lien_tw'           => $twitterLink
                            ];

                            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-pret-refuse', $varMail);
                            $message->setTo($clients->email);
                            $mailer = $this->get('mailer');
                            $mailer->send($message);
                        }
                    }

                    $_SESSION['freeow']['title']   = 'Refus de prêt';
                    $_SESSION['freeow']['message'] = 'Le prêt a été refusé et les emails envoyés aux prêteurs';
                } else {
                    $_SESSION['freeow']['title']   = 'Refus de prêt';
                    $_SESSION['freeow']['message'] = 'Le prêt a déjà été refusé';
                }

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            } elseif (isset($_POST['send_form_dossier_resume'])) {
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

                if (false === $dates_valide && in_array(\projects_status::A_FUNDER, array($_POST['status'], $this->projects->status))) {
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
                        && $this->projects->status < \projects_status::EN_ATTENTE_PIECES
                    ) {
                        $_POST['status'] = \projects_status::EN_ATTENTE_PIECES;
                    }

                    if (
                        $_POST['analyste'] > 0
                        && $_POST['analyste'] != $this->projects->id_analyste
                        && $this->projects->status < \projects_status::REVUE_ANALYSTE
                    ) {
                        $_POST['status'] = \projects_status::REVUE_ANALYSTE;
                    }

                    $this->projects->title               = $_POST['title'];
                    $this->projects->title_bo            = $_POST['title_bo'];
                    $this->projects->nature_project      = $_POST['nature_project'];
                    $this->projects->id_analyste         = $_POST['analyste'];
                    $this->projects->id_commercial       = $_POST['commercial'];
                    $this->projects->display             = $_POST['display_project'];
                    $this->projects->id_project_need     = $_POST['need'];
                    $this->projects->id_borrowing_motive = $_POST['motive'];

                    if (false === $this->bReadonlyRiskNote) {
                        $this->projects->id_product = $_POST['assigned_product'];
                        $this->projects->period     = $_POST['duree'];
                        $this->projects->amount     = str_replace([' ', ','], ['', '.'], $_POST['montant']);
                    }

                    if ($this->projects->status <= \projects_status::A_FUNDER) {
                        $sector = $this->translator->trans('company-sector_sector-' . $this->companies->sector);
                        $this->settings->get('Prefixe URL pages projet', 'type');
                        $this->projects->slug = $this->ficelle->generateSlug($this->settings->value . '-' . $sector . '-' . $this->companies->city . '-' . substr(md5($this->projects->title . $this->projects->id_project), 0, 7));
                    }

                    if ($this->projects->status >= \projects_status::PREP_FUNDING) {
                        if (isset($_POST['date_publication']) && ! empty($_POST['date_publication'])) {
                            $publicationDate                  = \DateTime::createFromFormat('d/m/Y H:i', $_POST['date_publication'] . ' ' . $_POST['date_publication_heure'] . ':' . $_POST['date_publication_minute']);
                            $this->projects->date_publication = $publicationDate->format('Y-m-d H:i:s');
                        }

                        if (isset($_POST['date_retrait']) && ! empty($_POST['date_retrait'])) {
                            $endOfPublicationDate         = \DateTime::createFromFormat('d/m/Y H:i', $_POST['date_retrait'] . ' ' . $_POST['date_retrait_heure'] . ':' . $_POST['date_retrait_minute']);
                            $this->projects->date_retrait = $endOfPublicationDate->format('Y-m-d H:i:s');
                        }

                        if (false === empty($this->projects->risk) && false === empty($this->projects->period)) {
                            try {
                                $this->projects->id_rate = $oProjectManager->getProjectRateRange($this->projects);
                            } catch (\Exception $exception) {
                                $_SESSION['freeow']['message'] .= $exception->getMessage();
                            }
                        }
                    }

                    $this->projects->update();

                    if (isset($_POST['current_status']) && $_POST['status'] != $_POST['current_status'] && $this->projects->status != $_POST['status']) {

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
                    $this->companies->id_client_owner = $_POST['id_client'];
                    $this->companies->code_naf        = $_POST['code_naf'];
                    $this->companies->libelle_naf     = $_POST['libelle_naf'];
                    $this->companies->tribunal_com    = $_POST['tribunal_com'];
                    $this->companies->activite        = $_POST['activite'];
                    $this->companies->lieu_exploi     = $_POST['lieu_exploi'];
                    $this->companies->latitude        = (float) str_replace(',', '.', $_POST['latitude']);
                    $this->companies->longitude       = (float) str_replace(',', '.', $_POST['longitude']);

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

                    $_SESSION['freeow']['message'] .= 'Modifications enregistrées avec succès';

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

                if ($this->projects->status > \projects_status::EN_FUNDING) {
                    $form_ok = false;
                }

                if ($form_ok == true) {
                    $endOfPublicationDate = \DateTime::createFromFormat('d/m/Y H:i', $_POST['date_de_retrait'] . ' ' . $_POST['date_retrait_heure'] . ':' . $_POST['date_retrait_minute']);

                    if ($endOfPublicationDate > new \DateTime()) {
                        $this->projects->date_retrait = $endOfPublicationDate->format('Y-m-d H:i:s');
                        $this->projects->update();
                    }
                }
            }

            /** @var \project_need $oProjectNeed */
            $oProjectNeed = $this->loadData('project_need');
            $needs        = $oProjectNeed->getTree();
            $this->aNeeds = $needs;

            if (in_array($this->projects->status, [\projects_status::REJETE, \projects_status::REJET_ANALYSTE, \projects_status::REJET_COMITE])) {
                /** @var \projects_status_history_details $oProjectsStatusHistoryDetails */
                $oProjectsStatusHistoryDetails = $this->loadData('projects_status_history_details');
                /** @var \project_rejection_reason $oRejectionReason */
                $oRejectionReason = $this->loadData('project_rejection_reason');

                $this->sRejectionReason = '';

                if (
                    $oProjectsStatusHistoryDetails->get($this->projects_status_history->id_project_status_history, 'id_project_status_history')
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

            $this->recup_info_remboursement_anticipe();
        } else {
            header('Location: ' . $this->lurl . '/dossiers');
            die;
        }
    }

    protected function sumBalances(array $aBalances, $aBalanceSheet)
    {
        $fTotal = 0.0;
        foreach ($aBalances as $sBalance) {
            if ('-' === substr($sBalance, 0, 1)) {
                $fTotal -= $aBalanceSheet['details'][substr($sBalance, 1)];
            } else {
                $fTotal += $aBalanceSheet['details'][$sBalance];
            }
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
        /** @var \projects_remb $projects_remb */
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
            /** @var \prelevements $prelevements */
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

        if (1 == $_POST['send_email_borrower']) {
            $this->sendProblemStatusEmailBorrower($iStatus);
        }

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
            if ($aReplacements['delai_regularisation'] >= 2) {
                $aReplacements['delai_regularisation'] .= ' jours';
            } else {
                $aReplacements['delai_regularisation'] .= ' jour';
            }
        }

        if (in_array($iStatus, array(\projects_status::RECOUVREMENT, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE))) {
            /** @var \echeanciers $oLenderRepaymentSchedule */
            $oLenderRepaymentSchedule = $this->loadData('echeanciers');
            $aReplacements['CRD'] = $this->ficelle->formatNumber($oLenderRepaymentSchedule->getOwedCapital(array('id_project' => $this->projects->id_project)));

            if (\projects_status::RECOUVREMENT == $iStatus) {
                $aReplacements['mensualites_impayees'] = $this->ficelle->formatNumber($oLenderRepaymentSchedule->getUnpaidAmountAtDate($this->projects->id_project, new \DateTime('NOW')));
            }
        }

        $aFundingDate = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'added ASC, id_project_status_history ASC', 0, 1);
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

        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');
        $logger->debug('Mail to send : ' . $sMailType . ' Variables : ' . json_encode($aReplacements), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $this->projects->id_project]);

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($sMailType, $aReplacements);
        $message->setTo($this->clients->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function sendProblemStatusEmailLender($iStatus, $projectStatusHistoryDetails)
    {
        $this->transactions = $this->loadData('transactions');

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
                $aCollectiveProcess = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status IN (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::PROCEDURE_SAUVEGARDE . ')', 'added ASC, id_project_status_history ASC', 0, 1);

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
                $aCollectiveProcess = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status IN (SELECT id_project_status FROM projects_status WHERE status IN (' . \projects_status::PROCEDURE_SAUVEGARDE . ', ' . \projects_status::REDRESSEMENT_JUDICIAIRE . '))', 'added ASC, id_project_status_history ASC', 0, 1);

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

                $aCompulsoryLiquidation = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::LIQUIDATION_JUDICIAIRE . ')', 'added ASC, id_project_status_history ASC', 0, 1);
                $aCommonReplacements['date_annonce_liquidation_judiciaire'] = date('d/m/Y', strtotime($aCompulsoryLiquidation[0]['added']));
                break;
        }

        $aRepaymentStatus = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . \projects_status::REMBOURSEMENT . ')', 'added ASC, id_project_status_history ASC', 0, 1);
        $aCommonReplacements['annee_projet'] = date('Y', strtotime($aRepaymentStatus[0]['added']));

        if (in_array($iStatus, array(\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE))) {
            $oMaxClaimsSendingDate = new \DateTime($projectStatusHistoryDetails->date);
            $aCommonReplacements['date_max_envoi_declaration_creances'] = date('d/m/Y', $oMaxClaimsSendingDate->add(new \DateInterval('P2M'))->getTimestamp());
        }

        $aLenderLoans = $this->loans->getProjectLoansByLender($this->projects->id_project);

        if (is_array($aLenderLoans)) {
            $aNextRepayment = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND date_echeance > "' . date('Y-m-d') . '"', 'date_echeance ASC', 0, 1);

            foreach ($aLenderLoans as $aLoans) {
                $this->lenders_accounts->get($aLoans['id_lender'], 'id_lender_account');
                $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

                $fTotalPayedBack = 0.0;
                $iLoansCount     = $aLoans['cnt'];
                $fLoansAmount    = $aLoans['amount'];

                foreach ($this->echeanciers->select('id_loan IN (' . $aLoans['loans'] . ') AND id_project = ' . $this->projects->id_project . ' AND status = 1') as $aPayment) {
                    $fTotalPayedBack += $this->transactions->getRepaymentTransactionsAmount($aPayment['id_echeancier']);
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

                    $aReplacements = $aCommonReplacements + array(
                            'prenom_p'                    => $this->clients->prenom,
                            'entreprise'                  => $this->companies->name,
                            'montant_pret'                => $this->ficelle->formatNumber($fLoansAmount / 100, 0),
                            'montant_rembourse'           => '<span style=\'color:#b20066;\'>' . $this->ficelle->formatNumber($fTotalPayedBack / 100) . '&nbsp;euros</span> vous ont d&eacute;j&agrave; &eacute;t&eacute; rembours&eacute;s.<br/><br/>',
                            'nombre_prets'                => $iLoansCount . ' ' . (($iLoansCount > 1) ? 'pr&ecirc;ts' : 'pr&ecirc;t'), // @todo intl
                            'date_prochain_remboursement' => $this->dates->formatDate($aNextRepayment[0]['date_echeance'], 'd/m/Y'), // @todo intl
                            'CRD'                         => $this->ficelle->formatNumber(($fLoansAmount - $fTotalPayedBack) / 100)
                        );

                    $sMailType = (in_array($this->clients->type, array(1, 3))) ? $sEmailTypePerson : $sEmailTypeSociety;
                    $locale  = $this->getParameter('locale');
                    $this->mail_template->get($sMailType, 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $locale . '" AND type');
                    $aReplacements['sujet'] = $this->mail_template->subject;

                    /** @var \Psr\Log\LoggerInterface $logger */
                    $logger = $this->get('logger');
                    $logger->debug('Mail to send : ' . $sMailType . ' Variables : ' . json_encode($aReplacements), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $this->projects->id_project]);

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

        /** @var \tax_type $taxType */
        $taxType = $this->loadData('tax_type');

        $taxRate        = $taxType->getTaxRateByCountry('fr');
        $this->fVATRate = $taxRate[\tax_type::TYPE_VAT] / 100;

        /** @var company_rating $oCompanyRating */
        $oCompanyRating = $this->loadData('company_rating');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager $companyBalanceSheetManager */
        $companyBalanceSheetManager = $this->get('unilend.service.company_balance_sheet_manager');

        $this->aRatings                 = $oCompanyRating->getHistoryRatingsByType($this->oProject->id_company_rating_history);
        $this->aAnnualAccounts          = $oAnnualAccounts->select('id_company = ' . $this->oCompany->id_company . ' AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $this->oProject->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);
        $aAnnualAccountsIds             = array_column($this->aAnnualAccounts, 'id_bilan');
        $this->aBalanceSheets           = $companyBalanceSheetManager->getBalanceSheetsByAnnualAccount($aAnnualAccountsIds)['details'];
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
            $this->lClients = $this->clients->searchEmprunteurs('OR', $this->params[0], $this->params[0]);
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

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ClientManager $clientManager */
        $clientManager = $this->get('unilend.service.client_manager');

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
            if (
                false === isset($this->params[1])
                || false === $this->clients->get($this->params[1], 'id_client')
                || $clientManager->isLender($this->clients)
            ) {
                $this->clients_adresses = $this->loadData('clients_adresses');

                $this->clients->etape_inscription_preteur  = 0;
                $this->clients->status_inscription_preteur = 0;
                $this->clients->type                       = 0;
                $this->clients->source                     = '';
                $this->clients->source2                    = '';
                $this->clients->source3                    = '';
                $this->clients->slug_origine               = '';
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
            $this->projects->status     = \projects_status::A_TRAITER;
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

            // additional safeguard to avoid duplicate email when taking an existing lender as borrower, will be replaced by the borrower account checks when doing balance project
            if ($clientManager->isLender($this->clients)){
                $this->clients->email = '';
            }

            $this->clients_adresses->get($this->clients->id_client, 'id_client');

            if (isset($this->params[1]) && $this->params[1] === 'altares') {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Altares $oAltares */
                $oAltares = $this->get('unilend.service.altares');
                try {
                    $oAltares->setCompanyData($this->companies);
                    $oAltares->setProjectData($this->projects);
                    $oAltares->setCompanyBalance($this->companies);
                } catch (\Exception $exception) {

                }

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

        $this->lProjects = $this->projects->selectProjectsByStatus([\projects_status::EN_FUNDING]);
    }

    public function _remboursements()
    {
        $this->setView('remboursements');
        $this->pageTitle = 'Remboursements';
        $this->listing([\projects_status::FUNDE, \projects_status::REMBOURSEMENT]);
    }

    public function _no_remb()
    {
        $this->setView('remboursements');
        $this->pageTitle = 'Incidents de remboursement';
        $this->listing([\projects_status::PROBLEME, \projects_status::RECOUVREMENT, \projects_status::PROBLEME_J_X, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT]);
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

        /** @var \tax_type $taxType */
        $taxType = $this->loadData('tax_type');
        /** @var \Psr\Log\LoggerInterface $oLogger */
        $oLogger = $this->get('logger');

        $taxRate   = $taxType->getTaxRateByCountry('fr');
        $this->tva = $taxRate[\tax_type::TYPE_VAT] / 100;

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');
            $this->users->get($this->projects->id_analyste, 'id_user');
            $this->projects_status->get($this->projects->status, 'status');

            $this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);

            $lRembs            = $this->echeanciers_emprunteur->select('id_project = ' . $this->projects->id_project);
            $dernierStatut     = $this->projects_status_history->select('id_project = ' . $this->projects->id_project, 'added DESC, id_project_status_history DESC', 0, 1);
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
                if ($r['status_emprunteur'] == 1) {
                    $this->nbRembEffet += 1;
                    $this->totalEffet += $r['montant'] + $r['commission'] + $r['tva'];
                    $this->interetEffet += $r['interets'];
                    $this->capitalEffet += $r['capital'];
                    $this->commissionEffet += $r['commission'];
                    $this->tvaEffet += $r['tva'];
                } else {
                    if ($this->nextRemb == '') {
                        $this->nextRemb = $r['date_echeance_emprunteur'];
                    }

                    $this->nbRembaVenir += 1;
                    $this->totalaVenir += $r['montant'] + $r['commission'] + $r['tva'];
                    $this->interetaVenir += $r['interets'];
                    $this->capitalaVenir += $r['capital'];
                    $this->commissionaVenir += $r['commission'];
                    $this->tvaaVenir += $r['tva'];
                }
            }

            $this->commissionUnilend = $this->commissionEffet + $this->commissionaVenir;

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
                    $listdesRembauto = $this->projects_remb->select('id_project = ' . $this->projects->id_project . ' AND status = ' . \projects_remb::STATUS_AUTOMATIC_REFUND_DISABLED . ' AND DATE(date_remb_preteurs) >= "' . date('Y-m-d') . '" AND date_remb_preteurs_reel = "0000-00-00 00:00:00"');

                    foreach ($listdesRembauto as $rembauto) {
                        $this->projects_remb->get($rembauto['id_project_remb'], 'id_project_remb');
                        $this->projects_remb->status = \projects_remb::STATUS_PENDING;
                        $this->projects_remb->update();
                    }
                }

                $this->projects->remb_auto = $_POST['remb_auto'];
                $this->projects->update();
            }

            if (isset($this->params[1]) && $this->params[1] == 'remb') {
                $settingsControleRemb = $this->loadData('settings');
                $settingsControleRemb->get('Controle cron remboursements auto', 'type');

                if ($settingsControleRemb->value == 1) {
                    $settingsControleRemb->value = 0;
                    $settingsControleRemb->update();

                    $this->settings->get('Facebook', 'type');
                    $lien_fb = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $lien_tw = $this->settings->value;

                    $montant                  = 0;
                    $iTotalTaxAmount          = 0;
                    $lEcheancesRembEmprunteur = $this->echeanciers_emprunteur->select('id_project = ' . $this->projects->id_project . ' AND status_emprunteur = 1', 'ordre ASC');

                    $oLogger->debug('Borrower repayment schedule for id_project: ' . $this->projects->id_project . ' = ' . json_encode($lEcheancesRembEmprunteur), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $this->projects->id_project]);

                    if (false === empty($lEcheancesRembEmprunteur)) {
                        foreach ($lEcheancesRembEmprunteur as $RembEmpr) {
                            $lEcheances = $this->echeanciers->select('id_project = ' . $RembEmpr['id_project'] . ' AND status_emprunteur = 1 AND ordre = ' . $RembEmpr['ordre'] . ' AND status = 0');

                            if (false === empty($lEcheances)) {
                                break;
                            }
                        }
                    }
                    /** @var TaxManager $taxManager */
                    $taxManager = $this->get('unilend.service.tax_manager');
                    /** @var \lender_repayment $lenderRepayment */
                    $lenderRepayment = $this->loadData('lender_repayment');

                    $oLogger->info('Manual repayment, lender repayments found: ' . json_encode($lEcheances), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $this->projects->id_project]);

                    foreach ($lEcheances as $e) {
                        $repaymentDate = date('Y-m-d H:i:s');
                        try {
                            if (false === $this->transactions->exist($e['id_echeancier'], 'id_echeancier')) {
                                $montant += $e['montant'];

                                $this->lenders_accounts->get($e['id_lender'], 'id_lender_account');
                                $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

                                $lenderRepayment->id_lender  = $e['id_lender'];
                                $lenderRepayment->id_company = $this->projects->id_company;
                                $lenderRepayment->amount     = $e['montant'];
                                $lenderRepayment->create();

                                $this->echeanciers->get($e['id_echeancier'], 'id_echeancier');
                                $this->echeanciers->capital_rembourse   = $this->echeanciers->capital;
                                $this->echeanciers->interets_rembourses = $this->echeanciers->interets;
                                $this->echeanciers->status              = \echeanciers::STATUS_REPAID;
                                $this->echeanciers->status_email_remb   = 1;
                                $this->echeanciers->date_echeance_reel  = $repaymentDate;
                                $this->echeanciers->update();

                                $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                                $this->transactions->montant          = $e['capital'];
                                $this->transactions->id_echeancier    = $e['id_echeancier'];
                                $this->transactions->id_langue        = 'fr';
                                $this->transactions->date_transaction = $repaymentDate;
                                $this->transactions->status           = \transactions::STATUS_VALID;
                                $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                $this->transactions->type_transaction = \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL;
                                $capitalTransactionId = $this->transactions->create();

                                $iTaxOnCapital = $taxManager->taxTransaction($this->transactions);

                                $this->wallets_lines->id_lender                = $e['id_lender'];
                                $this->wallets_lines->type_financial_operation = \wallets_lines::TYPE_REPAYMENT;
                                $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                                $this->wallets_lines->status                   = 1;
                                $this->wallets_lines->type                     = \wallets_lines::VIRTUAL;
                                $this->wallets_lines->amount                   = $this->transactions->montant;
                                $this->wallets_lines->create();
                                $this->wallets_lines->unsetData();

                                $this->transactions->unsetData();
                                $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                                $this->transactions->montant          = $e['interets'];
                                $this->transactions->id_echeancier    = $e['id_echeancier'];
                                $this->transactions->id_langue        = 'fr';
                                $this->transactions->date_transaction = $repaymentDate;
                                $this->transactions->status           = \transactions::STATUS_VALID;
                                $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                                $this->transactions->type_transaction = \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS;
                                $this->transactions->create();

                                $iTaxOnInterests = $taxManager->taxTransaction($this->transactions);
                                $iTotalTaxAmount = bcadd($iTotalTaxAmount, bcadd($iTaxOnCapital, $iTaxOnInterests));

                                $this->wallets_lines->id_lender                = $e['id_lender'];
                                $this->wallets_lines->type_financial_operation = \wallets_lines::TYPE_REPAYMENT;
                                $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                                $this->wallets_lines->status                   = 1;
                                $this->wallets_lines->type                     = \wallets_lines::VIRTUAL;
                                $this->wallets_lines->amount                   = $this->transactions->montant;
                                $this->wallets_lines->create();

                                $oLogger->debug('Manual repayment : repayment amount= ' . $e['montant'] . ' Interests tax= ' . $iTaxOnInterests . ' Capital tax= ' . $iTaxOnCapital, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $this->projects->id_project]);

                                $iTotalEAT                       = $e['montant'] - $iTaxOnInterests - $iTaxOnCapital;
                                $this->notifications->type       = \notifications::TYPE_REPAYMENT;
                                $this->notifications->id_lender  = $this->lenders_accounts->id_lender_account;
                                $this->notifications->id_project = $this->projects->id_project;
                                $this->notifications->amount     = $iTotalEAT;
                                $this->notifications->create();

                                $this->clients_gestion_mails_notif->id_transaction  = $capitalTransactionId;
                                $this->clients_gestion_mails_notif->id_client       = $this->lenders_accounts->id_client_owner;
                                $this->clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_REPAYMENT;
                                $this->clients_gestion_mails_notif->date_notif      = $repaymentDate;
                                $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                                $this->clients_gestion_mails_notif->create();

                                if ($this->projects->status == \projects_status::RECOUVREMENT) {
                                    $this->companies->get($this->projects->id_company, 'id_company');

                                    $this->settings->get('Cabinet de recouvrement', 'type');
                                    $sRecoveryCompany = $this->settings->value;

                                    $varMail = array(
                                        'surl'             => $this->surl,
                                        'url'              => $this->furl,
                                        'prenom_p'         => $this->clients->prenom,
                                        'cab_recouvrement' => $sRecoveryCompany,
                                        'mensualite_p'     => $this->ficelle->formatNumber(bcdiv($iTotalEAT, 100, 2)),
                                        'nom_entreprise'   => $this->companies->name,
                                        'solde_p'          => $this->transactions->getSolde($this->clients->id_client),
                                        'link_echeancier'  => $this->furl,
                                        'motif_virement'   => $this->clients->getLenderPattern($this->clients->id_client),
                                        'lien_fb'          => $lien_fb,
                                        'lien_tw'          => $lien_tw
                                    );

                                    $oLogger->info('Manual repayment, Send preteur-dossier-recouvre email. Data to use: ' . var_export($varMail, true), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $e['id_project']]);

                                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-dossier-recouvre', $varMail);
                                    $message->setTo($this->clients->email);
                                    $mailer = $this->get('mailer');
                                    $mailer->send($message);

                                } elseif (isset($this->params[2]) && $this->params[2] == 'regul') {
                                    $this->companies->get($this->projects->id_company, 'id_company');

                                    $nbpret = $this->loans->counter('id_lender = ' . $e['id_lender'] . ' AND id_project = ' . $e['id_project']);

                                    // euro avec ou sans "s"
                                    if (bcdiv($iTotalEAT, 100) >= 2) {
                                        $euros = ' euros';
                                    } else {
                                        $euros = ' euro';
                                    }
                                    $rembNetEmail = $this->ficelle->formatNumber(bcdiv($iTotalEAT, 100, 2)) . $euros;
                                    $balance      = $this->transactions->getSolde($this->clients->id_client);

                                    if ($balance >= 2) {
                                        $euros = ' euros';
                                    } else {
                                        $euros = ' euro';
                                    }
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
                                        'solde_p'               => $this->ficelle->formatNumber($balance) . $euros,
                                        'motif_virement'        => $this->clients->getLenderPattern($this->clients->id_client),
                                        'lien_fb'               => $lien_fb,
                                        'lien_tw'               => $lien_tw
                                    );

                                    $oLogger->info('Manual repayment, Send preteur-regularisation-remboursement. Data to use: ' . var_export($varMail, true), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $e['id_project']]);

                                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-regularisation-remboursement', $varMail);
                                    $message->setTo($this->clients->email);
                                    $mailer = $this->get('mailer');
                                    $mailer->send($message);
                                } elseif ($this->clients_gestion_notifications->getNotif($this->clients->id_client, \clients_gestion_type_notif::TYPE_REPAYMENT, 'immediatement') == true) {
                                    $this->clients_gestion_mails_notif->get($this->clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                                    $this->clients_gestion_mails_notif->immediatement = 1; // on met a jour le statut immediatement
                                    $this->clients_gestion_mails_notif->update();

                                    $this->loans->get($e['id_loan']);
                                    $lastProjectRepayment = (0 == $this->echeanciers->counter('id_project = ' . $this->projects->id_project . ' AND id_loan = ' . $this->loans->id_loan . ' AND status = 0 AND id_lender = ' . $e['id_lender']));

                                    $this->companies->get($this->projects->id_company, 'id_company');

                                    $nbpret = $this->loans->counter('id_lender = ' . $e['id_lender'] . ' AND id_project = ' . $e['id_project']);

                                    if (bcdiv($iTotalEAT, 100) >= 2) {
                                        $euros = ' euros';
                                    } else {
                                        $euros = ' euro';
                                    }
                                    $rembNetEmail = $this->ficelle->formatNumber(bcdiv($iTotalEAT, 100, 2)) . $euros;
                                    $balance      = $this->transactions->getSolde($this->clients->id_client);

                                    if ($balance >= 2) {
                                        $euros = ' euros';
                                    } else {
                                        $euros = ' euro';
                                    }
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
                                        'solde_p'               => $this->ficelle->formatNumber($balance) . $euros,
                                        'motif_virement'        => $this->clients->getLenderPattern($this->clients->id_client),
                                        'lien_fb'               => $lien_fb,
                                        'lien_tw'               => $lien_tw
                                    );

                                    if ($lastProjectRepayment) {
                                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-dernier-remboursement', $varMail);
                                        $oLogger->info('Manual repayment, Send preteur-dernier-remboursement. Data to use: ' . var_export($varMail, true), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $e['id_project']]);
                                    } else {
                                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                                        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-remboursement', $varMail);
                                        $oLogger->info('Manual repayment, Send preteur-remboursement. Data to use: ' . var_export($varMail, true), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $e['id_project']]);
                                    }
                                    $message->setTo($this->clients->email);
                                    $mailer = $this->get('mailer');
                                    $mailer->send($message);
                                }
                            }
                        } catch (\Exception $exception) {
                            /** @var \Psr\Log\LoggerInterface $oLogger */
                            $oLogger = $this->get('logger');
                            $oLogger->error(
                                'id_project=' . $e['id_project'] . ', id_echeancier=' . $e['id_echeancier'] . ' - An error occurred when calculating the refund details - Exception message: ' . $exception->getMessage() . ' - Exception code: ' . $exception->getCode(),
                                ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $e['id_project']]
                            );
                        }
                    }
                    // if the repayment exists also in automatic repayment pending list, update its status to "automatic disabled".
                    /** @var \projects_remb $autoRepayment */
                    $projectRepayment = $this->loadData('projects_remb');
                    if($projectRepayment->get($RembEmpr['id_project'], 'ordre = ' . $RembEmpr['ordre'] . ' AND id_project')) {
                        $projectRepayment->status = \projects_remb::STATUS_AUTOMATIC_REFUND_DISABLED;
                        $projectRepayment->date_remb_preteurs_reel = date('Y-m-d H:i:s');
                        $projectRepayment->update();
                    }


                    if (0 != $montant) {
                        $rembNetTotal = $montant - $iTotalTaxAmount;

                        $this->transactions->unsetData();
                        $this->transactions->montant_unilend          = - $rembNetTotal;
                        $this->transactions->montant_etat             = $iTotalTaxAmount;
                        $this->transactions->id_echeancier_emprunteur = $RembEmpr['id_echeancier_emprunteur'];
                        $this->transactions->id_langue                = 'fr';
                        $this->transactions->date_transaction         = date('Y-m-d H:i:s');
                        $this->transactions->status                   = \transactions::STATUS_VALID;
                        $this->transactions->ip_client                = $_SERVER['REMOTE_ADDR'];
                        $this->transactions->type_transaction         = \transactions_types::TYPE_UNILEND_REPAYMENT;
                        $this->transactions->create();

                        $this->bank_unilend->id_transaction         = $this->transactions->id_transaction;
                        $this->bank_unilend->id_project             = $this->projects->id_project;
                        $this->bank_unilend->montant                = - $rembNetTotal;
                        $this->bank_unilend->etat                   = $iTotalTaxAmount;
                        $this->bank_unilend->type                   = 2; // remb unilend
                        $this->bank_unilend->id_echeance_emprunteur = $RembEmpr['id_echeancier_emprunteur'];
                        $this->bank_unilend->status                 = 1;
                        $this->bank_unilend->create();

                        /** @var platform_account_unilend $oAccountUnilend */
                        $oAccountUnilend = $this->loadData('platform_account_unilend');
                        $oAccountUnilend->addDueDateCommssion($RembEmpr['id_echeancier_emprunteur']);

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
                            'montantRemb'     => $this->ficelle->formatNumber(bcdiv($rembNetTotal, 100, 2)),
                            'lien_fb'         => $lien_fb,
                            'lien_tw'         => $lien_tw
                        );

                        $oLogger->info('Manual repayment, Send facture-emprunteur-remboursement. Data to use: ' . var_export($varMail, true), ['class' => __CLASS__, 'function' => __FUNCTION__]);

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
                            $oInvoice->commission      = bcmul($fCommissionRate, 100);
                            $oInvoice->montant_ht      = $oBorrowerRepaymentSchedule->commission;
                            $oInvoice->tva             = $oBorrowerRepaymentSchedule->tva;
                            $oInvoice->montant_ttc     = $oBorrowerRepaymentSchedule->commission + $oBorrowerRepaymentSchedule->tva;
                            $oInvoice->create();
                        }

                        $_SESSION['freeow']['title']   = 'Remboursement prêteur';
                        $_SESSION['freeow']['message'] = 'Les prêteurs ont bien été remboursés !';
                    } else {
                        $_SESSION['freeow']['title']   = 'Remboursement prêteur';
                        $_SESSION['freeow']['message'] = "Aucun remboursement n'a été effectué aux prêteurs !";
                    }

                    if (0 == $this->echeanciers->counter('id_project = ' . $this->projects->id_project . ' AND status = 0')) {
                        /** @var MailerManager $mailerManager */
                        $mailerManager = $this->get('unilend.service.email_manager');
                        $mailerManager->setLogger($oLogger);
                        $mailerManager->sendInternalNotificationEndOfRepayment($this->projects);
                        $mailerManager->sendClientNotificationEndOfRepayment($this->projects);
                    }

                    $lesRembEmprun = $this->bank_unilend->select('type = 1 AND status = 0 AND id_project = ' . $this->projects->id_project, 'id_unilend ASC', 0, 1); // on ajoute la restriction pour BT 17882

                    foreach ($lesRembEmprun as $r) {
                        $this->bank_unilend->get($r['id_unilend'], 'id_unilend');
                        $this->bank_unilend->status = 1;
                        $this->bank_unilend->update();
                    }

                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                    $oProjectManager = $this->get('unilend.service.project_manager');

                    // si le projet etait en statut Recouvrement/probleme on le repasse en remboursement
                    if ($this->projects->status == \projects_status::RECOUVREMENT) {
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

            if (isset($_POST['spy_remb_anticipe']) && $_POST['id_reception'] > 0 && isset($_POST['id_reception'])) {
                $id_reception = $_POST['id_reception'];

                $this->projects                      = $this->loadData('projects');
                $this->echeanciers                   = $this->loadData('echeanciers');
                $this->receptions                    = $this->loadData('receptions');
                $this->echeanciers_emprunteur        = $this->loadData('echeanciers_emprunteur');
                $this->transactions                  = $this->loadData('transactions');
                $this->lenders_accounts              = $this->loadData('lenders_accounts');
                $this->clients                       = $this->loadData('clients');
                $this->wallets_lines                 = $this->loadData('wallets_lines');
                $this->mail_template                 = $this->loadData('mail_templates');
                $this->companies                     = $this->loadData('companies');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                $oProjectManager= $this->get('unilend.service.project_manager');

                $this->receptions->get($id_reception);
                $this->projects->get($this->receptions->id_project);
                $this->companies->get($this->projects->id_company, 'id_company');

                if (bcmul($_POST['montant_crd_preteur'], 100) == $this->receptions->montant) {
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

                    $oLogger->info('Manual Anticipated repayment, echeanciers and echeanciers_emprunteur update. Project id: ' . $this->projects->id_project, ['class' => __CLASS__, 'function' => __FUNCTION__]);

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
                        $reste_a_payer_pour_preteur = $this->echeanciers->getOwedCapital(array('id_loan' => $preteur['id_loan']));

                        $this->lenders_accounts->get($preteur['id_lender'], 'id_lender_account');
                        $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');

                        $this->transactions->id_client        = $this->lenders_accounts->id_client_owner;
                        $this->transactions->montant          = bcmul($reste_a_payer_pour_preteur, 100);
                        $this->transactions->id_echeancier    = 0; // pas d'id_echeance car multiple
                        $this->transactions->id_loan_remb     = $preteur['id_loan'];
                        $this->transactions->id_project       = $this->projects->id_project;
                        $this->transactions->id_langue        = 'fr';
                        $this->transactions->date_transaction = date('Y-m-d H:i:s');
                        $this->transactions->status           = \transactions::STATUS_VALID;
                        $this->transactions->ip_client        = $_SERVER['REMOTE_ADDR'];
                        $this->transactions->type_transaction = \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT;
                        $this->transactions->create();

                        $this->wallets_lines->id_lender                = $preteur['id_lender'];
                        $this->wallets_lines->type_financial_operation = 40;
                        $this->wallets_lines->id_loan                  = $preteur['id_loan'];
                        $this->wallets_lines->id_transaction           = $this->transactions->id_transaction;
                        $this->wallets_lines->status                   = 1; // non utilisé
                        $this->wallets_lines->type                     = 2; // transaction virtuelle
                        $this->wallets_lines->amount                   = bcmul($reste_a_payer_pour_preteur, 100);
                        $this->wallets_lines->id_wallet_line           = $this->wallets_lines->create();

                        $montant_total += $reste_a_payer_pour_preteur;
                    }

                    $this->bdd->query('
                        UPDATE echeanciers SET
                            status = 1,
                            capital_rembourse = capital,
                            updated = NOW(),
                            date_echeance_reel = NOW(),
                            date_echeance_emprunteur_reel = NOW(),
                            status_email_remb = 1
                        WHERE id_project = ' . $this->projects->id_project . ' AND status = 0'
                    );

                    // partie a retirer de bank unilend
                    if ($montant_total != 0) {
                        $this->transactions->montant                  = 0;
                        $this->transactions->id_echeancier            = 0; // on reinitialise
                        $this->transactions->id_client                = 0; // on reinitialise
                        $this->transactions->montant_unilend          = bcmul($montant_total, -100);
                        $this->transactions->montant_etat             = 0; // pas d'argent pour l'état
                        $this->transactions->id_echeancier_emprunteur = 0; // pas d'echeance emprunteur
                        $this->transactions->id_langue                = 'fr';
                        $this->transactions->date_transaction         = date('Y-m-d H:i:s');
                        $this->transactions->status                   = \transactions::STATUS_VALID;
                        $this->transactions->ip_client                = $_SERVER['REMOTE_ADDR'];
                        $this->transactions->type_transaction         = \transactions_types::TYPE_UNILEND_REPAYMENT;
                        $this->transactions->id_loan_remb             = 0;
                        $this->transactions->id_project               = $this->projects->id_project;
                        $this->transactions->create();

                        $this->bank_unilend->id_transaction         = $this->transactions->id_transaction;
                        $this->bank_unilend->id_project             = $this->projects->id_project;
                        $this->bank_unilend->montant                = bcmul($montant_total, -100);
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

            $this->recup_info_remboursement_anticipe();
        }
    }

    public function _detail_remb_preteur()
    {
        $this->clients          = $this->loadData('clients');
        $this->echeanciers      = $this->loadData('echeanciers');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $this->projects         = $this->loadData('projects');
        /** @var \loans loan */
        $this->loan = $this->loadData('loans');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager lenderManager */
        $this->lenderManager = $this->get('unilend.service.lender_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager loanManager */
        $this->loanManager = $this->get('unilend.service.loan_manager');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            /** @var \loans $oLoans */
            $loans = $this->loadData('loans');
            /** @var \echeanciers_emprunteur $oRepaymentSchedule */
            $repaymentSchedule = $this->loadData('echeanciers_emprunteur');

            $this->nbPeteurs = $loans->getNbPreteurs($this->projects->id_project);
            $this->tauxMoyen = $this->projects->getAverageInterestRate();
            $this->montant   = $repaymentSchedule->sum('montant', 'id_project = ' . $this->projects->id_project) / 100;
            $this->lLenders  = $loans->select('id_project = ' . $this->projects->id_project, 'rate ASC');
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

        /** @var \loans loan */
        $this->loan = $this->loadData('loans');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager loanManager */
        $this->loanManager = $this->get('unilend.service.loan_manager');
        $this->loan->get($this->params[1]);

        $this->lRemb = $this->echeanciers->getRepaymentWithTaxDetails($this->params[1]);

        // on check si on est en remb anticipé
        // ON recup la date de statut remb
        $dernierStatut = $this->projects_status_history->select('id_project = ' . $this->params[0], 'added DESC, id_project_status_history DESC', 0, 1);

        $this->projects_status->get(\projects_status::REMBOURSEMENT_ANTICIPE, 'status');
        $this->montant_ra = 0;

        if (true === isset($dernierStatut[0]['id_project_status']) && $dernierStatut[0]['id_project_status'] == $this->projects_status->id_project_status) {
            $this->montant_ra = $this->echeanciers->getEarlyRepaidCapital(array('id_loan' => $this->params[1]));
            $this->date_ra    = $dernierStatut[0]['added'];
        }
    }

    public function _echeancier_emprunteur()
    {
        $this->clients                 = $this->loadData('clients');
        $this->echeanciers             = $this->loadData('echeanciers');
        $this->projects                = $this->loadData('projects');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->receptions              = $this->loadData('receptions');
        $this->prelevements            = $this->loadData('prelevements');
        /** @var \echeanciers_emprunteur $repaymentSchedule */
        $repaymentSchedule = $this->loadData('echeanciers_emprunteur');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->lRemb = $repaymentSchedule->getDetailedProjectRepaymentSchedule($this->projects);

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
                $this->MontantEmprunteur += round($r['montant'] + $r['commission'] + $r['tva'], 2);
                $this->commission += $r['commission'];
                $this->comParMois    = $r['commission'];
                $this->comTtcParMois = $r['commission'] + $r['tva'];
                $this->tva           = $r['tva'];
                $this->totalTva += $r['tva'];

                $this->capital += $r['capital'];
            }
            // on check si on est en remb anticipé
            // ON recup la date de statut remb
            $dernierStatut    = $this->projects_status_history->select('id_project = ' . $this->projects->id_project, 'added DESC, id_project_status_history DESC', 0, 1);
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
    private function recup_info_remboursement_anticipe()
    {
        $this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
        $this->echeanciers            = $this->loadData('echeanciers');
        $oBusinessDays                = $this->loadLib('jours_ouvres');

        //Récupération de la date theorique de remb ( ON AJOUTE ICI LA ZONE TAMPON DE 3 JOURS APRES LECHEANCE)
        $aLastOrder             = $this->echeanciers->getLastOrder($this->projects->id_project);
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
                $aNextEcheance = $this->echeanciers->select(" id_project = " . $this->projects->id_project . "
                    AND DATE_ADD(date_echeance, INTERVAL 3 DAY) > NOW()
                    AND id_lender = (SELECT id_lender
                    FROM echeanciers where id_project = " . $this->projects->id_project . " LIMIT 1)
                    AND ordre = " . ($iOrderEarlyRefund + 1), 'ordre ASC', 0, 1);

                if (count($aNextEcheance) > 0) {
                    // on refait le meme process pour la nouvelle date
                    $aLastOrder             = $aNextEcheance[0];
                    $sLastOrderDate         = $aLastOrder['date_echeance'];
                    $iLastOrderDate         = strtotime($sLastOrderDate);
                    $sBusinessDaysOrderDate = $oBusinessDays->display_jours_ouvres($iLastOrderDate, 4);
                } else {
                    $this->nextRepaymentDate = "Aucune &eacute;ch&eacute;ance &agrave; venir dans le futur";
                }
            } else {
                // on va recup la date de la derniere echeance qui suit le process de base
                $aRepaymentSchedule                   = $this->echeanciers->select(' id_project = ' . $this->projects->id_project . ' AND ordre = ' . ($iOrderEarlyRefund + 1), 'ordre ASC', 0, 1);
                $this->date_derniere_echeance_normale = (false === empty($aRepaymentSchedule[0]['date_echeance'])) ? $this->dates->formatDateMysqltoFr_HourOut($aRepaymentSchedule[0]['date_echeance']) : '';
            }
        }

        if (false === empty($sBusinessDaysOrderDate)) {
            $this->nextRepaymentDate  = date('d/m/Y', $sBusinessDaysOrderDate);
            $this->date_next_echeance = $this->dates->formatDateMysqltoFr_HourOut($sLastOrderDate);
        }

        $this->montant_restant_du_emprunteur = $this->echeanciers_emprunteur->reste_a_payer_ra($this->projects->id_project, $iOrderEarlyRefund);
        $this->montant_restant_du_preteur    = $this->echeanciers->getRemainingCapitalAtDue($this->projects->id_project, $iOrderEarlyRefund);
        $resultat_num                        = bcsub($this->montant_restant_du_preteur, $this->montant_restant_du_emprunteur, 2);
        $this->ordre_echeance_ra             = $iOrderEarlyRefund;
        $this->remb_anticipe_effectue        = false;

        if ($this->projects->status == \projects_status::REMBOURSEMENT_ANTICIPE) {
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

        if (count($L_vrmt_anticipe) == 1 && $this->projects->status != \projects_status::REMBOURSEMENT_ANTICIPE) {
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
        $L_echeance_avant            = $this->echeanciers->select(" id_project = " . $this->projects->id_project . " AND status = 0 AND ordre < " . $this->ordre_echeance_ra);
        $this->ra_possible_all_payed = true;
        if (count($L_echeance_avant) > 0) {
            $this->phrase_resultat       = "<div style='color:red;'>Remboursement impossible <br />Toutes les &eacute;ch&eacute;ances pr&eacute;c&eacute;dentes ne sont pas rembours&eacute;es</div>";
            $this->ra_possible_all_payed = false;
        }
    }

    public function _send_cgv_ajax()
    {
        $this->hideDecoration();

        /** @var \clients $oClients */
        $oClients = $this->loadData('clients');
        /** @var \projects $oProjects */
        $oProjects = $this->loadData('projects');
        /** @var \companies $oCompanies */
        $oCompanies = $this->loadData('companies');
        /** @var \project_cgv $oProjectCgv */
        $oProjectCgv = $this->loadData('project_cgv');
        /** @var \settings $oSettings */
        $oSettings = $this->loadData('settings');

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
        $facebookUrl = $oSettings->value;

        $oSettings->get('Twitter', 'type');
        $twitterUrl = $oSettings->value;

        $oSettings->get('Part unilend', 'type');
        $commission = $oSettings->value;

        $oSettings->get('Commission remboursement', 'type');
        $owedCapitalCommission = $oSettings->value;

        /** @var \tax_type $taxType */
        $taxType = $this->loadData('tax_type');
        $taxType->get(\tax_type::TYPE_VAT);
        $vatRate = $taxType->rate / 100;

        $varMail = array(
            'surl'                 => $this->surl,
            'url'                  => $this->furl,
            'prenom_p'             => $oClients->prenom,
            'lien_cgv_universign'  => $sCgvLink,
            'lien_tw'              => $twitterUrl,
            'lien_fb'              => $facebookUrl,
            'commission_deblocage' => bcmul($commission / (1 + $vatRate), 100),
            'commission_crd'       => bcmul($owedCapitalCommission, 100),
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

    protected function generateBalanceLineHtml($codes, $formType, $extraClass = '')
    {
        $html = '';
        foreach($codes as $code) {
            $index = array_search($code, array_column($this->allTaxFormTypes[$formType], 'code'));
            $field = $this->allTaxFormTypes[$formType][$index];

            $html .= '<tr class="' . $extraClass . '"> <td>' . $field['label'] . '</td> <td width="45">' . $field['code'] . '</td>';
            $iColumn                 = 0;
            $iPreviousBalanceSheetId = null;

            foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
                if ($formType != $aBalanceSheet['form_type']) {
                    $html .= '<td></td>';
                    if ($iColumn) {
                        $html .= '<td></td>';
                    }
                } else {
                    $value = isset($aBalanceSheet['details'][$field['code']]) ? $aBalanceSheet['details'][$field['code']] : 0;
                    if ($iColumn) {
                        $previousValue = isset($this->aBalanceSheets[$iPreviousBalanceSheetId]['details'][$field['code']]) ? $this->aBalanceSheets[$iPreviousBalanceSheetId]['details'][$field['code']] : 0;
                        $movement      = empty($value) || empty($previousValue) ? 'N/A' : round(($previousValue - $value) / abs($value) * 100) . '&nbsp;%';
                        $html .= '<td>' . $movement . '</td>';

                    }
                    $formatedValue = $this->ficelle->formatNumber($value, 0);
                    $tabIndex      = 420 + $iColumn;
                    $html .= '<td><input type="text" class="numbers" name="box[' . $iBalanceSheetId . '][' . $field['code'] . ']" value="' . $formatedValue . '" tabindex="' . $tabIndex . '"/>&nbsp;€</td>';

                    $iPreviousBalanceSheetId = $iBalanceSheetId;
                }
                $iColumn++;
            }
            $html .= '</tr>';
        }

        return $html;
    }

    protected function generateBalanceSubTotalLineHtml($label, $codes, $formType, $domId = '')
    {
        $html = '<tr class="sub-total"><td colspan="2">' . $label . '</td>';
        $iPreviousTotal = null;
        $iColumn = 0;
        foreach ($this->aBalanceSheets as $aBalanceSheet) {
            if ($formType != $aBalanceSheet['form_type']) {
                $html .= '<td></td>';
                if ($iColumn) {
                    $html .= '<td></td>';
                }
            } else {
                $iTotal = $this->sumBalances($codes, $aBalanceSheet);

                if ($iColumn) {
                    $movement = empty($iTotal) || empty($iPreviousTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%';
                    $html .= '<td>' . $movement . '</td>';
                }
                $formatedValue = $this->ficelle->formatNumber($iTotal, 0);
                $html .= '<td id="'.$domId . '">' . $formatedValue . '</td>';
                $iPreviousTotal = $iTotal;
            }
            $iColumn ++;
        }
        $html .= '</tr>';

        return $html;
    }

    protected function generateBalanceGroupHtml($totalLabel, $code, $formType)
    {
        return $this->generateBalanceLineHtml($code, $formType) . $this->generateBalanceSubTotalLineHtml($totalLabel, $code, $formType);
    }

    protected function generateBalanceTotalLineHtml($label, $codes, $formType, $domId = '')
    {
        $html = '<tr><th colspan="2">' . $label . '</th>';
        $iPreviousTotal = null;
        $iIndex         = 0;
        $iColumn        = 0;
        foreach ($this->aBalanceSheets as $aBalanceSheet) {
            if ($formType != $aBalanceSheet['form_type']) {
                $html .= '<th></th>';
                if ($iColumn) {
                    $html .= '<th></th>';
                }
            } else {
                $iTotal = $this->sumBalances($codes, $aBalanceSheet);

                if ($iColumn) {
                    $movement = empty($iTotal) || empty($iPreviousTotal) ? 'N/A' : round(($iPreviousTotal - $iTotal) / abs($iTotal) * 100) . '&nbsp;%';
                    $html .= '<th>' . $movement . '</th>';
                }
                $formatedValue = $this->ficelle->formatNumber($iTotal, 0);
                $html .= '<th id="'.$domId . $iIndex++ . '">' . $formatedValue . '</th>';
                $iPreviousTotal = $iTotal;
            }
            $iColumn ++;
        }
        $html .= '</tr>';

        return $html;
    }

    protected function negtive($case)
    {
        if ('-' === substr($case, 0, 1)) {
            return substr($case, 1);
        } else {
            return '-' . $case;
        }
    }

    public function _regenerate_dirs()
    {
        $this->autoFireHead   = false;
        $this->autoFireHeader = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        /** @var \projects $project */
        $project = $this->loadData('projects');

        if (isset($this->params[0]) && $project->get($this->params[0])) {
            $path     = $this->path . 'public/default/var/dirs/';
            $filename = $project->slug . '.pdf';

            if (file_exists($path . $filename)) {
                if (false === is_dir($path . 'archives/' . $project->slug)) {
                    mkdir($path . 'archives/' . $project->slug, 0770, true);
                }

                rename(
                    $path . $filename,
                    $path . 'archives/' . $project->slug . '/' . date('Y-m-d H:i:s') . '.pdf'
                );
            }
        }
    }
}
