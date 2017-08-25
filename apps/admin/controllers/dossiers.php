<?php

use Unilend\Bundle\CoreBusinessBundle\Service\TermsOfSaleManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Partner;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\WSClientBundle\Entity\Altares\EstablishmentIdentityDetail;
use \Psr\Log\LoggerInterface;

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
    /** @var \companies */
    protected $targetCompany;
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

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';

        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
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

        /** @var \project_need $projectNeed */
        $projectNeed = $this->loadData('project_need');
        $this->needs = $projectNeed->getTree();

        if (isset($_POST['form_search_dossier'])) {
            $startDate          = empty($_POST['date1']) ? '' : \DateTime::createFromFormat('d/m/Y', $_POST['date1'])->format('Y-m-d');
            $endDate            = empty($_POST['date2']) ? '' : \DateTime::createFromFormat('d/m/Y', $_POST['date2'])->format('Y-m-d');
            $projectNeed        = empty($_POST['projectNeed']) ? '' : $_POST['projectNeed'];
            $duration           = empty($_POST['duree']) ? '' : $_POST['duree'];
            $status             = empty($_POST['status']) ? '' : $_POST['status'];
            $analyst            = empty($_POST['analyste']) ? '' : $_POST['analyste'];
            $siren              = empty($_POST['siren']) ? '' : $_POST['siren'];
            $projectId          = empty($_POST['id']) ? '' : $_POST['id'];
            $companyName        = empty($_POST['raison-sociale']) ? '' : $_POST['raison-sociale'];
            $commercial         = empty($_POST['commercial']) ? '' : $_POST['commercial'];
            $iNbStartPagination = isset($_POST['nbLignePagination']) ? (int) $_POST['nbLignePagination'] : 0;
            $this->nb_lignes    = isset($this->nb_lignes) ? (int) $this->nb_lignes : 100;
            $this->lProjects    = $this->projects->searchDossiers($startDate, $endDate, $projectNeed, $duration, $status, $analyst, $siren, $projectId, $companyName, null, $commercial, $iNbStartPagination, $this->nb_lignes);
        } elseif (isset($this->params[0])) {
            $this->lProjects = $this->projects->searchDossiers('', '', '', '', $this->params[0]);
        }

        $this->iCountProjects = isset($this->lProjects) && is_array($this->lProjects) ? array_shift($this->lProjects) : null;

        if (1 === $this->iCountProjects && (false === empty($projectId) || false === empty($companyName))) {
            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->lProjects[0]['id_project']);
            die;
        }
    }

    public function _edit()
    {
        $this->projects                      = $this->loadData('projects');
        $this->projects_status               = $this->loadData('projects_status');
        $this->projects_status_history       = $this->loadData('projects_status_history');
        $this->projects_notes                = $this->loadData('projects_notes');
        $this->project_cgv                   = $this->loadData('project_cgv');
        $this->companies                     = $this->loadData('companies');
        $this->targetCompany                 = $this->loadData('companies');
        $this->companies_actif_passif        = $this->loadData('companies_actif_passif');
        $this->company_balance               = $this->loadData('company_balance');
        $this->company_balance_type          = $this->loadData('company_balance_type');
        $this->companies_bilans              = $this->loadData('companies_bilans');
        $this->clients                       = $this->loadData('clients');
        $this->clients_adresses              = $this->loadData('clients_adresses');
        $this->loans                         = $this->loadData('loans');
        $this->projects_pouvoir              = $this->loadData('projects_pouvoir');
        $this->echeanciers                   = $this->loadData('echeanciers');
        $this->notifications                 = $this->loadData('notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->prescripteurs                 = $this->loadData('prescripteurs');
        $this->clients_prescripteurs         = $this->loadData('clients');
        $this->companies_prescripteurs       = $this->loadData('companies');
        $this->settings                      = $this->loadData('settings');
        /** @var \borrowing_motive $borrowingMotive */
        $borrowingMotive = $this->loadData('borrowing_motive');
        /** @var \company_tax_form_type $companyTaxFormType */
        $companyTaxFormType = $this->loadData('company_tax_form_type');
        /** @var \company_balance_type $companyBalanceDetailsType */
        $companyBalanceDetailsType = $this->loadData('company_balance_type');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->get('unilend.service.project_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager $productManager */
        $productManager = $this->get('unilend.service_product.product_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager $companyBalanceSheetManager */
        $companyBalanceSheetManager = $this->get('unilend.service.company_balance_sheet_manager');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->projectEntity   = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->projects->id_project);
            $this->taxFormTypes    = $companyTaxFormType->select();
            $this->allTaxFormTypes = [];

            foreach ($this->taxFormTypes as $formType) {
                $this->allTaxFormTypes[$formType['label']] = $companyBalanceDetailsType->select('id_company_tax_form_type = ' . $formType['id_type']);
            }

            $this->aBorrowingMotives = $borrowingMotive->select('rank');

            $this->settings->get('Cabinet de recouvrement', 'type');
            $this->cab = $this->settings->value;

            /** @var \tax_type $taxType */
            $taxType = $this->loadData('tax_type');

            $taxRate        = $taxType->getTaxRateByCountry('fr');
            $this->fVATRate = $taxRate[\Unilend\Bundle\CoreBusinessBundle\Entity\TaxType::TYPE_VAT] / 100;

            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');
            $this->clients_adresses->get($this->companies->id_client_owner, 'id_client');
            $this->projects_notes->get($this->projects->id_project, 'id_project');
            $this->project_cgv->get($this->projects->id_project, 'id_project');

            $this->projects_status->get($this->projects->status, 'status');
            $this->projects_status_history->loadLastProjectHistory($this->projects->id_project);

            if (
                $this->projects->status <= ProjectsStatus::COMMERCIAL_REVIEW
                && empty($this->projects->id_commercial)
                && empty($this->companies->phone)
                && 1 === preg_match('/^[0-9]{9}$/', $this->companies->siren)
                && ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN !== $this->projects_status_history->content
            ) {
                /** @var \Unilend\Bundle\WSClientBundle\Entity\Altares\EstablishmentIdentity $establishmentIdentity */
                $establishmentIdentity  = $this->get('unilend.service.ws_client.altares_manager')->getEstablishmentIdentity($this->companies->siren);

                if ($establishmentIdentity instanceof EstablishmentIdentityDetail && false === empty($establishmentIdentity->getPhoneNumber())) {
                    $this->companies->phone = $establishmentIdentity->getPhoneNumber();
                    $this->companies->update();
                }
            }
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
            $projectStatusManager = $this->get('unilend.service.project_status_manager');

            $this->rejectionReasonMessage = $projectStatusManager->getRejectionReasonTranslation($this->projects_status_history->content);
            $this->bHasAdvisor            = false;

            if ($this->projects->status == ProjectsStatus::FUNDE) {
                $proxy       = $this->projects_pouvoir->select('id_project = ' . $this->projects->id_project);
                $this->proxy = empty($proxy) ? [] : $proxy[0];

                /** @var \clients_mandats $clientMandate */
                $clientMandate = $this->loadData('clients_mandats');
                $mandate = $clientMandate->select('id_project = ' . $this->projects->id_project, 'updated DESC');
                $this->mandate = empty($mandate) ? [] : $mandate[0];

                $this->validBankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($this->clients->id_client);
            }

            if ($this->projects->id_prescripteur > 0 && $this->prescripteurs->get($this->projects->id_prescripteur, 'id_prescripteur')) {
                $this->clients_prescripteurs->get($this->prescripteurs->id_client, 'id_client');
                $this->companies_prescripteurs->get($this->prescripteurs->id_entite, 'id_company');
                $this->bHasAdvisor = true;
            }

            $this->latitude  = (float) $this->companies->latitude;
            $this->longitude = (float) $this->companies->longitude;

            $this->aAnnualAccountsDates = array();
            $this->aAnalysts            = $this->users->select('(status = 1 AND id_user_type = 2) OR id_user = ' . $this->projects->id_analyste);
            $this->aSalesPersons        = $this->users->select('(status = 1 AND id_user_type = 3) OR id_user = 23 OR id_user = ' . $this->projects->id_commercial); // ID user 23 corresponds to Arnaud
            $this->projectComments      = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:ProjectsComments')->findBy(['idProject' => $this->projects->id_project], ['added' => 'DESC']);
            $this->aAllAnnualAccounts   = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'cloture_exercice_fiscal DESC');
            $this->lProjects_status     = $projectStatusManager->getPossibleStatus($this->projects);

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
                    $this->incomeStatements[$balanceId] = $companyBalanceSheetManager->getIncomeStatement($this->companies_bilans, true);
                }
                if (count($this->lCompanies_actif_passif) < count($this->lbilans)) {
                    foreach (array_diff(array_column($this->lbilans, 'id_bilan'), array_column($this->lCompanies_actif_passif, 'id_bilan')) as $iAnnualAccountsId) {
                        if ($this->aBalanceSheets[$iAnnualAccountsId]['form_type'] == \company_tax_form_type::FORM_2033) {
                            /** @var companies_actif_passif $oAssetsDebts */
                            $oAssetsDebts           = $this->loadData('companies_actif_passif');
                            $oAssetsDebts->id_bilan = $iAnnualAccountsId;
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

            /** @var \project_need $projectNeed */
            $projectNeed      = $this->loadData('project_need');
            $needs            = $projectNeed->getTree();
            $this->needs      = $needs;
            $this->isTakeover = $this->isTakeover();
            $this->projectHasMonitoringEvent = $this->get('unilend.service.risk_data_monitoring_manager')->hasMonitoringEvent($this->companies->siren);

            if (isset($_POST['problematic_status']) && $this->projects->status != $_POST['problematic_status']) {
                $this->problematicStatusForm($_POST['problematic_status']);
            } elseif (isset($_POST['last_annual_accounts'])) {
                $this->projects->id_dernier_bilan = $_POST['last_annual_accounts'];
                $this->projects->update();

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            } elseif (isset($_POST['balance_count'])) {
                $this->projects->balance_count = $_POST['balance_count'];
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
            } elseif (isset($_POST['rejection_reason'])) {
                /** @var \projects_status_history $oProjectStatusHistory */
                $oProjectStatusHistory = $this->loadData('projects_status_history');

                if ($oProjectStatusHistory->loadLastProjectHistory($this->projects->id_project)) {
                    /** @var \projects_status_history_details $oProjectsStatusHistoryDetails */
                    $oProjectsStatusHistoryDetails = $this->loadData('projects_status_history_details');

                    $bCreate = (false === $oProjectsStatusHistoryDetails->get($oProjectStatusHistory->id_project_status_history, 'id_project_status_history'));

                    switch ($this->projects->status) {
                        case ProjectsStatus::COMMERCIAL_REJECTION:
                            $oProjectsStatusHistoryDetails->commercial_rejection_reason = $_POST['rejection_reason'];
                            break;
                        case ProjectsStatus::ANALYSIS_REJECTION:
                            $oProjectsStatusHistoryDetails->analyst_rejection_reason = $_POST['rejection_reason'];
                            break;
                        case ProjectsStatus::COMITY_REJECTION:
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
            } elseif (isset($_POST['pret_refuse']) && $_POST['pret_refuse'] == 1) {
                if ($this->projects->status < ProjectsStatus::PRET_REFUSE) {
                    $loanRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
                    /** @var \echeanciers $echeanciers */
                    $echeanciers = $this->loadData('echeanciers');
                    /** @var LoggerInterface $logger */
                    $logger = $this->get('logger');

                    $this->settings->get('Facebook', 'type');
                    $facebookLink = $this->settings->value;

                    $this->settings->get('Twitter', 'type');
                    $twitterLink = $this->settings->value;

                    $lendersCount = $loanRepository->getLenderNumber($this->projects->id_project);

                    $entityManager->getConnection()->beginTransaction();
                    try {
                        $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::PRET_REFUSE, $this->projects);

                        $echeanciers->delete($this->projects->id_project, 'id_project');

                        $loans = $loanRepository->findBy(['idProject' => $this->projects->id_project, 'status' => Loans::STATUS_ACCEPTED]);

                        foreach ($loans as $loan) {
                            $loan->setStatus(Loans::STATUS_REJECTED);
                            $entityManager->flush($loan);

                            $this->get('unilend.service.operation_manager')->refuseLoan($loan);
                            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
                            $wallet  = $loan->getIdLender();
                            $varMail = [
                                'surl'              => $this->surl,
                                'url'               => $this->furl,
                                'prenom_p'          => $wallet->getIdClient()->getPrenom(),
                                'valeur_bid'        => $this->ficelle->formatNumber($loan->getAmount() / 100, 0),
                                'nom_entreprise'    => $this->companies->name,
                                'nb_preteurMoinsUn' => $lendersCount - 1,
                                'motif_virement'    => $wallet->getWireTransferPattern(),
                                'lien_fb'           => $facebookLink,
                                'lien_tw'           => $twitterLink
                            ];

                            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-pret-refuse', $varMail);
                            try {
                                $message->setTo($wallet->getIdClient()->getEmail());
                                $mailer = $this->get('mailer');
                                $mailer->send($message);
                            } catch (\Exception $exception) {
                                $logger->warning(
                                    'Could not send email: preteur-pret-refuse - Exception: ' . $exception->getMessage(),
                                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $wallet->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                                );
                            }
                        }
                        $entityManager->getConnection()->commit();
                    } catch (Exception $exception) {
                        $entityManager->getConnection()->rollBack();

                        $_SESSION['freeow']['title']   = 'Refus de prêt';
                        $_SESSION['freeow']['message'] = 'Une erreur est survenu. Le prêt n\'a pas été refusé';

                        $this->get('logger')->error('Error occurs when refuse the loans. The process has benn rollbacked. Error: ' . $exception->getMessage());
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
                $_SESSION['freeow']['title']   = 'Sauvegarde du résumé';
                $_SESSION['freeow']['message'] = '';

                $resetFundsCommissionRate = false;

                if ($this->projects->status <= ProjectsStatus::PREP_FUNDING) {
                    if (false === empty($_POST['partner']) && $this->projects->id_partner != $_POST['partner']) {
                        $this->projects->id_partner                = $_POST['partner'];
                        $this->projects->id_product                = 0;
                        $this->projects->commission_rate_funds     = null;
                        $this->projects->commission_rate_repayment = null;

                        $resetFundsCommissionRate = true;
                    }

                    /** @var \product $product */
                    $product = $this->loadData('product');
                    /** @var \partner_product $partnerProduct */
                    $partnerProduct = $this->loadData('partner_product');

                    if (
                        false === empty($_POST['product'])
                        && false === empty($this->projects->id_partner)
                        && $product->get($_POST['product'])
                        && $partnerProduct->get($_POST['product'], 'id_partner = ' . $this->projects->id_partner . ' AND id_product')
                        && $productManager->isProjectEligible($this->projects, $product)
                    ) {
                        if ($this->projects->id_product != $partnerProduct->id_product) {
                            $resetFundsCommissionRate = true;
                        }

                        $this->projects->id_product                = $partnerProduct->id_product;
                        $this->projects->commission_rate_repayment = $partnerProduct->commission_rate_repayment;

                        if ($resetFundsCommissionRate) {
                            $this->projects->commission_rate_funds = $partnerProduct->commission_rate_funds;
                        }
                    }
                }

                if (
                    false === $resetFundsCommissionRate
                    && false === empty($_POST['specific_commission_rate_funds'])
                    && $this->isFundsCommissionRateEditable()
                ) {
                    $this->projects->commission_rate_funds = $this->ficelle->cleanFormatedNumber($_POST['specific_commission_rate_funds']);
                }

                $serialize = serialize(array('id_project' => $this->projects->id_project, 'post' => $_POST));
                $this->users_history->histo(10, 'dossier edit Resume & actions', $_SESSION['user']['id_user'], $serialize);

                if (false === empty($_POST['date_publication'])) {
                    $publicationDate                = \DateTime::createFromFormat('d/m/YHi', $_POST['date_publication'] . $_POST['date_publication_heure'] . $_POST['date_publication_minute']);
                    $endOfPublicationDate           = \DateTime::createFromFormat('d/m/YHi', $_POST['date_retrait'] . $_POST['date_retrait_heure'] . $_POST['date_retrait_minute']);
                    $publicationLimitationDate      = new \DateTime('NOW + 5 minutes');
                    $endOfPublicationLimitationDate = new \DateTime('NOW + 1 hour');

                    if (
                        $publicationDate->format('Y-m-d H:i:s') !== $this->projects->date_publication
                        && ($publicationDate <= $publicationLimitationDate || $endOfPublicationDate <= $endOfPublicationLimitationDate)
                    ) {
                        $_SESSION['public_dates_error'] = 'La date de publication du dossier doit être au minimum dans 5 minutes et la date de retrait dans plus d\'une heure';

                        header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                        die;
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
                    false === empty($_POST['commercial'])
                    && $_POST['commercial'] != $this->projects->id_commercial
                    && $this->projects->status < ProjectsStatus::COMMERCIAL_REVIEW
                ) {
                    if (ProjectsStatus::NOT_ELIGIBLE != $this->projects->status) {
                        $_POST['status'] = ProjectsStatus::COMMERCIAL_REVIEW;
                    }

                    $latitude  = (float) $this->companies->latitude;
                    $longitude = (float) $this->companies->longitude;

                    if (empty($latitude) && empty($longitude)) {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LocationManager $location */
                        $location    = $this->get('unilend.service.location_manager');
                        $coordinates = $location->getCompanyCoordinates($this->companies);

                        if ($coordinates) {
                            $this->companies->latitude  = $coordinates['latitude'];
                            $this->companies->longitude = $coordinates['longitude'];
                        }
                    }
                }

                if (
                    false === empty($_POST['analyste'])
                    && $_POST['analyste'] != $this->projects->id_analyste
                    && $this->projects->status < ProjectsStatus::ANALYSIS_REVIEW
                ) {
                    $_POST['status'] = ProjectsStatus::ANALYSIS_REVIEW;
                }

                if ($this->projects->create_bo && empty($this->clients->source) && isset($_POST['source'])) {
                    $this->clients->source = $_POST['source'];
                    $this->clients->update();
                }

                $this->companies->sector       = isset($_POST['sector']) ? $_POST['sector'] : $this->companies->sector;
                $this->companies->name         = $_POST['societe'];
                $this->companies->tribunal_com = $_POST['tribunal_com'];
                $this->companies->activite     = $_POST['activite'];
                $this->companies->update();

                $this->projects->title               = $_POST['title'];
                $this->projects->id_analyste         = isset($_POST['analyste']) ? $_POST['analyste'] : $this->projects->id_analyste;
                $this->projects->id_commercial       = isset($_POST['commercial']) ? $_POST['commercial'] : $this->projects->id_commercial;
                $this->projects->id_borrowing_motive = $_POST['motive'];

                if ($this->projects->status <= ProjectsStatus::COMITY_REVIEW) {
                    $this->projects->id_project_need = $_POST['need'];
                    $this->projects->period          = $_POST['duree'];
                    $this->projects->amount          = $this->ficelle->cleanFormatedNumber($_POST['montant']);

                    if (false === $this->isTakeover() && false === empty($this->projects->id_target_company)) {
                        $this->projects->id_target_company = 0;
                    }
                }

                if ($this->projects->status <= ProjectsStatus::PREP_FUNDING) {
                    if (false === empty($_POST['project_partner'])) {
                        $this->projects->id_partner                = $_POST['project_partner'];
                        $this->projects->id_product                = null;
                        $this->projects->commission_rate_funds     = null;
                        $this->projects->commission_rate_repayment = null;
                    }

                    /** @var \partner_product $partnerProduct */
                    $partnerProduct = $this->loadData('partner_product');

                    if (
                        false === empty($_POST['assigned_product'])
                        && $partnerProduct->get($_POST['assigned_product'], 'id_partner = ' . $this->projects->id_partner . ' AND id_product')
                    ) {
                        $this->projects->id_product                = $partnerProduct->id_product;
                        $this->projects->commission_rate_funds     = $partnerProduct->commission_rate_funds;
                        $this->projects->commission_rate_repayment = $partnerProduct->commission_rate_repayment;
                    } elseif (false === empty($_POST['assigned_product'])) {
                        $_SESSION['freeow']['message'] .= 'Ce produit n\'est pas configuré pour le partenaire<br>';
                    }
                }

                if ($this->projects->status <= ProjectsStatus::A_FUNDER) {
                    $sector = $this->translator->trans('company-sector_sector-' . $this->companies->sector);
                    $this->settings->get('Prefixe URL pages projet', 'type');
                    $this->projects->slug = $this->ficelle->generateSlug($this->settings->value . '-' . $sector . '-' . $this->companies->city . '-' . substr(md5($this->projects->title . $this->projects->id_project), 0, 7));
                }

                if ($this->projects->status == ProjectsStatus::A_FUNDER) {
                    if (isset($_POST['date_publication']) && ! empty($_POST['date_publication'])) {
                        $publicationDate                  = \DateTime::createFromFormat('d/m/Y H:i', $_POST['date_publication'] . ' ' . $_POST['date_publication_heure'] . ':' . $_POST['date_publication_minute']);
                        $this->projects->date_publication = $publicationDate->format('Y-m-d H:i:s');
                    }

                    if (isset($_POST['date_retrait']) && ! empty($_POST['date_retrait'])) {
                        $endOfPublicationDate         = \DateTime::createFromFormat('d/m/Y H:i', $_POST['date_retrait'] . ' ' . $_POST['date_retrait_heure'] . ':' . $_POST['date_retrait_minute']);
                        $this->projects->date_retrait = $endOfPublicationDate->format('Y-m-d H:i:s');
                    }
                }

                if ($this->projects->status >= ProjectsStatus::PREP_FUNDING) {
                    if (false === empty($this->projects->risk) && false === empty($this->projects->period)) {
                        try {
                            $this->projects->id_rate = $oProjectManager->getProjectRateRangeId($this->projectEntity);
                        } catch (\Exception $exception) {
                            $_SESSION['freeow']['message'] .= $exception->getMessage();
                        }
                    }
                }

                $this->projects->update();

                if (isset($_POST['current_status']) && $_POST['status'] != $_POST['current_status'] && $this->projects->status != $_POST['status']) {
                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], $_POST['status'], $this->projects);
                }

                $_SESSION['freeow']['message'] .= 'Modifications enregistrées avec succès';

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            } elseif (isset($_POST['send_form_date_retrait'])) {
                if (
                    isset($_POST['date_retrait'], $_POST['date_retrait_heure'], $_POST['date_retrait_minute'])
                    && 1 === preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{8}#', $_POST['date_retrait'] . $_POST['date_retrait_heure'] . $_POST['date_retrait_minute'])
                    && $this->projects->status <= ProjectsStatus::EN_FUNDING
                ) {
                    $endOfPublicationDate = \DateTime::createFromFormat('d/m/YHi', $_POST['date_retrait'] . $_POST['date_retrait_heure'] . $_POST['date_retrait_minute']);

                    if ($endOfPublicationDate > new \DateTime()) {
                        $this->projects->date_retrait = $endOfPublicationDate->format('Y-m-d H:i:s');
                        $this->projects->update();
                    }
                }

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            }

            /** @var \project_need $oProjectNeed */
            $oProjectNeed = $this->loadData('project_need');
            $needs        = $oProjectNeed->getTree();
            $this->aNeeds = $needs;

            if (in_array($this->projects->status, [ProjectsStatus::COMMERCIAL_REJECTION, ProjectsStatus::ANALYSIS_REJECTION, ProjectsStatus::COMITY_REJECTION])) {
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

            $this->xerfi                 = $this->loadData('xerfi');
            $this->sectors               = $this->loadData('company_sector')->select();
            $this->sources               = array_column($this->clients->select('source NOT LIKE "http%" AND source NOT IN ("", "1") GROUP BY source'), 'source');
            $this->ratings               = $this->loadRatings($this->companies, $this->projects->id_company_rating_history, $this->xerfi);
            $this->aCompanyProjects      = $this->companies->getProjectsBySIREN();
            $this->iCompanyProjectsCount = count($this->aCompanyProjects);
            $this->fCompanyOwedCapital   = $this->companies->getOwedCapitalBySIREN();
            $this->bIsProblematicCompany = $this->companies->countProblemsBySIREN() > 0;

            /** @var \product $product */
            $product = $this->loadData('product');

            $this->settings->get('Durée des prêts autorisées', 'type');
            $this->dureePossible      = explode(',', $this->settings->value);
            $this->availableContracts = [];

            if (false === empty($this->projects->id_product) && $product->get($this->projects->id_product)) {
                $durationMax = $productManager->getMaxEligibleDuration($product);
                $durationMin = $productManager->getMinEligibleDuration($product);

                foreach ($this->dureePossible as $index => $duration) {
                    if (
                        is_numeric($durationMax) && $duration > $durationMax
                        || is_numeric($durationMin) && $duration < $durationMin
                    ) {
                        unset($this->dureePossible[$index]);
                    }
                }

                $this->availableContracts = array_column($productManager->getAvailableContracts($product), 'label');
            }

            if (false === in_array($this->projects->period, [0, 1000000]) && false === in_array($this->projects->period, $this->dureePossible)) {
                array_push($this->dureePossible, $this->projects->period);
                sort($this->dureePossible);
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\PartnerRepository $partnerRepository */
            $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');

            $this->eligibleProducts = $productManager->findEligibleProducts($this->projects, true);
            $this->selectedProduct  = $product;
            $this->isProductUsable  = empty($product->id_product) ? false : in_array($this->selectedProduct, $this->eligibleProducts);
            $this->partnerList      = $partnerRepository->getPartnersSortedByName(Partner::STATUS_VALIDATED);
            $this->partnerProduct   = $this->loadData('partner_product');

            if (false === empty($this->projects->id_product)) {
                $this->partnerProduct->get($this->projects->id_product, 'id_partner = ' . $this->projects->id_partner . ' AND id_product');
            }

            if (false === empty($this->projects->risk) && false === empty($this->projects->period) && $this->projects->status >= ProjectsStatus::PREP_FUNDING) {
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

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
            $attachmentManager = $this->get('unilend.service.attachment_manager');

            $this->aAttachments                   = $this->projectEntity->getAttachments();
            $this->aAttachmentTypes               = $attachmentManager->getAllTypesForProjects();
            $this->attachmentTypesForCompleteness = $attachmentManager->getAllTypesForProjects(false);
            $this->isFundsCommissionRateEditable  = $this->isFundsCommissionRateEditable();
            $this->lastBalanceSheet               = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneBy([
                'idClient' => $this->projectEntity->getIdCompany()->getIdClientOwner(),
                'idType'   => \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::DERNIERE_LIASSE_FISCAL
            ]);

            $this->aMandatoryAttachmentTypes = [];
            $partnerAttachments              = $partnerRepository->find($this->projects->id_partner)->getAttachmentTypes(true);
            foreach ($partnerAttachments as $partnerAttachment) {
                $this->aMandatoryAttachmentTypes[] = $partnerAttachment->getAttachmentType();
            }

            if ($this->isTakeover()) {
                $this->loadTargetCompany();
            }
            $this->loadEarlyRepaymentInformation(false);
            $this->treeRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Tree');
            $this->legalDocuments = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs')->findBy(['idClient' => $this->clients->id_client]);

            $this->transferFunds($this->projectEntity);
        } else {
            header('Location: ' . $this->lurl . '/dossiers');
            die;
        }
    }

    private function transferFunds(Projects $project)
    {
        if ($project->getStatus() >= ProjectsStatus::REMBOURSEMENT) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager              = $this->get('unilend.service.project_manager');
            $this->companyRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
            $this->bankAccountRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount');
            $this->currencyFormatter     = $this->get('currency_formatter');

            $restFunds                   = $projectManager->getRestOfFundsToRelease($project, true);
            $this->wireTransferOuts      = $project->getWireTransferOuts();
            $this->restFunds             = $this->currencyFormatter->formatCurrency($restFunds, 'EUR');
            $this->displayAddButton      = $restFunds > 0;
        }
    }

    /**
     * @return bool
     */
    private function isFundsCommissionRateEditable()
    {
        return (
            $this->projects->status <= ProjectsStatus::FUNDE
            && false === empty($this->projects->id_product)
            && in_array($_SESSION['user']['id_user_type'], [\users_types::TYPE_ADMIN, \users_types::TYPE_DIRECTION])
        );
    }

    /**
     * @param array $balances
     * @param array $balanceSheet
     * @return float
     */
    protected function sumBalances(array $balances, array $balanceSheet)
    {
        $total = 0.0;
        foreach ($balances as $balance) {
            if ('-' === substr($balance, 0, 1)) {
                $total -= $balanceSheet['details'][substr($balance, 1)];
            } else {
                $total += $balanceSheet['details'][$balance];
            }
        }
        return $total;
    }

    private function problematicStatusForm($iStatus)
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        $projectManager->addProjectStatus($_SESSION['user']['id_user'], $_POST['problematic_status'], $this->projects);

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
        if (in_array($iStatus, array(ProjectsStatus::PROCEDURE_SAUVEGARDE, ProjectsStatus::REDRESSEMENT_JUDICIAIRE, ProjectsStatus::LIQUIDATION_JUDICIAIRE, ProjectsStatus::DEFAUT))) {
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
            case ProjectsStatus::PROBLEME:
                $sMailType = 'emprunteur-projet-statut-probleme';
                break;
            case ProjectsStatus::PROBLEME_J_X:
                $sMailType = 'emprunteur-projet-statut-probleme-j-x';
                break;
            case ProjectsStatus::RECOUVREMENT:
                $sMailType = 'emprunteur-projet-statut-recouvrement';
                break;
            case ProjectsStatus::PROCEDURE_SAUVEGARDE:
                $sMailType = 'emprunteur-projet-statut-procedure-sauvegarde';
                break;
            case ProjectsStatus::REDRESSEMENT_JUDICIAIRE:
                $sMailType = 'emprunteur-projet-statut-redressement-judiciaire';
                break;
            case ProjectsStatus::LIQUIDATION_JUDICIAIRE:
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

        if (in_array($iStatus, array(ProjectsStatus::PROBLEME, ProjectsStatus::PROBLEME_J_X))) {
            $aNextRepayment = $oPaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND date_echeance_emprunteur > "' . date('Y-m-d') . '"', 'date_echeance_emprunteur ASC', 0, 1);
            $oNow           = new \DateTime();
            $aReplacements['delai_regularisation'] = $oNow->diff(new \DateTime($aNextRepayment[0]['date_echeance_emprunteur']))->days;
            if ($aReplacements['delai_regularisation'] >= 2) {
                $aReplacements['delai_regularisation'] .= ' jours';
            } else {
                $aReplacements['delai_regularisation'] .= ' jour';
            }
        }

        if (in_array($iStatus, array(ProjectsStatus::RECOUVREMENT, ProjectsStatus::PROCEDURE_SAUVEGARDE, ProjectsStatus::REDRESSEMENT_JUDICIAIRE, ProjectsStatus::LIQUIDATION_JUDICIAIRE))) {
            /** @var \echeanciers $oLenderRepaymentSchedule */
            $oLenderRepaymentSchedule = $this->loadData('echeanciers');
            $aReplacements['CRD'] = $this->ficelle->formatNumber($oLenderRepaymentSchedule->getOwedCapital(array('id_project' => $this->projects->id_project)));

            if (ProjectsStatus::RECOUVREMENT == $iStatus) {
                $aReplacements['mensualites_impayees'] = $this->ficelle->formatNumber($oLenderRepaymentSchedule->getUnpaidAmountAtDate($this->projects->id_project, new \DateTime('NOW')));
            }
        }

        $aFundingDate = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . ProjectsStatus::REMBOURSEMENT . ')', 'added ASC, id_project_status_history ASC', 0, 1);
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

        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        $logger->debug('Mail to send : ' . $sMailType . ' Variables : ' . json_encode($aReplacements), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $this->projects->id_project]);

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($sMailType, $aReplacements);
        try {
            $message->setTo($this->clients->email);
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email: ' . $sMailType . ' - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $this->clients->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    private function sendProblemStatusEmailLender($iStatus, $projectStatusHistoryDetails)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager       = $this->get('doctrine.orm.entity_manager');
        /** @var NumberFormatter $numberFormatter */
        $numberFormatter     = $this->get('number_formatter');
        $walletRepository    = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');


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
            case ProjectsStatus::PROBLEME:
                $iNotificationType = Notifications::TYPE_PROJECT_PROBLEM;
                $sEmailTypePerson  = 'preteur-projet-statut-probleme';
                $sEmailTypeSociety = 'preteur-projet-statut-probleme';
                break;
            case ProjectsStatus::PROBLEME_J_X:
                $iNotificationType = Notifications::TYPE_PROJECT_PROBLEM_REMINDER;
                $sEmailTypePerson  = 'preteur-projet-statut-probleme-j-x';
                $sEmailTypeSociety = 'preteur-projet-statut-probleme-j-x';
                break;
            case ProjectsStatus::RECOUVREMENT:
                $iNotificationType = Notifications::TYPE_PROJECT_RECOVERY;
                $sEmailTypePerson  = 'preteur-projet-statut-recouvrement';
                $sEmailTypeSociety = 'preteur-projet-statut-recouvrement';
                break;
            case ProjectsStatus::PROCEDURE_SAUVEGARDE:
                $iNotificationType = Notifications::TYPE_PROJECT_PRECAUTIONARY_PROCESS;
                $sEmailTypePerson  = 'preteur-projet-statut-procedure-sauvegarde';
                $sEmailTypeSociety = 'preteur-projet-statut-procedure-sauvegarde';
                break;
            case ProjectsStatus::REDRESSEMENT_JUDICIAIRE:
                $iNotificationType  = Notifications::TYPE_PROJECT_RECEIVERSHIP;
                $aCollectiveProcess = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status IN (SELECT id_project_status FROM projects_status WHERE status = ' . ProjectsStatus::PROCEDURE_SAUVEGARDE . ')', 'added ASC, id_project_status_history ASC', 0, 1);

                if (empty($aCollectiveProcess)) {
                    $sEmailTypePerson  = 'preteur-projet-statut-redressement-judiciaire';
                    $sEmailTypeSociety = 'preteur-projet-statut-redressement-judiciaire';
                } else {
                    $sEmailTypePerson  = 'preteur-projet-statut-redressement-judiciaire-post-procedure';
                    $sEmailTypeSociety = 'preteur-projet-statut-redressement-judiciaire-post-procedure';
                }
                break;
            case ProjectsStatus::LIQUIDATION_JUDICIAIRE:
                $iNotificationType  = Notifications::TYPE_PROJECT_COMPULSORY_LIQUIDATION;
                $aCollectiveProcess = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status IN (SELECT id_project_status FROM projects_status WHERE status IN (' . ProjectsStatus::PROCEDURE_SAUVEGARDE . ', ' . ProjectsStatus::REDRESSEMENT_JUDICIAIRE . '))', 'added ASC, id_project_status_history ASC', 0, 1);

                if (empty($aCollectiveProcess)) {
                    $sEmailTypePerson  = 'preteur-projet-statut-liquidation-judiciaire';
                    $sEmailTypeSociety = 'preteur-projet-statut-liquidation-judiciaire';
                } else {
                    $sEmailTypePerson  = 'preteur-projet-statut-liquidation-judiciaire-post-procedure';
                    $sEmailTypeSociety = 'preteur-projet-statut-liquidation-judiciaire-post-procedure';
                }
                break;
            case ProjectsStatus::DEFAUT:
                $iNotificationType = Notifications::TYPE_PROJECT_FAILURE;
                $sEmailTypePerson  = 'preteur-projet-statut-defaut-personne-physique';
                $sEmailTypeSociety = 'preteur-projet-statut-defaut-personne-morale';

                $aCompulsoryLiquidation = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . ProjectsStatus::LIQUIDATION_JUDICIAIRE . ')', 'added ASC, id_project_status_history ASC', 0, 1);
                $aCommonReplacements['date_annonce_liquidation_judiciaire'] = date('d/m/Y', strtotime($aCompulsoryLiquidation[0]['added']));
                break;
        }

        $aRepaymentStatus = $this->projects_status_history->select('id_project = ' . $this->projects->id_project . ' AND id_project_status = (SELECT id_project_status FROM projects_status WHERE status = ' . ProjectsStatus::REMBOURSEMENT . ')', 'added ASC, id_project_status_history ASC', 0, 1);
        $aCommonReplacements['annee_projet'] = date('Y', strtotime($aRepaymentStatus[0]['added']));

        if (in_array($iStatus, [ProjectsStatus::PROCEDURE_SAUVEGARDE, ProjectsStatus::REDRESSEMENT_JUDICIAIRE, ProjectsStatus::LIQUIDATION_JUDICIAIRE])) {
            $oMaxClaimsSendingDate = new \DateTime($projectStatusHistoryDetails->date);
            $aCommonReplacements['date_max_envoi_declaration_creances'] = date('d/m/Y', $oMaxClaimsSendingDate->add(new \DateInterval('P2M'))->getTimestamp());
        }

        $aLenderLoans = $this->loans->getProjectLoansByLender($this->projects->id_project);

        if (is_array($aLenderLoans)) {
            $aNextRepayment = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND date_echeance > "' . date('Y-m-d') . '"', 'date_echeance ASC', 0, 1);
            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');

            foreach ($aLenderLoans as $aLoans) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
                $wallet = $walletRepository->find($aLoans['id_lender']);

                $netRepayment = 0.0;
                $loansCount   = $aLoans['cnt'];
                $loansAmount  = round(bcdiv($aLoans['amount'], 100, 4), 2);

                foreach ($this->echeanciers->select('id_loan IN (' . $aLoans['loans'] . ') AND id_project = ' . $this->projects->id_project . ' AND status = 1') as $aPayment) {
                    $netRepayment += $operationRepository->getNetAmountByRepaymentScheduleId($aPayment['id_echeancier']);
                }

                $this->notifications->type       = $iNotificationType;
                $this->notifications->id_lender  = $aLoans['id_lender'];
                $this->notifications->id_project = $this->projects->id_project;
                $this->notifications->amount     = bcsub($loansAmount, 100);
                $this->notifications->id_bid     = 0;
                $this->notifications->create();

                if (
                    in_array($iStatus, [ProjectsStatus::PROCEDURE_SAUVEGARDE, ProjectsStatus::REDRESSEMENT_JUDICIAIRE, ProjectsStatus::LIQUIDATION_JUDICIAIRE, ProjectsStatus::DEFAUT])
                    || $this->clients_gestion_notifications->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM, 'immediatement')
                ) {
                    $this->clients_gestion_mails_notif->id_client       = $wallet->getIdClient()->getIdClient();
                    $this->clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_PROJECT_PROBLEM;
                    $this->clients_gestion_mails_notif->id_notification = $this->notifications->id_notification;
                    $this->clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                    $this->clients_gestion_mails_notif->id_loan         = 0;
                    $this->clients_gestion_mails_notif->immediatement   = 1;
                    $this->clients_gestion_mails_notif->create();

                    $aReplacements = $aCommonReplacements + [
                        'prenom_p'                    => $wallet->getIdClient()->getPrenom(),
                        'entreprise'                  => $this->companies->name,
                        'montant_pret'                => $numberFormatter->format($loansAmount),
                        'montant_rembourse'           => '<span style=\'color:#b20066;\'>' . $numberFormatter->format($netRepayment) . '&nbsp;euros</span> vous ont d&eacute;j&agrave; &eacute;t&eacute; rembours&eacute;s.<br/><br/>',
                        'nombre_prets'                => $loansCount . ' ' . (($loansCount > 1) ? 'pr&ecirc;ts' : 'pr&ecirc;t'), // @todo intl
                        'date_prochain_remboursement' => $this->dates->formatDate($aNextRepayment[0]['date_echeance'], 'd/m/Y'), // @todo intl
                        'CRD'                         => $numberFormatter->format($loansAmount - $netRepayment)
                    ];

                    $sMailType = ($wallet->getIdClient()->isNaturalPerson()) ? $sEmailTypePerson : $sEmailTypeSociety;
                    $locale    = $this->getParameter('locale');
                    $this->mail_template->get($sMailType, 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $locale . '" AND type');
                    $aReplacements['sujet'] = $this->mail_template->subject;

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($sMailType, $aReplacements);
                    try {
                        $message->setTo($wallet->getIdClient()->getEmail());
                        $mailer = $this->get('mailer');
                        $mailer->send($message);
                    } catch (\Exception $exception) {
                        $logger->warning(
                            'Could not send email: ' . $sMailType . ' - Exception: ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $wallet->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param \companies  $company
     * @param int|null    $companyRatingHistoryId
     * @param \xerfi|null $xerfi
     * @return array
     */
    private function loadRatings(\companies &$company, $companyRatingHistoryId = null, \xerfi &$xerfi = null)
    {
        $return = [];

        if (null === $companyRatingHistoryId) {
            /** @var \company_rating_history $companyRatingHistory */
            $companyRatingHistory = $this->loadData('company_rating_history');
            $companyRatingHistory = $companyRatingHistory->select('id_company = ' . $company->id_company, 'added DESC', 0, 1);

            if (isset($companyRatingHistory[0]['id_company_rating_history'])) {
                $companyRatingHistoryId = $companyRatingHistory[0]['id_company_rating_history'];
            }
        }

        if (null === $xerfi) {
            /** @var \xerfi $xerfi */
            $xerfi = $this->loadData('xerfi');
        }

        if (false === empty($company->code_naf)) {
            $xerfi->get($company->code_naf, 'naf');
        }

        if (false === empty($companyRatingHistoryId)) {
            $return['id_company_rating_history'] = $companyRatingHistoryId;

            /** @var \company_rating $companyRating */
            $companyRating = $this->loadData('company_rating');
            $ratings       = $companyRating->getHistoryRatingsByType($companyRatingHistoryId, true);

            if (
                (false === isset($ratings['xerfi']) || false === isset($ratings['xerfi_unilend']))
                && false === empty($company->code_naf)
            ) {
                if (empty($xerfi->naf)) {
                    $xerfiScore   = 'N/A';
                    $xerfiUnilend = 'PAS DE DONNEES';
                } elseif ('' === $xerfi->score) {
                    $xerfiScore   = 'N/A';
                    $xerfiUnilend = $xerfi->unilend_rating;
                } else {
                    $xerfiScore   = $xerfi->score;
                    $xerfiUnilend = $xerfi->unilend_rating;
                }

                if (false === isset($ratings['xerfi'])) {
                    $companyRating->id_company_rating_history = $companyRatingHistoryId;
                    $companyRating->type                      = 'xerfi';
                    $companyRating->value                     = $xerfiScore;
                    $companyRating->create();
                }

                if (false === isset($ratings['xerfi_unilend'])) {
                    $companyRating->id_company_rating_history = $companyRatingHistoryId;
                    $companyRating->type                      = 'xerfi_unilend';
                    $companyRating->value                     = $xerfiUnilend;
                    $companyRating->create();
                }

                $ratings = $companyRating->getHistoryRatingsByType($companyRatingHistoryId, true);
            }

            foreach ($ratings as $ratingType => $rating) {
                switch ($rating['action']) {
                    case \company_rating_history::ACTION_WS:
                        $action = 'Webservice';
                        $user   = '';
                        break;
                    case \company_rating_history::ACTION_XERFI:
                        $action = 'Automatique';
                        $user   = '';
                        break;
                    case \company_rating_history::ACTION_USER:
                    default:
                        $action = 'Manuel';
                        $user   = $rating['user'];
                        break;
                }

                $return[$ratingType] = [
                    'value'  => $rating['value'],
                    'date'   => $rating['added']->format('d/m/Y H:i'),
                    'action' => $action,
                    'user'   => $user
                ];
            }
        }

        return $return;
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

        /** @var \tax_type $taxType */
        $taxType = $this->loadData('tax_type');

        $taxRate        = $taxType->getTaxRateByCountry('fr');
        $this->fVATRate = $taxRate[\Unilend\Bundle\CoreBusinessBundle\Entity\TaxType::TYPE_VAT] / 100;

        /** @var company_rating $oCompanyRating */
        $oCompanyRating = $this->loadData('company_rating');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager $companyBalanceSheetManager */
        $companyBalanceSheetManager = $this->get('unilend.service.company_balance_sheet_manager');

        $this->ratings                 = $oCompanyRating->getHistoryRatingsByType($this->oProject->id_company_rating_history);
        $this->aAnnualAccounts          = $oAnnualAccounts->select('id_company = ' . $this->oCompany->id_company . ' AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $this->oProject->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);
        $aAnnualAccountsIds             = array_column($this->aAnnualAccounts, 'id_bilan');
        $this->bIsProblematicCompany    = $this->oCompany->countProblemsBySIREN() > 0;
        $this->iDeclaredRevenue         = $this->oProject->ca_declara_client;
        $this->iDeclaredOperatingIncome = $this->oProject->resultat_exploitation_declara_client;
        $this->iDeclaredCapitalStock    = $this->oProject->fonds_propres_declara_client;
        $this->aCompanyProjects         = $this->oCompany->getProjectsBySIREN();
        $this->fCompanyOwedCapital      = $this->oCompany->getOwedCapitalBySIREN();
        $this->aBalanceSheets           = $companyBalanceSheetManager->getBalanceSheetsByAnnualAccount($aAnnualAccountsIds);

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
        $this->aRejectionReasons = $oProjectRejectionReason->select('', 'label');
        $this->iStep             = $this->params[0];
        $this->iProjectId        = $this->params[1];
    }

    public function _changeClient()
    {
        $this->hideDecoration();

        $this->search = urldecode(filter_var($this->params[0], FILTER_SANITIZE_STRING));

        if (false === empty($this->params[0])) {
            /** @var \clients $clients */
            $clients       = $this->loadData('clients');
            $this->clients = $clients->searchEmprunteurs('OR', $this->search, $this->search, '', '', str_replace(' ', '', $this->search));
        }
    }

    public function _memo()
    {
        $this->hideDecoration();

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $this->editMemo();
                break;
            case 'DELETE':
                $this->deleteMemo();
                break;
            case 'GET':
            default:
                $this->listMemo();
                break;
        }
    }

    private function listMemo()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Doctrine\ORM\EntityRepository $projectCommentRepository */
        $projectCommentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsComments');

        if (
            isset($this->params[0], $this->params[1])
            && ($projectCommentEntity = $projectCommentRepository->find($this->params[1]))
            && $projectCommentEntity->getIdProject()->getIdProject() == $this->params[0]
        ) {
            /** @var ProjectsComments $projectCommentEntity */
            $this->type    = 'edit';
            $this->content = $projectCommentEntity->getContent();
            $this->public  = $projectCommentEntity->getPublic();
        } else {
            $this->type    = 'add';
            $this->content = '';
            $this->public  = false;
        }

        $this->setView('memo/edit');
    }

    private function editMemo()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Doctrine\ORM\EntityRepository $projectRepository */
        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        /** @var \Doctrine\ORM\EntityRepository $projectCommentRepository */
        $projectCommentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsComments');

        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $projectEntity */
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments $projectCommentEntity */
        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users $userEntity */

        if (
            isset($_POST['projectId'], $_POST['content'])
            && filter_var($_POST['projectId'], FILTER_VALIDATE_INT)
            && ($projectEntity = $projectRepository->find($_POST['projectId']))
        ) {
            if (
                isset($_POST['commentId'])
                && ($projectCommentEntity = $projectCommentRepository->find($_POST['commentId']))
                && $_SESSION['user']['id_user'] == $projectCommentEntity->getIdUser()->getIdUser()
            ) {
                $projectCommentEntity->setContent($_POST['content']);
                $projectCommentEntity->setPublic(empty($_POST['public']) ? false : true);

                $entityManager->persist($projectCommentEntity);
                $entityManager->flush($projectCommentEntity);

                $slackNotification = 'édité';
            } else {
                $projectCommentEntity = new ProjectsComments();
                $projectCommentEntity->setIdProject($projectEntity);
                $projectCommentEntity->setContent($_POST['content']);
                $projectCommentEntity->setPublic(empty($_POST['public']) ? false : true);
                $projectCommentEntity->setIdUser($this->userEntity);

                $entityManager->persist($projectCommentEntity);
                $entityManager->flush($projectCommentEntity);

                $slackNotification = 'ajouté';
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\SlackManager $slackManager */
            $slackManager = $this->get('unilend.service.slack_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users $userRepository */
            $userRepository    = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');
            $slackNotification = 'Mémo ' . $slackNotification . ' par *' . $projectCommentEntity->getIdUser()->getFirstname() . ' ' . $projectCommentEntity->getIdUser()->getName() . '* sur le projet ' . $slackManager->getProjectName($projectEntity);

            if (
                $projectEntity->getIdCommercial() > 0
                && $_SESSION['user']['id_user'] != $projectEntity->getIdCommercial()
                && ($userEntity = $userRepository->find($projectEntity->getIdCommercial()))
                && false === empty($userEntity->getSlack())
            ) {
                $slackManager->sendMessage($slackNotification, '@' . $userEntity->getSlack());
            }

            if (
                $projectEntity->getIdAnalyste() > 0
                && $_SESSION['user']['id_user'] != $projectEntity->getIdAnalyste()
                && ($userEntity = $userRepository->find($projectEntity->getIdAnalyste()))
                && false === empty($userEntity->getSlack())
            ) {
                $slackManager->sendMessage($slackNotification, '@' . $userEntity->getSlack());
            }
        }

        $this->projectComments = $projectCommentRepository->findBy(['idProject' => $_POST['projectId']], ['added' => 'DESC']);

        $this->setView('memo/list');
    }

    private function deleteMemo()
    {
        $this->autoFireView = false;

        header('Content-Type: application/json');

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Doctrine\ORM\EntityRepository $projectCommentRepository */
        $projectCommentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsComments');

        if (
            isset($this->params[0], $this->params[1])
            && ($projectCommentEntity = $projectCommentRepository->find($this->params[1]))
            && $projectCommentEntity->getIdProject()->getIdProject() == $this->params[0]
            && $projectCommentEntity->getIdUser()->getIdUser() == $_SESSION['user']['id_user']
        ) {
            $entityManager->remove($projectCommentEntity);
            $entityManager->flush($projectCommentEntity);

            echo json_encode([
                'success' => true
            ]);
        } else {
            if (empty($projectCommentEntity)) {
                $error = 'Erreur inconnue';
            } elseif ($projectCommentEntity->getIdProject()->getIdProject() != $this->params[0]) {
                $error = 'Le mémo n\'appartient pas à ce projet';
            } elseif ($projectCommentEntity->getIdUser()->getIdUser() != $_SESSION['user']['id_user']) {
                $error = 'Vous ne disposez pas des droits pour supprimer ce mémo';
            } else {
                $error = 'Erreur inconnue';
            }

            echo json_encode([
                'error'   => true,
                'message' => $error
            ]);
        }
    }

    public function _file()
    {
        $this->hideDecoration();

        if (isset($_POST['send_etape5']) && isset($this->params[0])) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
            $attachmentManager = $this->get('unilend.service.attachment_manager');
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\AttachmentTypeRepository $attachmentTypeRepo */
            $attachmentTypeRepo = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType');
            /** @var Projects $project */
            $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->params[0]);
            $client  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());

            // Histo user //
            $serialize = serialize(array('id_project' => $this->params[0], 'files' => $_FILES));
            $this->users_history->histo(9, 'dossier edit etapes 5', $_SESSION['user']['id_user'], $serialize);

            $this->tablResult = array();

            foreach ($this->request->files->all() as $attachmentTypeId => $uploadedFile) {
                if ($uploadedFile) {
                    $attachment        = null;
                    $projectAttachment = null;
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $attachmentType */
                    $attachmentType = $attachmentTypeRepo->find($attachmentTypeId);
                    if ($attachmentType) {
                        $attachment = $attachmentManager->upload($client, $attachmentType, $uploadedFile);
                    }
                    if ($attachment) {
                        $projectAttachment = $attachmentManager->attachToProject($attachment, $project);
                    }
                    if ($projectAttachment) {
                        $this->tablResult['fichier_' . $attachmentTypeId] = 'ok';
                    }
                }
            }

            $this->result = json_encode($this->tablResult);
        }
    }

    public function _remove_file()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $result = [];
        $_POST   = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        if (isset($_POST['attachment_id'])) {
            $entityManager =  $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment $projectAttachment */
            $projectAttachment = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment')->find($_POST['attachment_id']);
            if ($projectAttachment) {
                $entityManager->remove($projectAttachment);
                $entityManager->flush($projectAttachment);
            }
            $result[$_POST['attachment_id']] = 'ok';
        }

        echo json_encode($result);
    }

    public function _add()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \clients clients */
        $this->clients = $this->loadData('clients');
        /** @var \clients_adresses clients_adresses */
        $this->clients_adresses = $this->loadData('clients_adresses');
        /** @var \companies companies */
        $this->companies = $this->loadData('companies');
        /** @var projects projects */
        $this->projects = $this->loadData('projects');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\PartnerManager $partnerManager */
        $partnerManager = $this->get('unilend.service.partner_manager');
        $defaultPartner = $partnerManager->getDefaultPartner();
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\PartnerRepository $partnerRepository */
        $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');
        $this->partnerList = $partnerRepository->getPartnersSortedByName(Partner::STATUS_VALIDATED);

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

        if (isset($this->params[0]) && $this->params[0] === 'create_etape2') {
            if (isset($this->params[1]) && is_numeric($this->params[1])) {
                /** @var Clients $clientEntity */
                $clientEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->params[1]);
                if (null !== $clientEntity && $clientManager->isBorrower($clientEntity)) {
                    $companyEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $clientEntity->getIdClient()]);
                } else {
                    $_SESSION['freeow']['title']   = 'La création n\' pas abouti';
                    $_SESSION['freeow']['message'] = 'Le client selectioné n\'est pas un emprunteur.';
                    header('Location: ' . $this->lurl . '/dossiers/add/create');
                    die;
                }
            } else {
                $companyEntity = $this->createBlankCompany();
            }

            $this->createProject($companyEntity, $defaultPartner->getId());

            header('Location: ' . $this->lurl . '/dossiers/add/' . $this->projects->id_project);
            die;
        } elseif (isset($this->params[0], $this->params[1]) && $this->params[0] === 'siren' && 1 === preg_match('/^[0-9]{9}$/', $this->params[1])) {
            $companyEntity = $this->createBlankCompany($this->params[1]);
            $this->createProject($companyEntity, $defaultPartner->getId());

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager $projectRequestManager */
            $projectRequestManager = $this->get('unilend.service.project_request_manager');
            $projectRequestManager->checkProjectRisk($this->projects, $_SESSION['user']['id_user']);

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
        } elseif (isset($this->params[0])) {
            if ($this->projects->get($this->params[0])) {
                if ($this->projects->create_bo) {
                    $this->companies->get($this->projects->id_company, 'id_company');
                    $this->clients->get($this->companies->id_client_owner, 'id_client');

                    // additional safeguard to avoid duplicate email when taking an existing lender as borrower, will be replaced by the borrower account checks when doing balance project
                    if ($clientManager->isLender($this->clients)) {
                        $this->clients->email = '';
                    }
                } elseif (0 == $this->projects->create_bo) {
                    $_SESSION['freeow']['title']   = 'Création de dossier';
                    $_SESSION['freeow']['message'] = 'Ce dossier n\'a pas été créé dans le back office';

                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                    die;
                }
            }
        }

        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->dureePossible = explode(',', $this->settings->value);

        $this->sources = array_column($this->clients->select('source NOT LIKE "http%" AND source NOT IN ("", "1") GROUP BY source'), 'source');
    }

    /**
     * @param null|string $siren
     * @return Companies
     */
    private function createBlankCompany($siren = null)
    {
        $clientEntity        = new Clients();
        $companyEntity       = new Companies();
        $clientAddressEntity = new ClientsAdresses();

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $entityManager->getConnection()->beginTransaction();
        try {
            $entityManager->persist($clientEntity);
            $entityManager->flush($clientEntity);

            $clientAddressEntity->setIdClient($clientEntity);
            $entityManager->persist($clientAddressEntity);

            $companyEntity->setSiren($siren);
            $companyEntity->setIdClientOwner($clientEntity->getIdClient());
            $companyEntity->setStatusAdresseCorrespondance(1);
            $entityManager->persist($companyEntity);
            $entityManager->flush($companyEntity);

            $this->get('unilend.service.wallet_creation_manager')->createWallet($clientEntity, WalletType::BORROWER);
            $entityManager->getConnection()->commit();
        } catch (Exception $exception) {
            $entityManager->getConnection()->rollBack();
            $this->get('logger')->error('An error occurred while creating client: ' . $exception->getMessage(), [['class' => __CLASS__, 'function' => __FUNCTION__]]);
        }

        return $companyEntity;
    }

    /**
     * @param Companies $companyEntity
     * @param int       $partnerId
     */
    private function createProject(Companies $companyEntity, $partnerId)
    {
        $this->projects->id_company                = $companyEntity->getIdCompany();
        $this->projects->create_bo                 = 1;
        $this->projects->status                    = ProjectsStatus::INCOMPLETE_REQUEST;
        $this->projects->id_partner                = $partnerId;
        $this->projects->commission_rate_funds     = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $this->projects->commission_rate_repayment = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $this->projects->create();

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->get('unilend.service.project_manager');
        $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::INCOMPLETE_REQUEST, $this->projects);

        $serialize = serialize(['id_project' => $this->projects->id_project]);
        $this->users_history->histo(7, 'dossier create', $_SESSION['user']['id_user'], $serialize);
    }

    public function _funding()
    {
        $this->projects  = $this->loadData('projects');
        $this->companies = $this->loadData('companies');
        $this->bids      = $this->loadData('bids');

        $this->lProjects = $this->projects->selectProjectsByStatus([ProjectsStatus::EN_FUNDING]);
    }

    public function _remboursements()
    {
        $this->setView('remboursements');
        $this->pageTitle = 'Remboursements';
        $this->listing([ProjectsStatus::FUNDE, ProjectsStatus::REMBOURSEMENT]);
    }

    public function _no_remb()
    {
        $this->setView('remboursements');
        $this->pageTitle = 'Incidents de remboursement';
        $this->listing([ProjectsStatus::PROBLEME, ProjectsStatus::RECOUVREMENT, ProjectsStatus::PROBLEME_J_X, ProjectsStatus::PROCEDURE_SAUVEGARDE, ProjectsStatus::REDRESSEMENT_JUDICIAIRE, ProjectsStatus::LIQUIDATION_JUDICIAIRE, ProjectsStatus::DEFAUT]);
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
        $this->notifications                 = $this->loadData('notifications');
        $this->projects_remb                 = $this->loadData('projects_remb');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $this->settings                      = $this->loadData('settings');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\OperationManager $operationManager */
        $operationManager                    = $this->get('unilend.service.operation_manager');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager                       = $this->get('doctrine.orm.entity_manager');
        $repaymentScheduleRepository         = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $paymentScheduleRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');
        $operationRepository                 = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $walletBalanceHistoryRepository      = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');

        /** @var \tax_type $taxType */
        $taxType = $this->loadData('tax_type');
        /** @var \Psr\Log\LoggerInterface $oLogger */
        $oLogger = $this->get('logger');

        $taxRate   = $taxType->getTaxRateByCountry('fr');
        $this->tva = $taxRate[\Unilend\Bundle\CoreBusinessBundle\Entity\TaxType::TYPE_VAT] / 100;

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');
            $this->users->get($this->projects->id_analyste, 'id_user');
            $this->projects_status->get($this->projects->status, 'status');

            $this->nbPeteurs = $this->loans->getNbPreteurs($this->projects->id_project);
            $lRembs          = $this->echeanciers_emprunteur->select('id_project = ' . $this->projects->id_project);

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
                /** @var \Symfony\Component\Stopwatch\Stopwatch $stopWatch */
                $stopWatch = $this->get('debug.stopwatch');
                $stopWatch->start('repayment');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRepaymentManager $projectRepaymentManager */
                $projectRepaymentManager = $this->get('unilend.service.project_repayment_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
                $projectManager = $this->get('unilend.service.project_manager');

                $project              = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->projects->id_project);
                $paidPaymentSchedules = $paymentScheduleRepository->findBy(
                    ['idProject' => $project, 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PAID],
                    ['ordre' => 'ASC']
                );

                if (false === empty($paidPaymentSchedules)) {
                    foreach ($paidPaymentSchedules as $paidPaymentSchedule) {
                        $repaymentSchedules = $repaymentScheduleRepository->findByProject($project, $paidPaymentSchedule->getOrdre(), null, Echeanciers::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PAID);
                        $repaymentNb        = 0;

                        if (0 < count($repaymentSchedules)) {
                            try {
                                $repaymentNb              = $projectRepaymentManager->repay($project, $paidPaymentSchedule->getOrdre(), $_SESSION['user']['id_user']);
                                $unpaidRepaymentSchedules = $repaymentScheduleRepository->findByProject($project, $paidPaymentSchedule->getOrdre(), null, Echeanciers::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PAID, null, 0, 1);
                                $repaidRepaymentSchedules = $repaymentScheduleRepository->findByProject($project, $paidPaymentSchedule->getOrdre(), null, Echeanciers::STATUS_REPAID, EcheanciersEmprunteur::STATUS_PAID);

                                if (0 === $repaymentNb) {
                                    $_SESSION['freeow']['title']   = 'Remboursement prêteur';
                                    $_SESSION['freeow']['message'] = "Aucun remboursement n'a été effectué aux prêteurs !";
                                } else {
                                    $emailNB = 0;
                                    foreach ($repaidRepaymentSchedules as $repaymentSchedule) {
                                        if (Echeanciers::STATUS_REPAYMENT_EMAIL_SENT === $repaymentSchedule->getStatusEmailRemb()) {
                                            continue;
                                        }
                                        $netRepayment         = $operationRepository->getNetAmountByRepaymentScheduleId($repaymentSchedule);
                                        $wallet               = $repaymentSchedule->getIdLoan()->getIdLender();
                                        $repaymentOperation   = $operationRepository->findOneBy(['idRepaymentSchedule' => $repaymentSchedule]);
                                        $walletBalanceHistory = $walletBalanceHistoryRepository->findOneBy(['idOperation' => $repaymentOperation, 'idWallet' => $wallet]);

                                        $this->notifications->type       = Notifications::TYPE_REPAYMENT;
                                        $this->notifications->id_lender  = $wallet->getId();
                                        $this->notifications->id_project = $repaymentSchedule->getIdLoan()->getProject()->getIdProject();
                                        $this->notifications->amount     = bcmul($netRepayment, 100);
                                        $this->notifications->create();

                                        $this->clients_gestion_mails_notif->id_client                 = $wallet->getIdClient()->getIdClient();
                                        $this->clients_gestion_mails_notif->id_notif                  = \clients_gestion_type_notif::TYPE_REPAYMENT;
                                        $this->clients_gestion_mails_notif->date_notif                = $repaymentSchedule->getDateEcheanceReel()->format('Y-m-d H:i:s');
                                        $this->clients_gestion_mails_notif->id_notification           = $this->notifications->id_notification;
                                        $this->clients_gestion_mails_notif->id_wallet_balance_history = $walletBalanceHistory->getId();
                                        $this->clients_gestion_mails_notif->create();

                                        if (ProjectsStatus::RECOUVREMENT === $project->getStatus()) {
                                            $projectRepaymentManager->sendDebtCollectionRepaymentMailToLender($repaymentSchedule);
                                        } elseif (isset($this->params[2]) && $this->params[2] == 'regul') {
                                            $projectRepaymentManager->sendRegularisationRepaymentMailToLender($repaymentSchedule);
                                        } elseif ($this->clients_gestion_notifications->getNotif($this->clients->id_client, \clients_gestion_type_notif::TYPE_REPAYMENT, 'immediatement') == true) {
                                            $projectRepaymentManager->sendRepaymentMailToLender($repaymentSchedule);
                                        }

                                        $repaymentSchedule->setStatusEmailRemb(Echeanciers::STATUS_REPAYMENT_EMAIL_SENT);
                                        $emailNB++;

                                        if (0 === $emailNB % 50) {
                                            $entityManager->flush();
                                        }
                                    }
                                    $entityManager->flush();

                                    if (ProjectsStatus::RECOUVREMENT === $project->getStatus()) {
                                        $projectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::REMBOURSEMENT, $project);
                                    }

                                    $_SESSION['freeow']['title']   = 'Remboursement prêteur';
                                    $_SESSION['freeow']['message'] = 'Les prêteurs ont bien été remboursés !';

                                    if (0 < count($unpaidRepaymentSchedules)) {
                                        $_SESSION['freeow']['title']   = 'Remboursement prêteur';
                                        $_SESSION['freeow']['message'] = "Certaines remboursements n'ont pas été effectués aux prêteurs ! Veuillez réessayer ultérieurement.";
                                    }
                                }

                            } catch (\Exception $exception) {
                                $_SESSION['freeow']['title']   = 'Remboursement prêteur';
                                $_SESSION['freeow']['message'] = 'Une erreur survenu ! Veuillez réessayer ultérieurement.';
                                $logger = $this->get('logger');
                                $logger->error('Errors occur during the repayment command. Error message : ' . $exception->getMessage(), [$exception->getTrace()]);
                            }

                            $stopWatchEvent = $stopWatch->stop('repayment');

                            $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);
                            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\SlackManager $slackManager */
                            $slackManager = $this->get('unilend.service.slack_manager');
                            $message      = $slackManager->getProjectName($project) .
                                ' - Remboursement effectué par ' . trim($user->getFirstname() . ' ' . $user->getName()) .
                                ' en ' . round($stopWatchEvent->getDuration() / 1000, 1) . ' secondes  (' . $repaymentNb . ' prêts, échéance #' . $paidPaymentSchedule->getOrdre() . ')';

                            $slackManager->sendMessage($message);

                            break;
                        }
                    }
                }

                header('Location: ' . $this->lurl . '/dossiers/detail_remb/' . $this->params[0]);
                die;
            }

            if (isset($_POST['spy_remb_anticipe']) && $_POST['id_reception'] > 0 && isset($_POST['id_reception'])) {
                $id_reception = $_POST['id_reception'];

                $this->projects               = $this->loadData('projects');
                /** @var \echeanciers echeanciers */
                $this->echeanciers            = $this->loadData('echeanciers');
                $this->receptions             = $this->loadData('receptions');
                $this->echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
                $this->clients                = $this->loadData('clients');
                $this->mail_template          = $this->loadData('mail_templates');
                $this->companies              = $this->loadData('companies');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                $oProjectManager = $this->get('unilend.service.project_manager');
                $loanRepository  = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans');

                $this->receptions->get($id_reception);
                $this->projects->get($this->receptions->id_project);
                $this->companies->get($this->projects->id_company, 'id_company');

                //in difference of the due capital displayed for the sales people to tell the client, the check on the amount is on all not yet paid by the borrower.
                $nextRepayment = $this->echeanciers->select('id_project = ' . $this->projects->id_project . ' AND status = ' . \echeanciers::STATUS_PENDING . ' AND date_echeance >= "' . $this->getLimitDate(new \DateTime('today midnight'))->format('Y-m-d H:i:s') . '"', ' ordre ASC', 0, 1);

                if (empty($nextRepayment)) {
                    header('Location: ' . $this->lurl . '/dossiers/detail_remb/' . $this->projects->id_project);
                    die;
                }

                $borrowerOwedCapital = $this->echeanciers_emprunteur->reste_a_payer_ra($this->projects->id_project, $nextRepayment[0]['ordre']);

                if (bcmul($borrowerOwedCapital, 100) == $this->receptions->montant) {
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

                    $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::REMBOURSEMENT_ANTICIPE, $this->projects);


                    foreach ($this->echeanciers->get_liste_preteur_on_project($this->projects->id_project) as $item) {
                        $loan = $loanRepository->find($item['id_loan']);
                        $operationManager->earlyRepayment($loan);
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

                    header('Location: ' . $this->lurl . '/dossiers/detail_remb/' . $this->projects->id_project);
                    die;
                }
            }
            $this->loadEarlyRepaymentInformation(true);
        }
    }

    public function _detail_remb_preteur()
    {
        $this->clients          = $this->loadData('clients');
        $this->echeanciers      = $this->loadData('echeanciers');
        $this->projects         = $this->loadData('projects');
        /** @var \loans loan */
        $this->loan = $this->loadData('loans');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LenderManager lenderManager */
        $this->lenderManager = $this->get('unilend.service.lender_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager loanManager */
        $this->loanManager = $this->get('unilend.service.loan_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository walletRepository */
        $this->walletRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            /** @var \loans $loans */
            $loans = $this->loadData('loans');
            /** @var \echeanciers_emprunteur $repaymentSchedule */
            $repaymentSchedule = $this->loadData('echeanciers_emprunteur');

            $this->nbPeteurs = $loans->getNbPreteurs($this->projects->id_project);
            $this->tauxMoyen = $this->projects->getAverageInterestRate();
            $this->montant   = $repaymentSchedule->sum('montant', 'id_project = ' . $this->projects->id_project) / 100;
            $this->lLenders  = $loans->select('id_project = ' . $this->projects->id_project, 'rate ASC');
        }
    }

    public function _detail_echeance_preteur()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var NumberFormatter numberFormatter */
        $this->numberFormatter = $this->get('number_formatter');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LoanManager loanManager */
        $this->loanManager = $this->get('unilend.service.loan_manager');

        $repaymentScheduleRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $this->walletRepository      = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $this->operationRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        if (isset($this->params[1])) {
            $this->loan    = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans')->find($this->params[1]);
            $this->client  = $this->loan->getIdLender()->getIdClient();
            $this->lRemb   = $repaymentScheduleRepository->findBy(['idLoan' => $this->loan, 'statusRa' => Echeanciers::IS_NOT_EARLY_REPAID]);

            // on check si on est en remb anticipé
            // ON recup la date de statut remb
            $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->params[0]);

            $this->montant_ra = 0;

            if (ProjectsStatus::REMBOURSEMENT_ANTICIPE === $project->getStatus()) {
                $this->montant_ra = $repaymentScheduleRepository->getEarlyRepaidCapitalByLoan($this->loan);
                $this->date_ra    = $repaymentScheduleRepository->findOneBy(['idLoan' => $this->loan, 'statusRa' => Echeanciers::IS_EARLY_REPAID])->getDateEcheanceReel();
            }
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

            $this->projects_status->get(ProjectsStatus::REMBOURSEMENT_ANTICIPE, 'status');

            if ($dernierStatut[0]['id_project_status'] == $this->projects_status->id_project_status) {
                //récupération du montant de la transaction du CRD pour afficher la ligne en fin d'échéancier
                $this->receptions->get($this->projects->id_project, 'type_remb = ' . Receptions::REPAYMENT_TYPE_EARLY . ' AND status_virement = 1 AND type = 2 AND id_project');
                $this->montant_ra = ($this->receptions->montant / 100);
                $this->date_ra    = $dernierStatut[0]['added'];

                //on ajoute ce qu'il reste au capital restant
                $this->capital += ($this->montant_ra * 100);
            }
        }
    }

    /**
     * @param boolean $displayActionButton
     */
    private function loadEarlyRepaymentInformation($displayActionButton)
    {
        $this->earlyRepaymentPossible = true;
        $this->displayActionButton    = $displayActionButton;

        if ($this->projects->status >= ProjectsStatus::REMBOURSEMENT) {
            if ($this->projects->status == ProjectsStatus::REMBOURSEMENT_ANTICIPE) {
                $this->message                = '<div style="color:green;">Remboursement anticipé effectué</div>';
                $this->earlyRepaymentPossible = false;

                return;
            }
            /** @var \echeanciers $repaymentSchedule */
            $repaymentSchedule = $this->loadData('echeanciers');
            $lateRepayment     = $repaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND status = ' . \echeanciers::STATUS_PENDING . ' AND DATE(date_echeance) <= "' . (new \DateTime())->format('Y-m-d') . '"', ' ordre ASC', 0, 1);

            if (false === empty($lateRepayment)) {
                $this->message                = '<div style="color:red;">Remboursement impossible. Toutes les échéances précédentes ne sont pas remboursées</div>';
                $this->earlyRepaymentPossible = false;

                return;
            }
            /** @var \echeanciers_emprunteur $paymentSchedule */
            $paymentSchedule = $this->loadData('echeanciers_emprunteur');
            $nextRepayment   = $repaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND status = ' . \echeanciers::STATUS_PENDING . ' AND date_echeance >= "' . $this->getLimitDate(new \DateTime('today midnight'))->format('Y-m-d H:i:s') . '"', ' ordre ASC', 0, 1);

            if (false === empty($nextRepayment)) {
                $this->earlyRepaymentLimitDate    = $this->getLimitDate(\DateTime::createFromFormat('Y-m-d H:i:s', $nextRepayment[0]['date_echeance']), true);
                $this->nextScheduledRepaymentDate = \DateTime::createFromFormat('Y-m-d H:i:s', $nextRepayment[0]['date_echeance']);
                $this->lenderOwedCapital          = $repaymentSchedule->getRemainingCapitalAtDue($this->projects->id_project, $nextRepayment[0]['ordre'] + 1);
                $this->borrowerOwedCapital        = $paymentSchedule->reste_a_payer_ra($this->projects->id_project, $nextRepayment[0]['ordre'] + 1);

                if (0 === bccomp($this->lenderOwedCapital, $this->borrowerOwedCapital, 2)) {
                    $this->message = '<div style="color:green;">Remboursement possible</div>';
                } elseif (-1 === bccomp($this->lenderOwedCapital, $this->borrowerOwedCapital, 2)) {
                    $this->message = '<div style="color:orange;">Remboursement possible <br />(CRD Prêteurs :' . $this->lenderOwedCapital . '€ - CRD Emprunteur :' . $this->borrowerOwedCapital . '€)</div>';
                } else {
                    $this->earlyRepaymentPossible = false;
                    $this->message                = '<div style="color:red;">Remboursement impossible <br />(CRD Prêteurs :' . $this->lenderOwedCapital . '€ - CRD Emprunteur :' . $this->borrowerOwedCapital . '€)</div>';

                    return;
                }
                /** @var \Doctrine\ORM\EntityManager $entityManager */
                $entityManager = $this->get('doctrine.orm.entity_manager');
                /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\ReceptionsRepository $receptionRepository */
                $receptionRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions');
                $this->reception     = $receptionRepository->getBorrowerAnticipatedRepaymentWireTransfer(
                    $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
                        ->find($this->projects->id_project)
                );

                if (1 === count($this->reception)) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions reception */
                    $this->reception   = $this->reception[0];
                    $lastPaidRepayment = $repaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND status = ' . Echeanciers::STATUS_REPAID, ' ordre DESC', 0, 1);

                    $currentLenderOwedCapital   = $repaymentSchedule->getRemainingCapitalAtDue($this->projects->id_project, $lastPaidRepayment[0]['ordre'] + 1);
                    $currentBorrowerOwedCapital = $paymentSchedule->reste_a_payer_ra($this->projects->id_project, $lastPaidRepayment[0]['ordre'] + 1);

                    if (0 === bccomp($currentLenderOwedCapital, $currentBorrowerOwedCapital, 2) && (bcdiv($this->reception->getMontant(), 100, 2)) >= $currentLenderOwedCapital) {
                        $this->wireTransferAmountOk = true;
                        $this->message              = '<div style="color:green;">Virement reçu conforme</div>';
                    } elseif (0 === bccomp($this->lenderOwedCapital, $this->borrowerOwedCapital, 2) && (bcdiv($this->reception->getMontant(), 100, 2)) >= $this->lenderOwedCapital) {
                        $this->wireTransferAmountOk = true;
                        $this->message              = '<div style="color:green;">Virement reçu conforme - Attente du remboursement de l\'échéance du ' . \DateTime::createFromFormat('Y-m-d H:i:s', $nextRepayment[0]['date_echeance'])->format('d/m/Y') . '</div>';
                        $this->displayActionButton  = false;
                    } elseif (bcdiv($this->reception->getMontant(), 100, 2) < $this->lenderOwedCapital) {
                        $this->wireTransferAmountOk = false;
                        $this->message              = '<div style="color:red;">Virement reçu - Probléme montant <br />(CRD Prêteurs :' . $this->lenderOwedCapital . '€ - Virement :' . ($this->reception->getMontant() / 100) . '€)</div>';
                    }
                }
            } else {
                $this->message                = '<div style="color:orange;">Il n\'est plus possible de rembourser par anticipation</div>';
                $this->earlyRepaymentPossible = false;

                return;
            }
        } else {
            $this->earlyRepaymentPossible = false;
            $this->message                = '<div>Le statut du projet ne  permet pas de faire un remboursement anticipé.</div>';
        }
    }

    /**
     * @param DateTime $date
     * @param bool     $countDown
     *
     * @return DateTime
     */
    private function getLimitDate(\DateTime $date, $countDown = false)
    {
        /** @var \jours_ouvres $businessDays */
        $businessDays = $this->loadLib('jours_ouvres');
        $interval     = new DateInterval('P1D');

        if ($countDown) {
            $interval->invert = 1;
        }
        $workingDays = 1;

        while ($workingDays <= 5) {
            $date->add($interval);

            if ($businessDays->isHoliday($date->getTimestamp())) {
                $workingDays++;
            }
        }
        return $date;
    }

    public function _send_cgv_ajax()
    {
        $this->hideDecoration();

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        if (
            empty($this->params[0])
            || $this->params[0] != (int) $this->params[0]
            || null === ($project = $projectRepository->find($this->params[0]))
        ) {
            $this->result = 'project id invalid';
            return;
        }

        try {
            /** @var TermsOfSaleManager $termsOfSaleManager */
            $termsOfSaleManager = $this->get('unilend.service.terms_of_sale_manager');
            $termsOfSaleManager->sendBorrowerEmail($project);
        } catch (\Exception $exception) {
            switch ($exception->getCode()) {
                case TermsOfSaleManager::EXCEPTION_CODE_INVALID_EMAIL:
                    $this->result = 'Erreur : L\'adresse mail du client est vide';
                    return;
                case TermsOfSaleManager::EXCEPTION_CODE_INVALID_PHONE_NUMBER:
                    $this->result = 'Le numéro de téléphone du dirigeant n\'est pas un numéro de portable';
                    return;
                case TermsOfSaleManager::EXCEPTION_CODE_PDF_FILE_NOT_FOUND:
                    $this->result = 'file not found';
                    return;
                default:
                    $this->result = $exception->getMessage();
                    return;
            }
        }

        $this->result = 'CGV envoyées avec succès';
    }

    public function _completude_preview()
    {
        $this->hideDecoration();

        /** @var \projects $oProjects */
        $oProjects = $this->loadData('projects');
        /** @var \clients $oClients */
        $oClients = $this->loadData('clients');

        if (false === isset($this->params[0]) || false === $oProjects->get($this->params[0])) {
            $this->error = 'no projects found';
            return;
        }
        /** @var \companies $oCompanies */
        $oCompanies = $this->loadData('companies');
        if (false === $oCompanies->get($oProjects->id_company)) {
            $this->error = 'no company found';
            return;
        }

        $iClientId = null;
        if ($oProjects->id_prescripteur) {
            /** @var \prescripteurs $oPrescripteurs */
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

        /** @var \projects $oProjects */
        $oProjects = $this->loadData('projects');
        /** @var \clients $oClients */
        $oClients = $this->loadData('clients');
        /** @var \companies $oCompanies */
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

        if (false === empty($_POST)) {
            /** @var \projects $oProjects */
            $oProjects = $this->loadData('projects');
            /** @var \clients $oClients */
            $oClients = $this->loadData('clients');
            /** @var \companies $oCompanies */
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
            try {
                $message->setTo($sRecipientEmail);
                $mailer = $this->get('mailer');
                $mailer->send($message);

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                $oProjectManager = $this->get('unilend.service.project_manager');
                $oProjectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::COMMERCIAL_REVIEW, $oProjects, 1, $varMail['liste_pieces']);

                unset($_SESSION['project_submission_files_list'][$oProjects->id_project]);
                echo 'Votre email a été envoyé';
            } catch (\Exception $exception) {
                $this->get('logger')->warning(
                    'Could not send email: ' . $sTypeEmail . ' - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $oClients->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
                echo 'Le mail n\'a pas été envoyé';
            }
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

        /** @var \temporary_links_login $oTemporaryLink */
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
            'link_compte_emprunteur' => $this->surl . '/espace_emprunteur/securite/' . $oTemporaryLink->generateTemporaryLink($oClients->id_client, \temporary_links_login::PASSWORD_TOKEN_LIFETIME_LONG)
        );
    }

    private function selectEmailCompleteness($iClientId)
    {
        $oClients = $this->loadData('clients');
        $oClients->get($iClientId);

        if (isset($oClients->secrete_question, $oClients->secrete_reponse)) {
            return 'depot-dossier-completude';
        } else {
            return 'depot-dossier-completude-avec-mdp';
        }
    }

    public function _status()
    {
        if (false === empty($_POST)) {
            $url = '/dossiers/status/' . $_POST['status'];

            if (false === empty($_POST['first-range-start']) && false === empty($_POST['first-range-end'])) {
                $start = new \DateTime(str_replace('/', '-', $_POST['first-range-start']));
                $end   = new \DateTime(str_replace('/', '-', $_POST['first-range-end']));
                $url .= '/' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d');

                if (false === empty($_POST['second-range-start']) && false === empty($_POST['second-range-end'])) {
                    $start = new \DateTime(str_replace('/', '-', $_POST['second-range-start']));
                    $end   = new \DateTime(str_replace('/', '-', $_POST['second-range-end']));
                    $url .= '/' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d');
                }
            }

            header('Location: ' . $url);
            exit;
        }

        $this->loadJs('admin/vis/vis.min');
        $this->loadCss('../scripts/admin/vis/vis.min');

        /** @var \projects_status $projectStatus */
        $projectStatus  = $this->loadData('projects_status');
        $this->statuses = $projectStatus->select('', 'status ASC');

        if (
            isset($this->params[0], $this->params[1])
            && false === empty($this->params[0])
            && $this->params[0] == (int) $this->params[0]
            && 1 === preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})_([0-9]{4}-[0-9]{2}-[0-9]{2})/', $this->params[1], $matches)
        ) {
            $this->baseStatus      = $this->params[0];
            $this->firstRangeStart = new \DateTime($matches[1]);
            $this->firstRangeEnd   = new \DateTime($matches[2]);

            $today = new \DateTime('NOW');

            if (
                $projectStatus->get($this->baseStatus)
                && $this->firstRangeStart->getTimestamp() <= $today->getTimestamp()
                && $this->firstRangeEnd->getTimestamp() <= $today->getTimestamp()
                && $this->firstRangeStart->getTimestamp() <= $this->firstRangeEnd->getTimestamp()
            ) {
                /** @var \projects_status_history $projectStatusHistory */
                $projectStatusHistory = $this->loadData('projects_status_history');
                $baseStatus           = $projectStatusHistory->getStatusByDates($this->baseStatus, $this->firstRangeStart, $this->firstRangeEnd);

                if (false === empty($baseStatus)) {
                    $this->history        = [
                        'label'    => $baseStatus[0]['label'],
                        'count'    => count($baseStatus),
                        'status'   => $projectStatus->status,
                        'children' => $this->getStatusChildren(array_column($baseStatus, 'id_project_status_history'))
                    ];

                    foreach ($this->history['children'] as $childStatus => &$child) {
                        if ($childStatus > 0) {
                            $this->history['children'][$childStatus]['children'] = $this->getStatusChildren($child['id_project_status_history']);
                        }
                    }

                    if (isset($this->params[2]) && 1 === preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})_([0-9]{4}-[0-9]{2}-[0-9]{2})/', $this->params[2], $matches)) {
                        $this->secondRangeStart = new \DateTime($matches[1]);
                        $this->secondRangeEnd   = new \DateTime($matches[2]);

                        if (
                            $this->secondRangeStart->getTimestamp() <= $today->getTimestamp()
                            && $this->secondRangeEnd->getTimestamp() <= $today->getTimestamp()
                            && $this->secondRangeStart->getTimestamp() <= $this->secondRangeEnd->getTimestamp()
                        ) {
                            $baseStatus = $projectStatusHistory->getStatusByDates($this->baseStatus, $this->secondRangeStart, $this->secondRangeEnd);

                            if (false === empty($baseStatus)) {
                                $this->compareHistory   = [
                                    'label'    => $baseStatus[0]['label'],
                                    'count'    => count($baseStatus),
                                    'status'   => $projectStatus->status,
                                    'children' => $this->getStatusChildren(array_column($baseStatus, 'id_project_status_history'))
                                ];

                                foreach ($this->compareHistory['children'] as $childStatus => &$child) {
                                    if ($childStatus > 0) {
                                        $this->compareHistory['children'][$childStatus]['children'] = $this->getStatusChildren($child['id_project_status_history']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array $statusHistory
     *
     * @return array
     */
    private function getStatusChildren(array $statusHistory)
    {
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->loadData('projects_status_history');
        $childrenStatus       = $projectStatusHistory->getFollowingStatus($statusHistory);
        $status               = array();

        array_map(function ($aElement) use (&$status) {
            if (false === isset($status[$aElement['status']])) {
                $status[$aElement['status']] = array(
                    'count'                     => 1,
                    'label'                     => $aElement['label'],
                    'max_date'                  => $aElement['added'],
                    'total_days'                => $aElement['diff_days'],
                    'id_project_status_history' => array($aElement['id_project_status_history'])
                );
            } else {
                $status[$aElement['status']]['count']++;
                $status[$aElement['status']]['total_days'] += $aElement['diff_days'];
                $status[$aElement['status']]['id_project_status_history'][] = $aElement['id_project_status_history'];

                if ($aElement['added'] > $status[$aElement['status']]['max_date']) {
                    $status[$aElement['status']]['max_date'] = $aElement['added'];
                }
            }
        }, $childrenStatus);

        uasort($status, function($aFirstElement, $aSecondElement) {
            if ($aFirstElement['count'] === $aSecondElement['count']) {
                return 0;
            }
            return $aFirstElement['count'] > $aSecondElement['count'] ? -1 : 1;
        });

        return array_map(function ($status) {
            $status['avg_days'] = round($status['total_days'] / $status['count'], 1);
            return $status;
        }, $status);
    }

    public function _postpone()
    {
        $this->hideDecoration();

        $this->projects = $this->loadData('projects');

        if (
            false === isset($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || false === $this->projects->get($this->params[0])
        ) {
            echo 'Projet inconnu';
            $this->autoFireView = false;
            return;
        }

        if (isset($this->params[1]) && 'resume' === $this->params[1] && ProjectsStatus::POSTPONED == $this->projects->status) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            $projectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::COMMERCIAL_REVIEW, $this->projects);

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
        } elseif (false === empty($_POST['comment'])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager        = $this->get('doctrine.orm.entity_manager');
            $projectCommentEntity = new ProjectsComments();
            $projectCommentEntity->setIdProject($entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->projects->id_project));
            $projectCommentEntity->setIdUser($this->userEntity);
            $projectCommentEntity->setContent('<p><u>Report projet</u></p>' . $_POST['comment']);

            $entityManager->persist($projectCommentEntity);
            $entityManager->flush($projectCommentEntity);

            if ($this->projects->status != ProjectsStatus::POSTPONED) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
                $projectManager = $this->get('unilend.service.project_manager');
                $projectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::POSTPONED, $this->projects);
            }

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
        }
    }

    public function _abandon()
    {
        $this->hideDecoration();

        $this->projects = $this->loadData('projects');

        if (
            false === isset($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || false === $this->projects->get($this->params[0])
        ) {
            echo 'Projet inconnu';
            $this->autoFireView = false;
            return;
        }

        if (false === empty($_POST['reason']) && filter_var($_POST['reason'], FILTER_VALIDATE_INT)) {
            if (false === empty($_POST['comment'])) {
                /** @var \Doctrine\ORM\EntityManager $entityManager */
                $entityManager        = $this->get('doctrine.orm.entity_manager');
                $projectCommentEntity = new ProjectsComments();
                $projectCommentEntity->setIdProject($entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->projects->id_project));
                $projectCommentEntity->setIdUser($this->userEntity);
                $projectCommentEntity->setContent('<p><u>Abandon projet</u></p>' . $_POST['comment']);

                $entityManager->persist($projectCommentEntity);
                $entityManager->flush($projectCommentEntity);
            }

            /** @var \project_abandon_reason $abandonReason */
            $abandonReason = $this->loadData('project_abandon_reason');
            $abandonReason->get($_POST['reason']);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            $projectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::ABANDONED, $this->projects, 0, $abandonReason->label);

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
        }
    }

    public function _publish()
    {
        $this->hideDecoration();

        $this->projects = $this->loadData('projects');

        if (
            false === isset($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || false === $this->projects->get($this->params[0])
        ) {
            echo 'Projet inconnu';
            $this->autoFireView = false;
            return;
        }

        if (
            isset($_POST['date_publication'], $_POST['date_publication_heure'], $_POST['date_publication_minute'])
            && isset($_POST['date_retrait'], $_POST['date_retrait_heure'], $_POST['date_retrait_minute'])
            && 1 === preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{8}#', $_POST['date_publication'] . $_POST['date_publication_heure'] . $_POST['date_publication_minute'])
            && 1 === preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{8}#', $_POST['date_retrait'] . $_POST['date_retrait_heure'] . $_POST['date_retrait_minute'])
        ) {
            $publicationDate                = \DateTime::createFromFormat('d/m/YHi', $_POST['date_publication'] . $_POST['date_publication_heure'] . $_POST['date_publication_minute']);
            $endOfPublicationDate           = \DateTime::createFromFormat('d/m/YHi', $_POST['date_retrait'] . $_POST['date_retrait_heure'] . $_POST['date_retrait_minute']);
            $publicationLimitationDate      = new \DateTime('NOW + 5 minutes');
            $endOfPublicationLimitationDate = new \DateTime('NOW + 1 hour');

            if ($publicationDate <= $publicationLimitationDate || $endOfPublicationDate <= $endOfPublicationLimitationDate) {
                $_SESSION['public_dates_error'] = 'La date de publication du dossier doit être au minimum dans 5 minutes et la date de retrait dans plus d\'une heure';

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            }

            $this->projects->date_publication = $publicationDate->format('Y-m-d H:i:s');
            $this->projects->date_retrait     = $endOfPublicationDate->format('Y-m-d H:i:s');
            $this->projects->update();

            $_SESSION['freeow']['title']   = 'Mise en ligne';
            $_SESSION['freeow']['message'] = 'Mise en ligne programmée avec succès';

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            $projectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::A_FUNDER, $this->projects);

            $slackManager    = $this->container->get('unilend.service.slack_manager');
            $publicationDate = new \DateTime($this->projects->date_publication);
            $star            = str_replace('.', ',', constant('\projects::RISK_' . $this->projects->risk));
            $message         = $slackManager->getProjectName($this->projects) . ' sera mis en ligne le *' . $publicationDate->format('d/m/Y à H:i') . '* - ' . $this->projects->period . ' mois :calendar: / ' . $this->ficelle->formatNumber($this->projects->amount, 0) . ' € :moneybag: / ' . $star . ' :star:';

            $slackManager->sendMessage($message);

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
        }
    }

    public function _comity_to_analysis()
    {
        $this->hideDecoration();

        $this->projects = $this->loadData('projects');

        if (
            false === isset($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || false === $this->projects->get($this->params[0])
        ) {
            echo 'Projet inconnu';
            $this->autoFireView = false;
            return;
        }

        if (false === empty($_POST['comment'])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager        = $this->get('doctrine.orm.entity_manager');
            $projectCommentEntity = new ProjectsComments();
            $projectCommentEntity->setIdProject($entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->projects->id_project));
            $projectCommentEntity->setIdUser($this->userEntity);
            $projectCommentEntity->setContent('<p><u>Retour à l\'analyse</u><p>' . $_POST['comment'] . '</p>');

            $entityManager->persist($projectCommentEntity);
            $entityManager->flush($projectCommentEntity);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            $projectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::ANALYSIS_REVIEW, $this->projects);

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
        }
    }

    public function _suspensive_conditions()
    {
        $this->hideDecoration();

        $this->projects = $this->loadData('projects');

        if (
            false === isset($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || false === $this->projects->get($this->params[0])
        ) {
            echo 'Projet inconnu';
            $this->autoFireView = false;
            return;
        }
    }

    public function _reject_suspensive_conditions()
    {
        /** @var \projects $project */
        $project = $this->loadData('projects');
        $project->get($this->params[0], 'id_project');

        /** @var \companies $company */
        $company = $this->loadData('companies');
        $company->get($project->id_company, 'id_company');

        /** @var \clients $client */
        $client = $this->loadData('clients');
        $client->get($company->id_client_owner, 'id_client');

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        $projectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::COMITY_REJECTION, $project);

        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->loadData('projects_status_history');
        $projectStatusHistory->loadLastProjectHistory($project->id_project);

        /** @var \projects_status_history_details $historyDetails */
        $historyDetails                            = $this->loadData('projects_status_history_details');
        $historyDetails->id_project_status_history = $projectStatusHistory->id_project_status_history;
        $historyDetails->comity_rejection_reason   = \project_rejection_reason::SUSPENSIVE_CONDITIONS;
        $historyDetails->create();

        if (false === empty($client->email)) {
            /** @var \settings $settings */
            $settings = $this->loadData('settings');
            $settings->get('Facebook', 'type');
            $facebookLink = $settings->value;

            $settings->get('Twitter', 'type');
            $twitterLink = $settings->value;

            $keywords = [
                'surl'                   => $this->surl,
                'url'                    => $this->furl,
                'prenom_e'               => $client->prenom,
                'link_compte_emprunteur' => $this->furl,
                'lien_fb'                => $facebookLink,
                'lien_tw'                => $twitterLink
            ];

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $keywords);
            try {
                $message->setTo($client->email);
                $mailer = $this->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->get('logger')->warning(
                    'Could not send email: emprunteur-dossier-rejete - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
        }

        header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->id_project);
        die;
    }

    public function _remove_suspensive_conditions()
    {
        $this->projects = $this->loadData('projects');
        $this->projects->get($this->params[0]);

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        $projectManager->addProjectStatus($_SESSION['user']['id_user'], ProjectsStatus::PREP_FUNDING, $this->projects);

        /** @var \companies $company */
        $company = $this->loadData('companies');
        $company->get($this->projects->id_company);

        /** @var \clients $client */
        $client = $this->loadData('clients');
        $client->get($company->id_client_owner);

        $this->get('unilend.service.email_manager')->sendBorrowerAccount($client, 'ouverture-espace-emprunteur-plein');

        header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
        die;
    }

    public function _takeover()
    {
        $this->hideDecoration();

        $this->projects = $this->loadData('projects');

        if (
            false === isset($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || false === $this->projects->get($this->params[0])
        ) {
            echo 'Projet inconnu';
            $this->autoFireView = false;
            return;
        }

        if (isset($this->params[1])) {
            switch ($this->params[1]) {
                case 'search':
                    if (isset($this->params[2])) {
                        /** @var \companies $company */
                        $company         = $this->loadData('companies');
                        $siren           = filter_var($this->params[2], FILTER_SANITIZE_NUMBER_INT);
                        $this->companies = $company->searchCompanyBySIREN($siren);
                        $this->siren     = $siren;
                    }
                    break;
                case 'create':
                    /** @var \clients $client */
                    $client            = $this->loadData('clients');
                    $client->id_langue = 'fr';
                    $client->status    = Clients::STATUS_ONLINE;
                    $client->create();

                    /** @var \clients_adresses $clientAddress */
                    $clientAddress            = $this->loadData('clients_adresses');
                    $clientAddress->id_client = $client->id_client;
                    $clientAddress->create();

                    /** @var \companies $company */
                    $company                                = $this->loadData('companies');
                    $company->id_client_owner               = $client->id_client;
                    $company->siren                         = filter_var($_POST['siren'], FILTER_SANITIZE_NUMBER_INT);
                    $company->status_adresse_correspondance = 1;
                    $company->create();

                    $this->projects->id_target_company = $company->id_company;
                    $this->projects->update();

                    $this->checkTargetCompanyRisk();

                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                    die;
                case 'select':
                    $this->projects->id_target_company = $_POST['id_target_company'];
                    $this->projects->update();

                    $this->checkTargetCompanyRisk();

                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                    die;
                case 'swap':
                    if ($this->isTakeover() && false === empty($this->projects->id_target_company) && $this->projects->status < ProjectsStatus::A_FUNDER) {
                        /** @var \company_rating_history $companyRatingHistory */
                        $companyRatingHistory   = $this->loadData('company_rating_history');
                        $companyRatingHistory   = $companyRatingHistory->select('id_company = ' . $this->projects->id_target_company, 'added DESC', 0, 1);
                        $companyRatingHistoryId = 0;

                        if (isset($companyRatingHistory[0]['id_company_rating_history'])) {
                            $companyRatingHistoryId = $companyRatingHistory[0]['id_company_rating_history'];
                        }

                        /** @var \companies $company */
                        $company = $this->loadData('companies');
                        $company->get($this->projects->id_target_company);

                        $targetCompanyId                           = $this->projects->id_company;
                        $this->projects->id_company                = $this->projects->id_target_company;
                        $this->projects->id_target_company         = $targetCompanyId;
                        $this->projects->id_company_rating_history = $companyRatingHistoryId;
                        $this->projects->balance_count             = null === $company->date_creation ? 0 : \DateTime::createFromFormat('Y-m-d', $company->date_creation)->diff(new \DateTime())->y;
                        $this->projects->id_dernier_bilan          = 0;
                        $this->projects->update();
                    }

                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                    die;
                default:
                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                    die;
            }
        }
    }

    /**
     * @return bool
     */
    private function isTakeover()
    {
        if (false === empty($this->needs)) {
            $needs = $this->needs;
        } else {
            /** @var \project_need $projectNeed */
            $projectNeed = $this->loadData('project_need');
            $needs       = $projectNeed->getTree();
        }

        return in_array(
            $this->projects->id_project_need,
            array_column($needs[\project_need::PARENT_TYPE_TRANSACTION]['children'], 'id_project_need')
        );
    }

    /**
     * @return bool
     */
    private function loadTargetCompany()
    {
        if (empty($this->projects->id_target_company) || false === $this->targetCompany->get($this->projects->id_target_company)) {
            return false;
        }

        $this->targetRatings = $this->loadRatings($this->targetCompany);

        return true;
    }

    private function checkTargetCompanyRisk()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->projects->id_target_company);

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $eligibility           = $projectRequestManager->checkCompanyRisk($company, $_SESSION['user']['id_user']);

        if (is_array($eligibility) && false === empty($eligibility)) {
            $projectRequestManager->addRejectionProjectStatus($eligibility[0], $this->projects, $_SESSION['user']['id_user']);
        }
    }

    public function _autocompleteCompanyName()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $aNames = [];

        if ($sTerm = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING)) {
            /** @var \companies $oCompanies */
            $oCompanies = $this->loadData('companies');
            $aNames = $oCompanies->searchByName($sTerm);
        }

        echo json_encode($aNames);
    }

    /**
     * @param array  $codes
     * @param string $formType
     * @param string $extraClass
     * @return string
     */
    protected function generateBalanceLineHtml(array $codes, $formType, $extraClass = '')
    {
        $html = '';
        foreach ($codes as $code) {
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

    /**
     * @param string $label
     * @param string $codeLabel
     * @param array  $codes
     * @param string $formType
     * @param string $domId
     * @param bool   $displayNegativeValue
     * @param array  $amountsToUse
     *
     * @return string
     */
    protected function generateBalanceSubTotalLineHtml($label, $codeLabel, $codes, $formType, $domId = '', $displayNegativeValue = true, $amountsToUse = [])
    {
        $html             = '<tr class="sub-total"><td>' . $label . '</td><td>' . $codeLabel . '</td>';
        $previousTotal    = null;
        $column           = 0;
        $index            = 0;
        $cumulativeAmount = [];

        foreach ($this->aBalanceSheets as $balanceSheet) {
            $cumulativeAmount[$index] = 0;

            if ($formType != $balanceSheet['form_type']) {
                $html .= '<td></td>';
                if ($column) {
                    $html .= '<td></td>';
                }
            } else {
                if (false === empty($amountsToUse[$index])) {
                    $total = $this->sumBalances($codes, $balanceSheet) + $amountsToUse[$index];
                } else {
                    $total = $this->sumBalances($codes, $balanceSheet);
                }

                if (false === $displayNegativeValue && $total < 0) {
                    $total = 0;
                }
                $cumulativeAmount[$index] = $total;

                if ($column) {
                    $movement = empty($total) || empty($previousTotal) ? 'N/A' : round(($previousTotal - $total) / abs($total) * 100) . '&nbsp;%';
                    $html     .= '<td>' . $movement . '</td>';
                }
                $formattedValue = $this->ficelle->formatNumber($total, 0);
                $html           .= '<td id="' . $domId . '" data-total="' . $total . '">' . $formattedValue . '</td>';
                $previousTotal  = $total;
            }
            $column++;
            $index++;
        }
        $html .= '</tr>';

        return ['html' => $html, 'amounts' => $cumulativeAmount];
    }

    /**
     * @param string $totalLabel
     * @param array  $code
     * @param string $formType
     * @param string $subTotalCodeLabel
     *
     * @return string
     */
    protected function generateBalanceGroupHtml($totalLabel, array $code, $formType, $subTotalCodeLabel = '')
    {
        return $this->generateBalanceLineHtml($code, $formType) . $this->generateBalanceSubTotalLineHtml($totalLabel, $subTotalCodeLabel, $code, $formType)['html'];
    }

    /**
     * @param string $label
     * @param array  $codes
     * @param string $formType
     * @param string $domId
     *
     * @return string
     */
    protected function generateBalanceTotalLineHtml($label, array $codes, $formType, $domId = '')
    {
        $html          = '<tr><th colspan="2">' . $label . '</th>';
        $previousTotal = null;
        $index         = 0;
        $column        = 0;

        foreach ($this->aBalanceSheets as $balanceSheet) {
            if ($formType != $balanceSheet['form_type']) {
                $html .= '<th></th>';

                if ($column) {
                    $html .= '<th></th>';
                }
            } else {
                $total = $this->sumBalances($codes, $balanceSheet);

                if ($column) {
                    $movement = empty($total) || empty($previousTotal) ? 'N/A' : round(($previousTotal - $total) / abs($total) * 100) . '&nbsp;%';
                    $html     .= '<th>' . $movement . '</th>';
                }
                $formattedValue = $this->ficelle->formatNumber($total, 0);
                $html           .= '<th id="' . $domId . $index++ . '" data-total="' . $total . '">' . $formattedValue . '</th>';
                $previousTotal  = $total;
            }
            $column++;
        }
        $html .= '</tr>';

        return $html;
    }

    /**
     * @param string $case
     * @return string
     */
    protected function negative($case)
    {
        if ('-' === substr($case, 0, 1)) {
            return substr($case, 1);
        } else {
            return '-' . $case;
        }
    }

    public function _regenerate_dirs()
    {
        $this->hideDecoration();

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

    public function _partner_products()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \projects $project */
        $project = $this->loadData('projects');
        /** @var \partner $partner */
        $partner = $this->loadData('partner');

        if (
            isset($this->params[0], $this->params[1])
            && $project->get($this->params[0])
            && $partner->get($this->params[1])
        ) {
            $project->id_partner = $partner->id;

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager $productManager */
            $productManager   = $this->get('unilend.service_product.product_manager');
            $eligibleProducts = $productManager->findEligibleProducts($project, true);
            $translator       = $this->get('translator');
            $partnerProducts  = [];

            foreach ($eligibleProducts as $eligibleProduct) {
                $partnerProducts[] = [
                    'id'    => $eligibleProduct->id_product,
                    'label' => $translator->trans('product_label_' . $eligibleProduct->label)
                ];
            }

            echo json_encode($partnerProducts);
        }
    }

    public function _add_wire_transfer_out_lightbox()
    {
        $this->hideDecoration();

        if (false === empty($this->params[0])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BorrowerManager $borrowerManager */
            $borrowerManager = $this->get('unilend.service.borrower_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\PartnerManager $partnerManager */
            $partnerManager = $this->get('unilend.service.partner_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WireTransferOutManager $wireTransferOutManager */
            $wireTransferOutManager = $this->get('unilend.service.wire_transfer_out_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            /** @var \NumberFormatter $currencyFormatter */
            $currencyFormatter = $this->get('currency_formatter');

            $this->companyRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
            $this->project           = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->params[0]);
            $this->borrowerMotif     = $borrowerManager->getBorrowerBankTransferLabel($this->project);
            $this->bankAccounts[]    = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($this->project->getIdCompany()->getIdClientOwner());
            $this->bankAccounts      = array_merge($this->bankAccounts, $partnerManager->getPartnerThirdPartyBankAccounts($this->project->getIdPartner()));
            $restFunds               = $projectManager->getRestOfFundsToRelease($this->project, true);
            $this->restFunds         = $currencyFormatter->formatCurrency($restFunds, 'EUR');

            if ($this->request->isMethod('POST')) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
                $projectManager = $this->get('unilend.service.project_manager');

                if ($this->request->request->get('date')) {
                    $date = DateTime::createFromFormat('d/m/Y', $this->request->request->get('date'));
                } else {
                    $date = null;
                }

                if (null !== $date && $date <= new DateTime()) {
                    $_SESSION['freeow']['title']   = 'Transfert de fonds';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé. La date de transfert n\'est pas valide.';
                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                    die;
                }

                $amount = $this->loadLib('ficelle')->cleanFormatedNumber($this->request->request->get('amount'));
                if ($amount <= 0) {
                    $_SESSION['freeow']['title']   = 'Transfert de fonds';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé. Montant n\'est pas valide.';
                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                    die;
                }

                $restFunds = $projectManager->getRestOfFundsToRelease($this->project, true);
                if ($amount > $restFunds) {
                    $_SESSION['freeow']['title']   = 'Transfert de fonds';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé. Montant trop élévé.';
                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                    die;
                }

                $bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->find($this->request->request->get('bank_account'));
                $wallet      = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);
                $user        = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);

                try {
                    $wireTransferOutManager->createTransfer($wallet, $amount, $bankAccount, $this->project, $user, $date, $this->request->request->get('pattern'));
                } catch (\Exception $exception) {
                    $this->get('logger')->error($exception->getMessage(), ['methode' => __METHOD__]);
                    $_SESSION['freeow']['title']   = 'Transfert de fonds échoué';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé';

                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                    die;
                }

                $_SESSION['freeow']['title']   = 'Transfert de fonds';
                $_SESSION['freeow']['message'] = 'Le transfert de fonds a été créé avec succès ';
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                die;
            }
        }
    }

    public function _refuse_wire_transfer_out_lightbox()
    {
        $this->hideDecoration();

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === empty($this->params[0])) {
            /** @var \NumberFormatTest currencyFormatter */
            $this->currencyFormatter = $this->get('currency_formatter');

            $this->wireTransferOut       = $entityManager->getRepository('UnilendCoreBusinessBundle:Virements')->find($this->params[0]);
            $this->bankAccountRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount');
            $this->companyRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        }

        if (false === empty($this->params[0]) && $this->request->isMethod('POST') && $this->wireTransferOut) {
            $forbiddenStatus = [Virements::STATUS_CLIENT_DENIED, Virements::STATUS_DENIED, Virements::STATUS_VALIDATED, Virements::STATUS_SENT];

            if (false === in_array($this->wireTransferOut->getStatus(), $forbiddenStatus)) {
                $this->wireTransferOut->setStatus(Virements::STATUS_DENIED);
                $entityManager->flush($this->wireTransferOut);
                $_SESSION['freeow']['title']   = 'Refus de transfert de fonds';
                $_SESSION['freeow']['message'] = 'Le transfert de fonds a été refusé avec succès ';
            } else {
                $_SESSION['freeow']['title']   = 'Refus de transfert de fonds';
                $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a été refusé.';
            }

            if (false === empty($this->params[1]) && 'project' === $this->params[1]) {
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->wireTransferOut->getProject()->getIdProject());
            } else {
                header('Location: ' . $this->lurl . '/transferts/virement_emprunteur/');
            }
            die;
        }
    }

    public function _details_impayes()
    {
        $this->useOneUi();
        /** @var \users $user */
        $user = $this->loadData('users');
        $user->get($_SESSION['user']['id_user']);

        if (\users_types::TYPE_RISK == $user->id_user_type
            || $user->id_user == 28
            || isset($this->params[0]) && 'risk' == $this->params[0] && in_array($user->id_user_type, [\users_types::TYPE_ADMIN, \users_types::TYPE_IT])
        ) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            if (false === empty($this->params[0])) {
                // Get details for all blocks
            }
        }
    }
}
