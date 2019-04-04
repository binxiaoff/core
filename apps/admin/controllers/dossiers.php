<?php

use Psr\Log\LoggerInterface;
use Unilend\Entity\{AcceptationsLegalDocs, AddressType, Attachment, AttachmentType, BankAccount, BorrowingMotive, Companies, CompanyAddress, CompanyBeneficialOwnerDeclaration, CompanyClient,
    CompanyStatus, CompanyStatusHistory, Echeanciers, EcheanciersEmprunteur, Loans, Operation, Partner, PartnerProjectAttachment, Prelevements, ProjectAbandonReason, ProjectAttachmentType,
    ProjectBeneficialOwnerUniversign, ProjectNotification, ProjectRejectionReason, ProjectRepaymentTask, Projects, ProjectsComments, ProjectsNotes, ProjectsPouvoir, ProjectsStatus,
    ProjectsStatusHistory, ProjectStatusHistoryReason, Users, UsersTypes, Virements, Wallet, WalletType, Zones};
use Unilend\Bundle\CoreBusinessBundle\Service\{BackOfficeUserManager, ProjectManager, ProjectRequestManager, TermsOfSaleManager, WireTransferOutManager, WorkingDaysManager};

class dossiersController extends bootstrap
{
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
    /** @var CompanyAddress */
    protected $companyMainAddress;
    /** @var CompanyAddress */
    protected $companyPostalAddress;
    /** @var \projects_pouvoir */
    protected $projects_pouvoir;
    /** @var \notifications */
    protected $notifications;
    /** @var \clients_gestion_mails_notif */
    protected $clients_gestion_mails_notif;
    /** @var \clients_gestion_notifications */
    protected $clients_gestion_notifications;

    /** @var array */
    protected $searchResult;
    /** @var int */
    protected $resultsCount;

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';

        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
    }

    /**
     * Search page
     */
    public function _default()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager       = $this->get('doctrine.orm.entity_manager');
        $projectStatus       = $entityManager->getRepository(ProjectsStatus::class)->findBy([], ['status' => 'ASC']);
        $this->projectStatus = [];

        foreach ($projectStatus as $status) {
            $this->projectStatus[$status->getStatus()] = $status;
        }

        $this->aAnalysts        = $this->users->select('status = ' . Users::STATUS_ONLINE . ' AND id_user_type = ' . UsersTypes::TYPE_RISK);
        $this->aSalesPersons    = $this->users->select('status = ' . Users::STATUS_ONLINE . ' AND id_user_type = ' . UsersTypes::TYPE_COMMERCIAL);

        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->fundingTimeValues = explode(',', $this->settings->value);

        /** @var \project_need $projectNeed */
        $projectNeed = $this->loadData('project_need');
        $this->needs = $projectNeed->getTree();

        $this->page = isset($_POST['page']) ? filter_var($_POST['page'], FILTER_VALIDATE_INT) : 1;
        $this->page = $this->page > 0 ? $this->page : 1;

        if (isset($_POST['form_search_dossier'])) {
            $projectRepository = $entityManager->getRepository(Projects::class);

            if (false === empty($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT) && null !== $projectRepository->find($_POST['id'])) {
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $_POST['id']);
                exit;
            }

            $projectRequestManager = $this->get('unilend.service.project_request_manager');

            $status        = $this->request->request->filter('status', null, FILTER_VALIDATE_INT);
            $status        = $status ? [$status] : null;
            $siren         = $projectRequestManager->validateSiren($this->request->request->filter('siren', null, FILTER_SANITIZE_STRING)) ?: null;
            $companyName   = $this->request->request->filter('raison-sociale', null, FILTER_SANITIZE_STRING) ?: null;
            $startDate     = $this->request->request->get('date1');
            $startDate     = $startDate && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $startDate) ? \DateTime::createFromFormat('d/m/Y', $startDate) : null;
            $endDate       = $this->request->request->get('date2');
            $endDate       = $endDate && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $endDate) ? \DateTime::createFromFormat('d/m/Y', $endDate) : null;
            $duration      = $this->request->request->filter('duree', null, FILTER_VALIDATE_INT) ?: null;
            $projectNeed   = $this->request->request->filter('projectNeed', null, FILTER_VALIDATE_INT) ?: null;
            $salesPersonId = $this->request->request->filter('commercial', null, FILTER_VALIDATE_INT) ?: null;
            $riskAnalystId = $this->request->request->filter('analyste', null, FILTER_VALIDATE_INT) ?: null;

            $limit  = $this->nb_lignes;
            $offset = ($this->page - 1) * $this->nb_lignes;

            $this->searchResult = $projectRepository->search($status, $siren, $companyName, $startDate, $endDate, $duration, $projectNeed, $salesPersonId, $riskAnalystId, $limit, $offset);
            $this->resultsCount = count($this->searchResult);

            if ($this->resultsCount >= $this->nb_lignes) {
                $this->resultsCount = $projectRepository->countSearch($status, $siren, $companyName, $startDate, $endDate, $duration, $projectNeed, $salesPersonId, $riskAnalystId);
            }
        } elseif (isset($this->params[0]) && 1 === preg_match('/^[1-9]([0-9,]*[0-9]+)*$/', $this->params[0])) {
            $statuses           = explode(',', $this->params[0]);
            $projectRepository  = $entityManager->getRepository(Projects::class);
            $this->searchResult = $projectRepository->search($statuses);
            $this->resultsCount = count($this->searchResult);

            if ($this->resultsCount >= $this->nb_lignes) {
                $this->resultsCount = $projectRepository->countSearch($statuses);
            }
        }

        if (isset($this->searchResult)) {
            $this->projectNotesRepository = $entityManager->getRepository(ProjectsNotes::class);
        }

        /** @var BackOfficeUserManager $backOfficeUserManager */
        $backOfficeUserManager    = $this->get('unilend.service.back_office_user_manager');
        $this->isRiskUser         = $backOfficeUserManager->isUserGroupRisk($this->userEntity);
        $this->hasRepaymentAccess = $backOfficeUserManager->isGrantedZone($this->userEntity, Zones::ZONE_LABEL_REPAYMENT);
    }

    /**
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function _edit()
    {
        $this->projects                      = $this->loadData('projects');
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
        $this->loans                         = $this->loadData('loans');
        $this->projects_pouvoir              = $this->loadData('projects_pouvoir');
        $this->echeanciers                   = $this->loadData('echeanciers');
        $this->notifications                 = $this->loadData('notifications');
        $this->clients_gestion_mails_notif   = $this->loadData('clients_gestion_mails_notif');
        $this->clients_gestion_notifications = $this->loadData('clients_gestion_notifications');
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
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BeneficialOwnerManager $beneficialOwnerManager */
        $beneficialOwnerManager = $this->get('unilend.service.beneficial_owner_manager');
        /** @var BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        $this->beneficialOwnerDeclaration = null;

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->projectEntity   = $entityManager->getRepository(Projects::class)->find($this->projects->id_project);
            $this->taxFormTypes    = $companyTaxFormType->select();
            $this->allTaxFormTypes = [];

            foreach ($this->taxFormTypes as $formType) {
                $this->allTaxFormTypes[$formType['label']] = $companyBalanceDetailsType->select('id_company_tax_form_type = ' . $formType['id_type']);
            }

            $this->aBorrowingMotives = $borrowingMotive->select('`rank`');

            /** @var \tax_type $taxType */
            $taxType = $this->loadData('tax_type');

            $taxRate        = $taxType->getTaxRateByCountry('fr');
            $this->fVATRate = $taxRate[\Unilend\Entity\TaxType::TYPE_VAT] / 100;

            $this->companies->get($this->projects->id_company, 'id_company');
            $this->clients->get($this->companies->id_client_owner, 'id_client');
            $this->companyMainAddress   = $this->projectEntity->getIdCompany()->getIdAddress();
            $this->companyPostalAddress = $this->projectEntity->getIdCompany()->getIdPostalAddress();
            $this->projects_notes->get($this->projects->id_project, 'id_project');
            $this->project_cgv->get($this->projects->id_project, 'id_project');

            $this->projectStatus = $entityManager->getRepository(ProjectsStatus::class)->findOneBy(['status' => $this->projectEntity->getStatus()]);

            try {
                $projectStatusHistoryRejectionReason = $entityManager->getRepository(ProjectStatusHistoryReason::class)
                    ->findLastRejectionReasonByProjectAndLabel($this->projects->id_project, ProjectRejectionReason::UNKNOWN_SIREN);
            } catch (\Exception $exception) {
                $projectStatusHistoryRejectionReason = null;

                $this->get('logger')->error('Could not find project status history reason. Error: ' . $exception->getMessage(), [
                    'id_project' => $this->projects->id_project,
                    'function'   => __FUNCTION__,
                    'class'      => __CLASS__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine()
                ]);
            }
            $this->isSirenEditable = $this->canEditSiren();

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
            $projectStatusManager   = $this->get('unilend.service.project_status_manager');
            $this->statusReasonText = $projectStatusManager->getStatusReasonByProject($this->projectEntity);

            if ($this->projects->status == ProjectsStatus::STATUS_FUNDED) {
                $proxy       = $this->projects_pouvoir->select('id_project = ' . $this->projects->id_project);
                $this->proxy = empty($proxy) ? [] : $proxy[0];

                /** @var \clients_mandats $clientMandate */
                $clientMandate = $this->loadData('clients_mandats');
                $mandate       = $clientMandate->select('id_project = ' . $this->projects->id_project, 'updated DESC');
                $this->mandate = empty($mandate) ? [] : $mandate[0];

                $this->validBankAccount = $entityManager->getRepository(BankAccount::class)->getClientValidatedBankAccount($this->clients->id_client);

                if (false === $beneficialOwnerManager->companyNeedsBeneficialOwnerDeclaration($this->projects->id_company)) {
                    $companyBeneficialOwnerDeclaration = $entityManager->getRepository(CompanyBeneficialOwnerDeclaration::class)->findBy(['idCompany' => $this->projects->id_company]);
                    if (false === empty($companyBeneficialOwnerDeclaration)) {
                        $beneficialOwnerDeclaration       = $entityManager->getRepository(ProjectBeneficialOwnerUniversign::class)->findOneBy(['idProject' => $this->projects->id_project], ['id' => 'DESC']);
                        $this->beneficialOwnerDeclaration = $beneficialOwnerDeclaration;
                    }
                }
            }

            $this->latitude  = null === $this->companyMainAddress ? 0 : (float) $this->companyMainAddress->getLatitude();
            $this->longitude = null === $this->companyMainAddress ? 0 : (float) $this->companyMainAddress->getLongitude();

            $this->aAnnualAccountsDates = [];
            $userRepository             = $entityManager->getRepository(Users::class);
            /** @var \Doctrine\Common\Collections\ArrayCollection analysts */
            $this->analysts = $userManager->getAnalysts();
            if (false === empty($this->projects->id_analyste) && $currentAnalyst = $userRepository->find($this->projects->id_analyste)) {
                if (false === in_array($currentAnalyst, $this->analysts)) {
                    $this->analysts[] = $currentAnalyst;
                }
            }
            $this->salesPersons = $userManager->getSalesPersons();
            if (false === empty($this->projects->id_commercial) && $currentSalesPerson = $userRepository->find($this->projects->id_commercial)) {
                if (false === in_array($currentSalesPerson, $this->salesPersons)) {
                    $this->salesPersons[] = $currentSalesPerson;
                }
            }
            $this->projectComments     = $entityManager->getRepository(ProjectsComments::class)
                ->findBy(['idProject' => $this->projects->id_project], ['added' => 'DESC']);
            $this->aAllAnnualAccounts = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'cloture_exercice_fiscal DESC');

            $this->possibleProjectStatus = $projectStatusManager->getPossibleStatus($this->projectEntity);
            if ($this->projectEntity->getStatus()) {
                $this->currentProjectStatus = $entityManager->getRepository(ProjectsStatus::class)->findOneBy(['status' => $this->projectEntity->getStatus()]);
            }

            if (empty($this->projects->id_dernier_bilan)) {
                $this->lbilans = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'cloture_exercice_fiscal DESC', 0, 3);

                if (false === empty($this->lbilans)) {
                    $this->projects->id_dernier_bilan = $this->lbilans[0]['id_bilan'];
                    $this->projects->update();
                }
            } else {
                $this->lbilans = $this->companies_bilans->select('id_company = ' . $this->companies->id_company . ' AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $this->projects->id_dernier_bilan . ')',
                    'cloture_exercice_fiscal DESC', 0, 3);
            }

            if (empty($this->lbilans)) {
                $this->lCompanies_actif_passif = [];
                $this->aBalanceSheets          = [];
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
            $projectNeed                     = $this->loadData('project_need');
            $needs                           = $projectNeed->getTree();
            $this->needs                     = $needs;
            $this->isTakeover                = $this->isTakeover();
            $this->projectHasMonitoringEvent = $this->get('unilend.service.risk_data_monitoring_manager')->projectHasMonitoringEvents($this->projectEntity);

            if (isset($_POST['problematic_status']) && $this->projects->status != $_POST['problematic_status']) {
                $this->problematicStatusForm($this->projectEntity);
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
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
                $oClosingDate                                        = new \DateTime($aLastAnnualAccounts['cloture_exercice_fiscal']);
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
            } elseif (isset($_POST['pret_refuse']) && $_POST['pret_refuse'] == 1) {
                if ($this->projects->status < ProjectsStatus::STATUS_CANCELLED) {
                    /** @var LoggerInterface $logger */
                    $logger = $this->get('logger');

                    $entityManager->getConnection()->beginTransaction();
                    try {
                        $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::STATUS_CANCELLED, $this->projects);

                        /** @var \echeanciers $echeanciers */
                        $echeanciers = $this->loadData('echeanciers');
                        $echeanciers->delete($this->projects->id_project, 'id_project');

                        $loanRepository = $entityManager->getRepository(Loans::class);
                        $lendersCount   = $loanRepository->getLenderNumber($this->projects->id_project);
                        $loans          = $loanRepository->findBy(['idProject' => $this->projects->id_project, 'status' => Loans::STATUS_ACCEPTED]);

                        foreach ($loans as $loan) {
                            $loan->setStatus(Loans::STATUS_REJECTED);
                            $entityManager->flush($loan);

                            $this->get('unilend.service.operation_manager')->refuseLoan($loan);
                            /** @var \Unilend\Entity\Wallet $wallet */
                            $wallet   = $loan->getWallet();
                            $keywords = [
                                'firstName'         => $wallet->getIdClient()->getPrenom(),
                                'loanAmount'        => $this->ficelle->formatNumber($loan->getAmount() / 100, 0),
                                'companyName'       => $this->companies->name,
                                'otherLendersCount' => $lendersCount - 1,
                                'lenderPattern'     => $wallet->getWireTransferPattern()
                            ];

                            /** @var \Unilend\SwiftMailer\TemplateMessage $message */
                            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-pret-refuse', $keywords);

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

                if ($this->projects->status <= ProjectsStatus::STATUS_REVIEW) {
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
                        $_SESSION['publish_error'] = 'La date de publication du projet doit être au minimum dans 5 minutes et la date de retrait dans plus d\'une heure';

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
                        $this->projects_pouvoir->status        = ProjectsPouvoir::STATUS_SIGNED;
                        $this->projects_pouvoir->create();
                    } else {
                        $_SESSION['freeow']['message'] .= 'Erreur upload pouvoir : ' . $this->upload->getErrorType() . '<br>';
                    }
                }

                if (
                    false === empty($_POST['commercial'])
                    && $_POST['commercial'] != $this->projects->id_commercial
                    && $this->projects->status < ProjectsStatus::STATUS_REQUEST
                ) {
                    if (ProjectsStatus::STATUS_CANCELLED != $this->projects->status) {
                        $_POST['status'] = ProjectsStatus::STATUS_REQUEST;
                    }

                    $latitude  = (float) $this->companies->latitude;
                    $longitude = (float) $this->companies->longitude;

                    if (null !== $this->companyMainAddress && empty($latitude) && empty($longitude)) {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LocationManager $location */
                        $location    = $this->get('unilend.service.location_manager');
                        $coordinates = $location->getCompanyCoordinates($this->companyMainAddress);

                        if ($coordinates) {
                            $this->companyMainAddress->setLatitude($coordinates['latitude']);
                            $this->companyMainAddress->setLongitude($coordinates['longitude']);
                            $entityManager->flush($this->companyMainAddress);
                        }
                    }
                }

                if (
                    false === empty($_POST['analyste'])
                    && $_POST['analyste'] != $this->projects->id_analyste
                    && $this->projects->status < ProjectsStatus::STATUS_REQUEST
                ) {
                    $_POST['status'] = ProjectsStatus::STATUS_REQUEST;
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

                $this->projects->title                = $_POST['title'];
                $this->projects->id_analyste          = isset($_POST['analyste']) ? $_POST['analyste'] : $this->projects->id_analyste;
                $this->projects->id_commercial        = isset($_POST['commercial']) ? $_POST['commercial'] : $this->projects->id_commercial;
                $this->projects->id_borrowing_motive  = $_POST['motive'];
                $this->projects->id_company_submitter = empty($_POST['company_submitter']) ? null : $_POST['company_submitter'];
                $this->projects->id_client_submitter  = empty($_POST['client_submitter']) ? null : $_POST['client_submitter'];

                if ($this->projects->status <= ProjectsStatus::STATUS_REQUEST) {
                    $this->projects->id_project_need = $_POST['need'];
                    $this->projects->period          = $_POST['duree'];
                    $this->projects->amount          = $this->ficelle->cleanFormatedNumber($_POST['montant']);

                    if (false === $this->isTakeover() && false === empty($this->projects->id_target_company)) {
                        $this->projects->id_target_company = 0;
                    }
                }

                if ($this->projects->status <= ProjectsStatus::STATUS_REVIEW) {
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

                if ($this->projects->status <= ProjectsStatus::STATUS_REVIEW && null !== $this->companyMainAddress) {
                    $sector = $this->translator->trans('company-sector_sector-' . $this->companies->sector);
                    $this->settings->get('Prefixe URL pages projet', 'type');
                    $this->projects->slug = $this->ficelle->generateSlug($this->settings->value . '-' . $sector . '-' . $this->companyMainAddress->getCity() . '-' . substr(md5($this->projects->title . $this->projects->id_project),
                            0, 7));
                }

                if ($this->projects->status == ProjectsStatus::STATUS_REVIEW) {
                    if (isset($_POST['date_publication']) && ! empty($_POST['date_publication'])) {
                        $publicationDate                  = \DateTime::createFromFormat('d/m/Y H:i',
                            $_POST['date_publication'] . ' ' . $_POST['date_publication_heure'] . ':' . $_POST['date_publication_minute']);
                        $this->projects->date_publication = $publicationDate->format('Y-m-d H:i:s');
                    }

                    if (isset($_POST['date_retrait']) && ! empty($_POST['date_retrait'])) {
                        $endOfPublicationDate         = \DateTime::createFromFormat('d/m/Y H:i', $_POST['date_retrait'] . ' ' . $_POST['date_retrait_heure'] . ':' . $_POST['date_retrait_minute']);
                        $this->projects->date_retrait = $endOfPublicationDate->format('Y-m-d H:i:s');
                    }
                }

                if ($this->projects->status == ProjectsStatus::STATUS_REVIEW) {
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
                    $projectStatusManager->addProjectStatus($this->userEntity, $_POST['status'], $this->projects);
                }

                if ($this->isSirenEditable) {
                    // Save any changes made until now and refresh objects data
                    $this->projects->update();
                    $this->companies->update();

                    $this->setFranchiseeSiren();
                }

                $_SESSION['freeow']['message'] .= 'Modifications enregistrées avec succès';

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            } elseif (isset($_POST['send_form_date_retrait'])) {
                if (
                    isset($_POST['date_retrait'], $_POST['date_retrait_heure'], $_POST['date_retrait_minute'])
                    && 1 === preg_match('#[0-9]{2}/[0-9]{2}/[0-9]{8}#', $_POST['date_retrait'] . $_POST['date_retrait_heure'] . $_POST['date_retrait_minute'])
                    && $this->projects->status <= ProjectsStatus::STATUS_ONLINE
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

            $this->xerfi                       = $this->loadData('xerfi');
            $this->sectors                     = $this->loadData('company_sector')->select();
            $this->sources                     = $this->getSourcesList();
            $this->ratings                     = $this->loadRatings($this->companies, $this->projects->id_company_rating_history, $this->xerfi);
            $this->ratings['unilend_prescore'] = $this->addUnilendPrescoring($this->projects_notes);
            $this->aCompanyProjects            = $this->companies->getProjectsBySIREN();
            $this->iCompanyProjectsCount       = count($this->aCompanyProjects);
            $this->fCompanyOwedCapital         = $this->companies->getOwedCapitalBySIREN();
            $companiesRepository               = $entityManager->getRepository(Companies::class);
            $this->bIsProblematicCompany       = $companiesRepository->isProblematicCompany($this->companies->siren);

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

            if (false === empty($this->projects->period) && false === in_array($this->projects->period, $this->dureePossible)) {
                array_push($this->dureePossible, $this->projects->period);
                sort($this->dureePossible);
            }

            /** @var \Unilend\Repository\PartnerRepository $partnerRepository */
            $partnerRepository = $entityManager->getRepository(Partner::class);

            $this->eligibleProducts       = $productManager->findEligibleProducts($this->projectEntity, true);
            $this->selectedProduct        = $product;
            $this->isProductUsable        = empty($product->id_product) ? false : in_array($this->selectedProduct, $this->eligibleProducts);
            $this->partnerList            = $partnerRepository->getPartnersSortedByName(Partner::STATUS_VALIDATED);
            $this->partnerProduct         = $this->loadData('partner_product');
            $this->isUnilendPartner       = Partner::PARTNER_CALS_ID === $this->projectEntity->getIdPartner()->getId();
            $this->agencies               = [];
            $this->submitters             = [];
            $this->hasBeneficialOwner     = null !== $entityManager->getRepository(CompanyBeneficialOwnerDeclaration::class)->findCurrentDeclarationByCompany($this->projects->id_company);
            $this->ownerIsBeneficialOwner = $beneficialOwnerManager->checkBeneficialOwnerDeclarationContainsAtLeastCompanyOwner($this->projects->id_company);

            if (false === empty($this->projects->id_product)) {
                $this->partnerProduct->get($this->projects->id_product, 'id_partner = ' . $this->projects->id_partner . ' AND id_product');
            }

            if (false === $this->isUnilendPartner) {
                $this->agencies = $entityManager->getRepository(Companies::class)->findBy(['idParentCompany' => $this->projectEntity->getIdPartner()->getIdCompany()->getIdCompany()]);

                /** @var Companies $headquarters */
                $headquarters = clone $this->projectEntity->getIdPartner()->getIdCompany();
                $headquarters->setName('Siège');
                $this->agencies[] = $headquarters;
            }
            usort($this->agencies, function ($first, $second) {
                return strcasecmp($first->getName(), $second->getName());
            });

            if ($this->projectEntity->getIdCompanySubmitter() && $this->projectEntity->getIdCompanySubmitter()->getIdCompany()) {
                $companyClients = $entityManager->getRepository(CompanyClient::class)->findBy(['idCompany' => $this->projectEntity->getIdCompanySubmitter()]);

                foreach ($companyClients as $companyClient) {
                    $this->submitters[$companyClient->getIdClient()->getIdClient()] = $companyClient->getIdClient();
                }
            }

            if (
                $this->projectEntity->getIdClientSubmitter()
                && $this->projectEntity->getIdClientSubmitter()->getIdClient()
                && false === isset($this->submitters[$this->projectEntity->getIdClientSubmitter()->getIdClient()])
            ) {
                $this->submitters[] = $this->projectEntity->getIdClientSubmitter();
            }
            usort($this->submitters, function ($first, $second) {
                return strcasecmp($first->getPrenom(), $second->getPrenom());
            });

            if (false === empty($this->projects->risk) && false === empty($this->projects->period) && $this->projects->status >= ProjectsStatus::STATUS_REVIEW) {
                if (false === empty($this->projects->id_rate)) {
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BidManager $bidManager */
                    $bidManager     = $this->get('unilend.service.bid_manager');
                    $rateRange      = $bidManager->getProjectRateRange($this->projects);
                    $this->rate_min = $rateRange['rate_min'];
                    $this->rate_max = $rateRange['rate_max'];
                }
            }

            $attachmentTypes          = $entityManager->getRepository(ProjectAttachmentType::class)->getAttachmentTypes();
            $mandatoryAttachmentTypes = array_map(function (PartnerProjectAttachment $type) {
                return $type->getAttachmentType()->getId();
            }, $this->projectEntity->getIdPartner()->getAttachmentTypes(true));

            $this->attachmentTypes                   = $attachmentTypes;
            $this->mandatoryAttachmentTypes          = $mandatoryAttachmentTypes;
            $this->projectAttachmentsByType          = [];
            $this->projectAttachmentsCountByCategory = [];

            /** @var \Unilend\Entity\ProjectAttachment $projectAttachment */
            foreach ($this->projectEntity->getAttachments() as $projectAttachment) {
                $this->projectAttachmentsByType[$projectAttachment->getAttachment()->getType()->getId()][] = $projectAttachment;
            }

            foreach ($this->projectAttachmentsByType as $attachmentTypeId => $attachmentsByType) {
                if (false === isset($attachmentTypes[$attachmentTypeId])) {
                    continue;
                }

                $categoryId = $attachmentTypes[$attachmentTypeId]->getIdCategory()->getId();

                if (false === isset($this->projectAttachmentsCountByCategory[$categoryId])) {
                    $this->projectAttachmentsCountByCategory[$categoryId] = 0;
                }

                ++$this->projectAttachmentsCountByCategory[$categoryId];
            }

            $this->isFundsCommissionRateEditable = $this->isFundsCommissionRateEditable();
            $this->lastBalanceSheet              = $entityManager->getRepository(Attachment::class)->findOneBy([
                'idClient' => $this->projectEntity->getIdCompany()->getIdClientOwner(),
                'idType'   => AttachmentType::DERNIERE_LIASSE_FISCAL
            ]);

            if ($this->isTakeover()) {
                $this->loadTargetCompany();
            }

            $this->loadEarlyRepaymentInformation(false);
            $this->treeRepository = $this->get('doctrine.orm.entity_manager')->getRepository(Tree::class);
            $this->legalDocuments = $this->get('doctrine.orm.entity_manager')->getRepository(AcceptationsLegalDocs::class)->findBy(['idClient' => $this->clients->id_client]);

            $this->companyManager      = $this->get('unilend.service.company_manager');
            $this->projectStatusHeader = '';

            if (null !== $this->projectEntity->getCloseOutNettingDate()) {
                $this->projectStatusHeader = 'Terme déchu le ' . $this->projectEntity->getCloseOutNettingDate()->format('d/m/Y');
            }

            if (
                $this->projectEntity->getIdCompany()->getIdStatus()
                && CompanyStatus::STATUS_IN_BONIS !== $this->projectEntity->getIdCompany()->getIdStatus()->getLabel()
            ) {
                $this->projectStatusHeader .= $this->projectStatusHeader !== '' ? ' - ' : '';
                $this->projectStatusHeader .= 'Société en ' . $this->companyManager->getCompanyStatusNameByLabel($this->projectEntity->getIdCompany()->getIdStatus()->getLabel());
            }

            $this->transferFunds($this->projectEntity);
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRatingManager $projectRatingManager */
            $projectRatingManager = $this->get('unilend.service.project_rating_manager');
            /** @var \NumberFormatter $numberFormatter */
            $numberFormatter                = $this->get('number_formatter');
            $this->projectRating            = $numberFormatter->format($projectRatingManager->getRating($this->projectEntity)) . ' étoiles';
            $this->projectCommiteeAvgGrade  = $numberFormatter->format($projectRatingManager->calculateCommitteeAverageGrade($this->projectEntity));
            $this->projectAbandonReasonList = $entityManager->getRepository(ProjectAbandonReason::class)
                ->findBy(['status' => ProjectAbandonReason::STATUS_ONLINE], ['reason' => 'ASC']);
        } else {
            header('Location: ' . $this->lurl . '/dossiers');
            die;
        }
    }

    /**
     * @return bool
     */
    private function canEditSiren(): bool
    {
        /** @var BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if (
            empty($this->companies->siren)
            && BorrowingMotive::ID_MOTIVE_FRANCHISER_CREATION == $this->projects->id_borrowing_motive
            && ($userManager->isGrantedRisk($this->userEntity) || $userManager->isGrantedSales($this->userEntity))
        ) {
            return true;
        }

        return false;
    }

    private function setFranchiseeSiren(): void
    {
        /** @var ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $siren                 = $projectRequestManager->validateSiren($this->request->request->get('siren', ''));

        if ($siren) {
            $this->companies->siren = $siren;
            $this->companies->update();

            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $company       = $entityManager->getRepository(Companies::class)->find($this->companies->id_company);
            // Refresh entity data because it will be used further in checkProjectRisk
            $entityManager->refresh($company);

            $projectRequestManager->checkProjectRisk($this->projectEntity, $this->userEntity->getIdUser());

            // Reload the data projects to refresh information that may have been modified via doctrine entity
            $this->projects->get($this->projects->id_project);
        }
    }

    private function transferFunds(Projects $project)
    {
        if ($project->getStatus() >= ProjectsStatus::STATUS_REPAYMENT) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager              = $this->get('unilend.service.project_manager');
            $this->companyRepository     = $entityManager->getRepository(Companies::class);
            $this->bankAccountRepository = $entityManager->getRepository(BankAccount::class);
            $this->currencyFormatter     = $this->get('currency_formatter');

            $this->restFunds        = $projectManager->getRestOfFundsToRelease($project, true);
            $this->wireTransferOuts = $project->getWireTransferOuts();
        }
    }

    /**
     * @return bool
     */
    private function isFundsCommissionRateEditable()
    {
        /** @var BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        return (
            $this->projects->status <= ProjectsStatus::STATUS_FUNDED
            && false === empty($this->projects->id_product)
            && $userManager->isGrantedManagement($this->userEntity)
        );
    }

    /**
     * @param array $balances
     * @param array $balanceSheet
     *
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

    public function _setProblematicStatus()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === empty($this->params[0])) {
            if (false !== ($projectId = filter_var($this->params[0], FILTER_VALIDATE_INT))
                && null !== ($project = $entityManager->getRepository(Projects::class)->find($projectId))
            ) {
                $errors = $this->problematicStatusForm($project);

                echo json_encode(['success' => empty($errors), 'error' => $errors]);
                return;
            }
        }
        echo json_encode(['success' => false, 'error' => ['ID projet incorrect']]);
    }

    /**
     * @param Projects $project
     *
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function problematicStatusForm(Projects $project)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusNotificationSender $projectStatusNotificationSender */
        $projectStatusNotificationSender = $this->get('unilend.service.project_status_notification_sender');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');
        $projectStatusManager->addProjectStatus($this->userEntity, $this->request->request->getInt('problematic_status'), $project);

        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->loadData('projects_status_history');
        $projectStatusHistory->loadLastProjectHistory($project->getIdProject());

        /** @var \projects_status_history_details $projectStatusHistoryDetails */
        $projectStatusHistoryDetails                            = $this->loadData('projects_status_history_details');
        $projectStatusHistoryDetails->id_project_status_history = $projectStatusHistory->id_project_status_history;
        $projectStatusHistoryDetails->mail_content              = $this->request->request->get('mail_content', '');
        $projectStatusHistoryDetails->create();

        // This will be displayed on lender loans notifications table
        if (false === empty($this->request->request->get('site_content'))) {
            $firstNotRepaidRepaymentSchedule = $entityManager->getRepository(Echeanciers::class)->findOneBy([
                'idProject' => $project,
                'status'    => [Echeanciers::STATUS_PENDING, Echeanciers::STATUS_PARTIALLY_REPAID]
            ], ['ordre' => 'ASC']);

            /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
            $translator = $this->get('translator');
            $subject    = $translator->trans('lender-notifications_later-repayment-title');
            if (null !== $firstNotRepaidRepaymentSchedule && $firstNotRepaidRepaymentSchedule->getDateEcheance() < new DateTime()) {
                $subject = $translator->trans('lender-notifications_later-repayment-with-repayment-schedule-title', ['%scheduleSequence%' => $firstNotRepaidRepaymentSchedule->getOrdre()]);
            }
            $projectNotification = new ProjectNotification();
            $projectNotification->setIdProject($project)
                ->setSubject($subject)
                ->setContent($this->request->request->get('site_content'))
                ->setIdUser($this->userEntity);

            $entityManager->persist($projectNotification);
            $entityManager->flush($projectNotification);
        }
        $errors = [];

        if ($this->request->request->getBoolean('send_email_borrower')) {
            try {
                $projectStatusNotificationSender->sendProblemStatusEmailToBorrower($project);
            } catch (\Exception $exception) {
                $this->get('logger')->warning(
                    'Problem status email was not sent to borrower. Error : ' . $exception->getMessage(),
                    ['id_project' => $project->getIdProject(), 'method' => __METHOD__]
                );
                $errors[] = 'Échéc à l\'envoi de l\'email emprunteur.';
            }
        }

        if ($this->request->request->getBoolean('send_email') || ProjectsStatus::STATUS_LOSS === $this->request->request->getInt('problematic_status')) {
            try {
                $projectStatusNotificationSender->sendProblemStatusNotificationsToLenders($project);
            } catch (\Exception $exception) {
                $this->get('logger')->warning(
                    'Problem status email was not sent to lenders. Error : ' . $exception->getMessage(),
                    ['id_project' => $project->getIdProject(), 'method' => __METHOD__]
                );
                $errors[] = 'Échéc à l\'envoie de l\'email prêteur';
            }
        }

        return $errors;
    }

    /**
     * @param \companies  $company
     * @param int|null    $companyRatingHistoryId
     * @param \xerfi|null $xerfi
     *
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
        $this->fVATRate = $taxRate[\Unilend\Entity\TaxType::TYPE_VAT] / 100;

        /** @var company_rating $oCompanyRating */
        $oCompanyRating = $this->loadData('company_rating');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager $companyBalanceSheetManager */
        $companyBalanceSheetManager = $this->get('unilend.service.company_balance_sheet_manager');
        /** @var \Unilend\Repository\CompaniesRepository $companiesRepository */
        $companiesRepository = $this->get('doctrine.orm.entity_manager')->getRepository(Companies::class);

        $this->ratings                  = $oCompanyRating->getHistoryRatingsByType($this->oProject->id_company_rating_history);
        $this->aAnnualAccounts          = $oAnnualAccounts->select('id_company = ' . $this->oCompany->id_company . ' AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $this->oProject->id_dernier_bilan . ')',
            'cloture_exercice_fiscal DESC', 0, 3);
        $aAnnualAccountsIds             = array_column($this->aAnnualAccounts, 'id_bilan');
        $this->bIsProblematicCompany    = $companiesRepository->isProblematicCompany($this->oCompany->siren);
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
        echo $sCSV;
        die;
    }

    public function _ajax_rejection()
    {
        $this->hideDecoration();

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $this->rejectionReasons = $entityManager->getRepository(ProjectRejectionReason::class)
            ->findBy(['status' => ProjectRejectionReason::STATUS_ONLINE], ['reason' => 'ASC']);
        $this->step             = $this->params[0];
        $this->projectId        = $this->params[1];
    }

    public function _changeClient()
    {
        $this->hideDecoration();

        if (false === empty($this->params[0])) {
            /** @var \clients $clients */
            $clients       = $this->loadData('clients');
            $this->search  = urldecode(filter_var($this->params[0], FILTER_SANITIZE_STRING));
            $this->clients = $clients->searchEmprunteurs('OR', $this->search, $this->search, '', '', str_replace(' ', '', $this->search));
        }
    }

    public function _memo()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        if ($this->request->isMethod(\Symfony\Component\HttpFoundation\Request::METHOD_POST)) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            if (
                isset($_POST['projectId'], $_POST['content'])
                && filter_var($_POST['projectId'], FILTER_VALIDATE_INT)
            ) {
                $projectRepository = $entityManager->getRepository(Projects::class);
                $projectEntity     = $projectRepository->find($_POST['projectId']);

                if (null !== $projectEntity) {
                    /** @var \Unilend\Entity\Projects $projectEntity */
                    $projectCommentEntity = new ProjectsComments();
                    $projectCommentEntity->setIdProject($projectEntity);
                    $projectCommentEntity->setContent($_POST['content']);
                    $projectCommentEntity->setPublic(empty($_POST['public']) ? false : true);
                    $projectCommentEntity->setIdUser($this->userEntity);

                    $entityManager->persist($projectCommentEntity);
                    $entityManager->flush($projectCommentEntity);

                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\SlackManager $slackManager */
                    $slackManager      = $this->get('unilend.service.slack_manager');
                    $slackNotification = 'Mémo ajouté par *' . $projectCommentEntity->getIdUser()->getFirstname() . ' ' . $projectCommentEntity->getIdUser()
                            ->getName() . '* sur le projet ' . $slackManager->getProjectName($projectEntity);

                    if (
                        $projectEntity->getIdCommercial()
                        && $projectEntity->getIdCommercial()->getIdUser() > 0
                        && $this->userEntity !== $projectEntity->getIdCommercial()
                        && false === empty($projectEntity->getIdCommercial()->getSlack())
                    ) {
                        $slackManager->sendMessage($slackNotification, '@' . $projectEntity->getIdCommercial()->getSlack());
                    }

                    if (
                        $projectEntity->getIdAnalyste()
                        && $projectEntity->getIdAnalyste()->getIdUser() > 0
                        && $this->userEntity !== $projectEntity->getIdAnalyste()
                        && false === empty($projectEntity->getIdAnalyste()->getSlack())
                    ) {
                        $slackManager->sendMessage($slackNotification, '@' . $projectEntity->getIdAnalyste()->getSlack());
                    }

                    $projectCommentRepository = $entityManager->getRepository(ProjectsComments::class);
                    $this->projectComments    = $projectCommentRepository->findBy(['idProject' => $_POST['projectId']], ['added' => 'DESC']);

                    $this->autoFireView = true;
                    $this->setView('memos');

                    return;
                }
            } elseif (
                isset($_POST['commentId'], $_POST['public'])
                && filter_var($_POST['commentId'], FILTER_VALIDATE_INT)
                && null !== filter_var($_POST['public'], FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE])
            ) {
                $errors                   = null;
                $public                   = (bool) $_POST['public'];
                $projectCommentRepository = $entityManager->getRepository(ProjectsComments::class);
                $projectCommentEntity     = $projectCommentRepository->find($_POST['commentId']);

                if (null === $projectCommentEntity) {
                    $errors = ['Mémo inconnu'];
                } else {
                    if ($projectCommentEntity->getPublic() !== $public) {
                        $projectCommentEntity->setPublic($public);

                        try {
                            $entityManager->flush($projectCommentEntity);
                        } catch (\Doctrine\ORM\OptimisticLockException $exception) {
                            $errors[] = 'Impossible de modifier la visibilité du mémo (' . $exception->getMessage() . ')';
                        }
                    }
                }

                $this->sendAjaxResponse(empty($errors), null, $errors);
            }

            $this->sendAjaxResponse(false, null, ['Action inconnue']);
        }
    }

    public function _add()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\PartnerManager $partnerManager */
        $partnerManager = $this->get('unilend.service.partner_manager');
        /** @var ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');

        /** @var \clients clients */
        $this->clients = $this->loadData('clients');
        /** @var \companies companies */
        $this->companies = $this->loadData('companies');
        /** @var projects projects */
        $this->projects = $this->loadData('projects');
        $defaultPartner = $partnerManager->getDefaultPartner();
        /** @var \Unilend\Repository\PartnerRepository $partnerRepository */
        $partnerRepository = $entityManager->getRepository(Partner::class);
        $this->partnerList = $partnerRepository->getPartnersSortedByName(Partner::STATUS_VALIDATED);

        try {
            if (
                isset($this->params[0], $_POST['id_client'])
                && 'client' === $this->params[0]
                && false !== filter_var($_POST['id_client'], FILTER_VALIDATE_INT)
                && null !== ($clientEntity = $entityManager->getRepository(Clients::class)->find($_POST['id_client']))
            ) {
                if (false === $clientEntity->isBorrower()) {
                    $_SESSION['freeow']['title']   = 'Impossible de créer le projet';
                    $_SESSION['freeow']['message'] = 'Le client selectioné n\'est pas un emprunteur';

                    header('Location: ' . $this->lurl . '/dossiers/add/create');
                    exit;
                }

                $company = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $clientEntity]);
                $project = $projectRequestManager->createProjectByCompany($this->userEntity, $company, $defaultPartner, ProjectsStatus::STATUS_REQUEST);
                $this->users_history->histo(7, 'dossier create', $this->userEntity->getIdUser(), serialize(['id_project' => $project->getIdProject()]));

                header('Location: ' . $this->lurl . '/dossiers/add/' . $project->getIdProject());
                exit;
            } elseif (isset($this->params[0]) && 'nouveau' === $this->params[0]) {
                $project = $projectRequestManager->newProject($this->userEntity, $defaultPartner, ProjectsStatus::STATUS_REQUEST);
                $this->users_history->histo(7, 'dossier create', $this->userEntity->getIdUser(), serialize(['id_project' => $project->getIdProject()]));

                header('Location: ' . $this->lurl . '/dossiers/add/' . $project->getIdProject());
                exit;
            } elseif (
                isset($this->params[0], $this->params[1])
                && 'siren' === $this->params[0]
                && 1 === preg_match('/^[0-9]{9}$/', $this->params[1])
            ) {
                $project = $projectRequestManager->newProject($this->userEntity, $defaultPartner, ProjectsStatus::STATUS_REQUEST, null, $this->params[1]);
                $this->users_history->histo(7, 'dossier create', $this->userEntity->getIdUser(), serialize(['id_project' => $project->getIdProject()]));

                /** @var ProjectRequestManager $projectRequestManager */
                $projectRequestManager = $this->get('unilend.service.project_request_manager');
                $projectRequestManager->checkProjectRisk($project, $this->userEntity->getIdUser());

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->getIdProject());
                exit;
            } elseif (
                isset($this->params[0])
                && false !== filter_var($this->params[0], FILTER_VALIDATE_INT)
                && $this->projects->get($this->params[0])
                && 0 == $this->projects->create_bo
            ) {
                $_SESSION['freeow']['title']   = 'Création de dossier';
                $_SESSION['freeow']['message'] = 'Ce dossier n\'a pas été créé dans le back office';

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                exit;
            }
        } catch (Exception $exception) {
            $this->get('logger')->error('An error occurred while creating project: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);

            $_SESSION['freeow']['title']   = 'Impossible de créer le projet';
            $_SESSION['freeow']['message'] = 'Une error est survenue.';

            header('Location: ' . $this->lurl . '/dossiers/add/create');
            exit;
        }


        if (false === empty($this->projects->id_company)) {
            $this->companies->get($this->projects->id_company);
        }

        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->dureePossible = explode(',', $this->settings->value);

        $this->sources = $this->getSourcesList();
    }

    public function _detail_remb_preteur()
    {
        if (
            empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
        ) {
            header('Location: /dossiers');
            exit;
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $project       = $entityManager->getRepository(Projects::class)->find($this->params[0]);

        if (null === $project) {
            header('Location: /dossiers');
            exit;
        }

        $loanRepository = $entityManager->getRepository(Loans::class);
        $projectStatus  = $entityManager->getRepository(ProjectsStatus::class)->findOneBy(['status' => $project->getStatus()]);
        $companyStatus  = $entityManager->getRepository(CompanyStatusHistory::class)->findOneBy(['idCompany' => $project->getIdCompany()], ['added' => 'DESC'])->getIdStatus();
        $loans          = $loanRepository->getProjectLoans($project);

        $this->render(null, [
            'project'       => $project,
            'projectStatus' => $projectStatus,
            'companyStatus' => $companyStatus,
            'lendersCount'  => $loanRepository->getLenderNumber($project),
            'loans'         => $loans
        ]);
    }

    public function _detail_echeance_preteur()
    {
        if (
            empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
        ) {
            header('Location: /dossiers');
            exit;
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $loan          = $entityManager->getRepository(Loans::class)->find($this->params[0]);

        if (null === $loan) {
            header('Location: /dossiers');
            exit;
        }

        $leftRepayments              = 0;
        $repayments                  = [];
        $lenderCompanyName           = null;
        $earlyRepayment              = null;
        $owedCapital                 = round(bcdiv($loan->getAmount(), 100, 5), 2);
        $projectStatus               = $entityManager->getRepository(ProjectsStatus::class)->findOneBy(['status' => $loan->getProject()->getStatus()]);
        $companyStatus               = $entityManager->getRepository(CompanyStatusHistory::class)->findOneBy(['idCompany' => $loan->getProject()->getIdCompany()], ['added' => 'DESC'])->getIdStatus();
        $lenderCompany               = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $loan->getWallet()->getIdClient()]);
        $repaymentScheduleRepository = $entityManager->getRepository(Echeanciers::class);
        $repaymentEntities           = $repaymentScheduleRepository->findBy(['idLoan' => $loan, 'statusRa' => Echeanciers::IS_NOT_EARLY_REPAID]);
        $operationRepository         = $entityManager->getRepository(Operation::class);

        if ($lenderCompany) {
            $lenderCompanyName = $lenderCompany->getName();
        }

        foreach ($repaymentEntities as $repaymentEntity) {
            if (Echeanciers::STATUS_REPAID !== $repaymentEntity->getStatus()) {
                ++$leftRepayments;
            }

            $owedCapital = bcsub($owedCapital, bcdiv($repaymentEntity->getCapitalRembourse(), 100, 5), 5);

            $taxes = 0;
            if (Echeanciers::STATUS_PENDING !== $repaymentEntity->getStatus()) {
                $taxes = $operationRepository->getTaxAmountByRepaymentScheduleId($repaymentEntity);
            }

            $repayments[] = [
                'sequence'                 => $repaymentEntity->getOrdre(),
                'capital'                  => round(bcdiv($repaymentEntity->getCapital(), 100, 5), 2),
                'repaidCapital'            => round(bcdiv($repaymentEntity->getCapitalRembourse(), 100, 5), 2),
                'interests'                => round(bcdiv($repaymentEntity->getInterets(), 100, 5), 2),
                'repaidInterests'          => round(bcdiv($repaymentEntity->getInteretsRembourses(), 100, 5), 2),
                'taxes'                    => $taxes,
                'theoreticalRepaymentDate' => $repaymentEntity->getDateEcheance(),
                'actualRepaymentDate'      => $repaymentEntity->getDateEcheanceReel(),
                'status'                   => $repaymentEntity->getStatus()
            ];
        }

        if (ProjectsStatus::STATUS_REPAID === $loan->getProject()->getStatus()) {
            $earlyRepaymentAmount = $repaymentScheduleRepository->getEarlyRepaidCapitalByLoan($loan);
            $earlyRepaymentDate   = $repaymentScheduleRepository->findOneBy(['idLoan' => $loan, 'statusRa' => Echeanciers::IS_EARLY_REPAID])->getDateEcheanceReel();
            $earlyRepayment       = [
                'amount'        => $earlyRepaymentAmount,
                'repaymentDate' => $earlyRepaymentDate
            ];
        }

        $this->render(null, [
            'project'           => $loan->getProject(),
            'projectStatus'     => $projectStatus,
            'companyStatus'     => $companyStatus,
            'loan'              => $loan,
            'lenderCompanyName' => $lenderCompanyName,
            'leftRepayments'    => $leftRepayments,
            'owedCapital'       => round($owedCapital, 2),
            'repayments'        => $repayments,
            'earlyRepayment'    => $earlyRepayment
        ]);
    }

    public function _echeancier_emprunteur()
    {
        if (
            empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
        ) {
            header('Location: /dossiers');
            exit;
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $project       = $entityManager->getRepository(Projects::class)->find($this->params[0]);

        if (null === $project) {
            header('Location: /dossiers');
            exit;
        }

        /** @var ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        $owedCapital    = $projectManager->getRemainingAmounts($project)['capital'];
        $earlyRepayment = [];
        $projectStatus  = $entityManager->getRepository(ProjectsStatus::class)->findOneBy(['status' => $project->getStatus()]);
        $companyStatus  = $entityManager->getRepository(CompanyStatusHistory::class)->findOneBy(['idCompany' => $project->getIdCompany()], ['added' => 'DESC'])->getIdStatus();
        $payments       = $entityManager->getRepository(EcheanciersEmprunteur::class)->getDetailedProjectPaymentSchedule($project);
        $payments       = array_map(function ($payment) {
            $payment['borrowerPaymentDate']   = \DateTime::createFromFormat('Y-m-d H:i:s', $payment['borrowerPaymentDate']);
            $payment['lenderRepaymentDate']   = \DateTime::createFromFormat('Y-m-d H:i:s', $payment['lenderRepaymentDate']);
            $payment['borrowerPaymentStatus'] = $this->getBorrowerPaymentStatusLabel($payment);
            $payment['lenderRepaymentStatus'] = $this->getLenderRepaymentStatusLabel($payment);
            return $payment;
        }, $payments);

        if (ProjectsStatus::STATUS_REPAID === $project->getStatus()) {
            $repaymentTask = $entityManager->getRepository(ProjectRepaymentTask::class)->findOneBy(
                ['idProject' => $project, 'type' => ProjectRepaymentTask::TYPE_EARLY, 'status' => ProjectRepaymentTask::STATUS_REPAID],
                ['repayAt' => 'DESC']
            );

            if (null !== $repaymentTask) {
                $earlyRepayment = [
                    'amount'        => round(bcadd(bcadd($repaymentTask->getCapital(), $repaymentTask->getInterest(), 5), $repaymentTask->getCommissionUnilend(), 5), 2),
                    'repaymentDate' => $repaymentTask->getRepayAt()
                ];
            }
        }

        $this->render(null, [
            'project'         => $project,
            'projectStatus'   => $projectStatus,
            'companyStatus'   => $companyStatus,
            'owedCapital'     => $owedCapital,
            'payments'        => $payments,
            'totalInterests'  => array_sum(array_column($payments, 'interests')),
            'totalCommission' => array_sum(array_column($payments, 'commission')),
            'totalVat'        => array_sum(array_column($payments, 'vat')),
            'earlyRepayment'  => $earlyRepayment
        ]);
    }

    /**
     * @param array $payment
     *
     * @return string
     */
    private function getLenderRepaymentStatusLabel(array $payment): string
    {
        if ($payment['payment'] === $payment['paid']) {
            return 'Remboursé';
        } elseif ($payment['paid'] > 0) {
            return 'Partiellement remboursé';
        }

        return 'En cours';
    }

    /**
     * @param array $payment
     *
     * @return string
     */
    private function getBorrowerPaymentStatusLabel(array $payment): string
    {
        switch ($payment['debitStatus']) {
            case Prelevements::STATUS_PENDING:
                return 'A venir';
            case Prelevements::STATUS_SENT:
                return 'Envoyé';
            case Prelevements::STATUS_VALID:
                return 'Validé';
            case Prelevements::STATUS_TERMINATED:
                return 'Terminé';
            case Prelevements::STATUS_TEMPORARILY_BLOCKED:
                return 'Bloqué temporairement';
            default:
                return 'Inconnu';
        }
    }

    /**
     * @param boolean $displayActionButton
     */
    private function loadEarlyRepaymentInformation($displayActionButton)
    {
        $this->earlyRepaymentPossible = true;
        $this->displayActionButton    = $displayActionButton;

        if ($this->projects->status >= ProjectsStatus::STATUS_REPAYMENT) {
            if ($this->projects->status == ProjectsStatus::STATUS_REPAID) {
                $this->message                = '<div style="color:green;">Remboursement anticipé effectué</div>';
                $this->earlyRepaymentPossible = false;

                return;
            }
            /** @var \echeanciers $repaymentSchedule */
            $repaymentSchedule = $this->loadData('echeanciers');
            $lateRepayment     = $repaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND status = ' . Echeanciers::STATUS_PENDING . ' AND DATE(date_echeance) <= "' . (new \DateTime())->format('Y-m-d') . '"',
                ' ordre ASC', 0, 1);

            if (false === empty($lateRepayment)) {
                $this->message                = '<div style="color:red;">Remboursement impossible. Toutes les échéances précédentes ne sont pas remboursées</div>';
                $this->earlyRepaymentPossible = false;

                return;
            }
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var WorkingDaysManager $workingDaysManager */
            $workingDaysManager = $this->get(WorkingDaysManager::class);

            $nextRepayment = $repaymentSchedule->select(
                'id_project = ' . $this->projects->id_project
                . ' AND status = ' . Echeanciers::STATUS_PENDING
                . ' AND date_echeance >= "' . $workingDaysManager->getNextWorkingDay(new \DateTime('today midnight'), 5)->format('Y-m-d H:i:s') . '"', ' ordre ASC', 0, 1
            );

            if (false === empty($nextRepayment)) {
                $this->earlyRepaymentLimitDate    = $workingDaysManager->getPreviousWorkingDay(\DateTime::createFromFormat('Y-m-d H:i:s', $nextRepayment[0]['date_echeance']), 5);
                $this->nextScheduledRepaymentDate = \DateTime::createFromFormat('Y-m-d H:i:s', $nextRepayment[0]['date_echeance']);
                $this->lenderOwedCapital          = $repaymentSchedule->getRemainingCapitalAtDue($this->projects->id_project, $nextRepayment[0]['ordre'] + 1);
                $this->borrowerOwedCapital        = $entityManager->getRepository(EcheanciersEmprunteur::class)
                    ->getRemainingCapitalFrom($this->projects->id_project, $nextRepayment[0]['ordre'] + 1);

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
                /** @var \Unilend\Repository\ReceptionsRepository $receptionRepository */
                $receptionRepository = $entityManager->getRepository(Receptions::class);
                $this->reception     = $receptionRepository->getBorrowerAnticipatedRepaymentWireTransfer(
                    $entityManager->getRepository(Projects::class)
                        ->find($this->projects->id_project)
                );

                if (1 === count($this->reception)) {
                    /** @var \Unilend\Entity\Receptions reception */
                    $this->reception   = $this->reception[0];
                    $lastPaidRepayment = $repaymentSchedule->select('id_project = ' . $this->projects->id_project . ' AND status = ' . Echeanciers::STATUS_REPAID, ' ordre DESC', 0, 1);

                    $currentLenderOwedCapital   = $repaymentSchedule->getRemainingCapitalAtDue($this->projects->id_project, $lastPaidRepayment[0]['ordre'] + 1);
                    $currentBorrowerOwedCapital = $entityManager->getRepository(EcheanciersEmprunteur::class)
                        ->getRemainingCapitalFrom($this->projects->id_project, $lastPaidRepayment[0]['ordre'] + 1);

                    $projectRepaymentTask = $entityManager->getRepository(ProjectRepaymentTask::class)->findOneBy([
                        'idProject' => $this->projects->id_project,
                        'type'      => ProjectRepaymentTask::TYPE_EARLY,
                        'status'    => ProjectRepaymentTask::STATUS_READY
                    ]);

                    if ($projectRepaymentTask) {
                        $this->wireTransferAmountOk = true;
                        $this->message              = '<div style="color:green;">Virement reçu conforme - Le remboursement a été planifié.</div>';
                        $this->displayActionButton  = false;
                    } elseif (0 === bccomp($currentLenderOwedCapital, $currentBorrowerOwedCapital, 2) && (bcdiv($this->reception->getMontant(), 100, 2)) >= $currentLenderOwedCapital) {
                        $this->wireTransferAmountOk = true;
                        $this->message              = '<div style="color:green;">Virement reçu conforme</div>';
                    } elseif (0 === bccomp($this->lenderOwedCapital, $this->borrowerOwedCapital, 2) && (bcdiv($this->reception->getMontant(), 100, 2)) >= $this->lenderOwedCapital) {
                        $this->wireTransferAmountOk = true;
                        $this->message              = '<div style="color:green;">Virement reçu conforme - Attente du remboursement de l\'échéance du ' . \DateTime::createFromFormat('Y-m-d H:i:s',
                                $nextRepayment[0]['date_echeance'])->format('d/m/Y') . '</div>';
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

    public function _send_cgv_ajax()
    {
        $this->hideDecoration();

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $projectRepository = $entityManager->getRepository(Projects::class);

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

    public function _status()
    {
        if (false === empty($this->request->query->all())) {
            $url = '/dossiers/status/' . $this->request->query->getInt('status');

            $fistRangeStart   = $this->request->query->get('first-range-start');
            $fistRangeEnd     = $this->request->query->get('first-range-end');
            $secondRangeStart = $this->request->query->get('second-range-start');
            $secondRangeEnd   = $this->request->query->get('second-range-end');

            if (1 === preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $fistRangeStart, $matches)
                && checkdate($matches[2], $matches[1], $matches[3])
                && 1 === preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $fistRangeEnd, $matches)
                && checkdate($matches[2], $matches[1], $matches[3])
            ) {
                $start = DateTime::createFromFormat('d/m/Y', $fistRangeStart);
                $end   = DateTime::createFromFormat('d/m/Y', $fistRangeEnd);
                $url   .= '/' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d');

                if (1 === preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $secondRangeStart, $matches)
                    && checkdate($matches[2], $matches[1], $matches[3])
                    && 1 === preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $secondRangeEnd, $matches)
                    && checkdate($matches[2], $matches[1], $matches[3])
                ) {
                    $start = DateTime::createFromFormat('d/m/Y', $secondRangeStart);
                    $end   = DateTime::createFromFormat('d/m/Y', $secondRangeEnd);
                    $url   .= '/' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d');
                }
            }

            header('Location: ' . $url);
            exit;
        }

        $this->loadJs('admin/vis/vis.min');
        $this->loadCss('../scripts/admin/vis/vis.min');

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $projectStatusRepository = $entityManager->getRepository(ProjectsStatus::class);
        $this->statuses          = $projectStatusRepository->findBy([], ['status' => 'ASC']);

        if (false === empty($this->params[0]) && false === empty($this->params[1])
            && 1 === preg_match('/(([0-9]{4})-([0-9]{2})-([0-9]{2}))_(([0-9]{4})-([0-9]{2})-([0-9]{2}))/', $this->params[1], $matches)
            && checkdate($matches[3], $matches[4], $matches[2]) && checkdate($matches[7], $matches[8], $matches[6])
        ) {
            $this->baseStatus      = (int) $this->params[0];
            $this->firstRangeStart = new \DateTime($matches[1]);
            $this->firstRangeEnd   = new \DateTime($matches[5]);
            $today                 = new \DateTime('NOW');
            $projectStatus         = $projectStatusRepository->findOneBy(['status' => $this->baseStatus]);
            if (
                $projectStatus
                && $this->firstRangeStart <= $today
                && $this->firstRangeEnd <= $today
                && $this->firstRangeStart <= $this->firstRangeEnd
            ) {
                $projectStatusHistoryRepository = $entityManager->getRepository(ProjectsStatusHistory::class);
                $baseStatus                     = $projectStatusHistoryRepository->getStatusByDates($this->baseStatus, $this->firstRangeStart, $this->firstRangeEnd);

                if (false === empty($baseStatus)) {
                    $this->history = [
                        'label'    => $projectStatus->getLabel(),
                        'count'    => count($baseStatus),
                        'status'   => $projectStatus->getStatus(),
                        'children' => $this->getStatusChildren(array_column($baseStatus, 'idProjectStatusHistory'))
                    ];

                    foreach ($this->history['children'] as $childStatus => &$child) {
                        if ($childStatus > 0) {
                            $this->history['children'][$childStatus]['children'] = $this->getStatusChildren($child['id_project_status_history']);
                        }
                    }

                    if (false === empty($this->params[2])
                        && 1 === preg_match('/(([0-9]{4})-([0-9]{2})-([0-9]{2}))_(([0-9]{4})-([0-9]{2})-([0-9]{2}))/', $this->params[2], $matches)
                        && checkdate($matches[3], $matches[4], $matches[2]) && checkdate($matches[7], $matches[8], $matches[6])
                    ) {
                        $this->secondRangeStart = new \DateTime($matches[1]);
                        $this->secondRangeEnd   = new \DateTime($matches[5]);

                        if (
                            $this->secondRangeStart <= $today
                            && $this->secondRangeEnd <= $today
                            && $this->secondRangeStart <= $this->secondRangeEnd
                        ) {
                            $baseStatus           = $projectStatusHistoryRepository->getStatusByDates($this->baseStatus, $this->secondRangeStart, $this->secondRangeEnd);
                            $this->compareHistory = [
                                'label'    => $projectStatus->getLabel(),
                                'count'    => count($baseStatus),
                                'status'   => $projectStatus->getStatus(),
                                'children' => $this->getStatusChildren(array_column($baseStatus, 'idProjectStatusHistory'))
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
                $status[$aElement['status']]['total_days']                  += $aElement['diff_days'];
                $status[$aElement['status']]['id_project_status_history'][] = $aElement['id_project_status_history'];

                if ($aElement['added'] > $status[$aElement['status']]['max_date']) {
                    $status[$aElement['status']]['max_date'] = $aElement['added'];
                }
            }
        }, $childrenStatus);

        uasort($status, function ($aFirstElement, $aSecondElement) {
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

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');

        if (isset($this->params[1]) && 'resume' === $this->params[1] && ProjectsStatus::STATUS_REVIEW == $this->projects->status) {
            $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::STATUS_REQUEST, $this->projects);

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
        } elseif (false === empty($_POST['comment'])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager        = $this->get('doctrine.orm.entity_manager');
            $projectCommentEntity = new ProjectsComments();
            $projectCommentEntity->setIdProject($entityManager->getRepository(Projects::class)->find($this->projects->id_project));
            $projectCommentEntity->setIdUser($this->userEntity);
            $projectCommentEntity->setContent('<p><u>Report projet</u></p>' . $_POST['comment']);
            $projectCommentEntity->setPublic(empty($_POST['public']) ? false : true);

            $entityManager->persist($projectCommentEntity);
            $entityManager->flush($projectCommentEntity);

            if ($this->projects->status != ProjectsStatus::STATUS_REVIEW) {
                $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::STATUS_REVIEW, $this->projects);
            }

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
        }
    }

    public function _abandon()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (
            false === isset($this->params[0])
            || null === $project = $entityManager->getRepository(Projects::class)->find($this->params[0])
        ) {
            $_SESSION['freeow']['title']   = 'Erreur abandon projet';
            $_SESSION['freeow']['message'] = 'Votre demande est incorrecte.';
            header('Location: ' . $this->lurl);
            die;
        }

        if (false === empty($_POST['reason'])) {
            /** @var ProjectAbandonReason[] $abandonReasons */
            $abandonReasons = $entityManager->getRepository(ProjectAbandonReason::class)
                ->findBy(['idAbandon' => $_POST['reason']]);

            if (empty($abandonReasons) || count($abandonReasons) !== count($_POST['reason'])) {
                $this->get('logger')->error('Could not abandon project: ' . $this->params[0] . '. At least one abandon reasons is unknown.', [
                    'id_project'      => $this->params[0],
                    'abandon_reasons' => $_POST['reason'],
                    'class'           => __CLASS__,
                    'function'        => __FUNCTION__
                ]);

                $_SESSION['freeow']['title']   = 'Erreur abandon projet';
                $_SESSION['freeow']['message'] = 'Le motif est vide et/ou inconnu.';
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->getIdProject());
                die;
            }
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
            $projectStatusManager = $this->get('unilend.service.project_status_manager');
            $result               = $projectStatusManager->abandonProject($project, $abandonReasons, $this->userEntity);

            if (false === $result) {
                $_SESSION['freeow']['title']   = 'Erreur abandon projet';
                $_SESSION['freeow']['message'] = 'Veuillez contacter l\'équipe technique.';
                header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->getIdProject());
                die;
            }

            if (false === empty($_POST['comment'])) {
                $projectCommentEntity = new ProjectsComments();

                $projectCommentEntity
                    ->setIdProject($project)
                    ->setIdUser($this->userEntity)
                    ->setContent('<p><u>Abandon projet</u></p>' . $_POST['comment'])
                    ->setPublic(empty($_POST['public']) ? false : true);

                $entityManager->persist($projectCommentEntity);
                try {
                    $entityManager->flush($projectCommentEntity);
                } catch (\Exception $exception) {
                    $this->get('logger')->error('Could not insert project abandon comment. Error: ' . $exception->getMessage(), [
                        'id_project' => $project->getIdProject(),
                        'comment'    => $_POST['comment'],
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine()
                    ]);

                    $_SESSION['freeow']['title']   = 'Erreur abandon projet';
                    $_SESSION['freeow']['message'] = 'Le projet est abandonné, mais le commentaire n\'a pas été inséré.';
                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->getIdProject());
                    die;
                }
            }
            $_SESSION['freeow']['title']   = 'Abandon du projet';
            $_SESSION['freeow']['message'] = 'Le projet a été abandonné avec succès';
            header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->getIdProject());
            die;
        }

        $_SESSION['freeow']['title']   = 'Abandon du projet';
        $_SESSION['freeow']['message'] = 'Aucune modification n\'a été faite.';
        header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->getIdProject());
        die;
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
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $companyMainAddress = $entityManager->getRepository(CompanyAddress::class)->findLastModifiedNotArchivedAddressByType($this->projects->id_company, AddressType::TYPE_MAIN_ADDRESS);

        if (null === $companyMainAddress) {
            $_SESSION['publish_error'] = 'L\'entreprise n\'a pas d\'adresse principale';

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
        }

        if (null === $entityManager->getRepository(CompanyBeneficialOwnerDeclaration::class)->findCurrentDeclarationByCompany($this->projects->id_company)) {
            $_SESSION['publish_error'] = 'Il n\'y a pas de bénéficiaire effectif déclaré';

            header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
            die;
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
                $_SESSION['publish_error'] = 'La date de publication du dossier doit être au minimum dans 5 minutes et la date de retrait dans plus d\'une heure';

                header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->projects->id_project);
                die;
            }

            $this->projects->date_publication = $publicationDate->format('Y-m-d H:i:s');
            $this->projects->date_retrait     = $endOfPublicationDate->format('Y-m-d H:i:s');
            $this->projects->update();

            $_SESSION['freeow']['title']   = 'Mise en ligne';
            $_SESSION['freeow']['message'] = 'Mise en ligne programmée avec succès';

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
            $projectStatusManager = $this->get('unilend.service.project_status_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRatingManager $projectRatingManager */
            $projectRatingManager = $this->get('unilend.service.project_rating_manager');
            $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::STATUS_REVIEW, $this->projects);

            $slackManager    = $this->container->get('unilend.service.slack_manager');
            $publicationDate = new \DateTime($this->projects->date_publication);
            $project         = $entityManager->getRepository(Projects::class)->find($this->projects->id_project);
            $star            = str_replace('.', ',', $projectRatingManager->getRating($project));
            $message         = $slackManager->getProjectName($project) . ' sera mis en ligne le *' . $publicationDate->format('d/m/Y à H:i') . '* - ' . $this->projects->period . ' mois :calendar: / ' . $this->ficelle->formatNumber($this->projects->amount,
                    0) . ' € :moneybag: / ' . $star . ' :star:';

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
            $projectCommentEntity->setIdProject($entityManager->getRepository(Projects::class)->find($this->projects->id_project));
            $projectCommentEntity->setIdUser($this->userEntity);
            $projectCommentEntity->setContent('<p><u>Retour à l\'analyse</u><p>' . $_POST['comment'] . '</p>');

            $entityManager->persist($projectCommentEntity);
            $entityManager->flush($projectCommentEntity);

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
            $projectStatusManager = $this->get('unilend.service.project_status_manager');
            $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::STATUS_REQUEST, $this->projects);

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
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var Projects $project */
        $project = $entityManager->getRepository(Projects::class)->find($this->params[0]);

        if (null === $project || null === $project->getIdCompany() || null === $project->getIdCompany()->getIdClientOwner()) {
            header('Location: ' . $this->lurl);
            die;
        }
        $client = $project->getIdCompany()->getIdClientOwner();

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');
        try {
            /** @var ProjectRejectionReason[] $rejectionReason */
            $rejectionReason = $entityManager->getRepository(ProjectRejectionReason::class)
                ->findBy(['label' => ProjectRejectionReason::SUSPENSIVE_CONDITIONS]);
            $result          = $projectStatusManager->rejectProject($project, ProjectsStatus::STATUS_CANCELLED, $rejectionReason, $this->userEntity);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Could not save the project status: ' . ProjectsStatus::STATUS_CANCELLED . '. Error: ' . $exception->getMessage(), [
                'id_project'       => $project->getIdProject(),
                'rejection_reason' => ProjectRejectionReason::SUSPENSIVE_CONDITIONS,
                'class'            => __CLASS__,
                'function'         => __FUNCTION__,
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine()
            ]);
            header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->getIdProject());
            die;
        }

        if ($result && false === empty($client->getEmail())) {
            $keywords = [
                'firstName' => $client->getPrenom()
            ];

            /** @var \Unilend\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-dossier-rejete', $keywords);
            try {
                $message->setTo($client->getEmail());
                $mailer = $this->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->get('logger')->warning('Could not send email: emprunteur-dossier-rejete - Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $client->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__
                ]);
            }
        }

        header('Location: ' . $this->lurl . '/dossiers/edit/' . $project->getIdProject());
        die;
    }

    public function _remove_suspensive_conditions()
    {
        $this->projects = $this->loadData('projects');
        $this->projects->get($this->params[0]);

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');
        $projectStatusManager->addProjectStatus($this->userEntity, ProjectsStatus::STATUS_REVIEW, $this->projects);

        /** @var \companies $company */
        $company = $this->loadData('companies');
        $company->get($this->projects->id_company);

        /** @var \clients $client */
        $client = $this->loadData('clients');
        $client->get($company->id_client_owner);

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
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyManager $companyManager */
                    $companyManager = $this->get('unilend.service.company_manager');
                    $siren          = filter_var($this->request->request->get('siren'), FILTER_SANITIZE_NUMBER_INT);
                    $company        = $companyManager->createBorrowerCompany($this->userEntity, $siren);

                    $this->projects->id_target_company = $company->getIdCompany();
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
                    if ($this->isTakeover() && false === empty($this->projects->id_target_company) && $this->projects->status < ProjectsStatus::STATUS_REVIEW) {
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
        $company       = $entityManager->getRepository(Companies::class)->find($this->projects->id_target_company);

        /** @var ProjectRequestManager $projectRequestManager */
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
            $aNames     = $oCompanies->searchByName($sTerm);
        }

        echo json_encode($aNames);
    }

    /**
     * @param array  $codes
     * @param string $formType
     * @param string $extraClass
     *
     * @return string
     */
    protected function generateBalanceLineHtml(array $codes, $formType, $extraClass = '')
    {
        $html = '';
        foreach ($codes as $code) {
            $index = array_search($code, array_column($this->allTaxFormTypes[$formType], 'code'));
            $field = $this->allTaxFormTypes[$formType][$index];

            $html                    .= '<tr class="' . $extraClass . '"> <td>' . $field['label'] . '</td> <td width="45">' . $field['code'] . '</td>';
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
                        $html          .= '<td>' . $movement . '</td>';

                    }
                    $formatedValue = $this->ficelle->formatNumber($value, 0);
                    $tabIndex      = 420 + $iColumn;
                    $html          .= '<td><input type="text" class="numbers" name="box[' . $iBalanceSheetId . '][' . $field['code'] . ']" value="' . $formatedValue . '" tabindex="' . $tabIndex . '"/>&nbsp;€</td>';

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
     *
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
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (
            isset($this->params[0], $this->params[1])
            && ($project = $entityManager->getRepository(Projects::class)->find($this->params[0]))
            && ($partner = $entityManager->getRepository(Partner::class)->find($this->params[1]))
        ) {
            $project->setIdPartner($partner);

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

        if (false === empty($this->params[0]) && false === empty($this->params[1])) {
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
            $this->currencyFormatter = $this->get('currency_formatter');

            $this->companyRepository = $entityManager->getRepository(Companies::class);
            $this->hasOverdue        = false;

            if (WireTransferOutManager::TRANSFER_OUT_BY_PROJECT === $this->params[0]) {
                $this->project       = $entityManager->getRepository(Projects::class)->find($this->params[1]);
                $this->company       = $this->project->getIdCompany();
                $this->borrowerMotif = $borrowerManager->getProjectBankTransferLabel($this->project);
                $this->restFunds     = $projectManager->getRestOfFundsToRelease($this->project, true);
            } else {
                $this->project       = null;
                $this->company       = $entityManager->getRepository(Companies::class)->find($this->params[1]);
                $this->borrowerMotif = $borrowerManager->getCompanyBankTransferLabel($this->company);
                $wallet              = $entityManager->getRepository(Wallet::class)->getWalletByType($this->company->getIdClientOwner(), WalletType::BORROWER);
                $this->restFunds     = $borrowerManager->getRestOfFundsToRelease($wallet);

                $this->projects = $entityManager->getRepository(Projects::class)->findBy(['idCompany' => $this->company]);
                foreach ($this->projects as $project) {
                    $overDueAmounts = $projectManager->getOverdueAmounts($project);
                    if ($overDueAmounts['capital'] > 0 || $overDueAmounts['interest'] > 0 || $overDueAmounts['commission'] > 0) {
                        $this->hasOverdue = true;
                        break;
                    }
                }
                if (1 === count($this->projects)) {
                    $this->project = current($this->projects);
                }
            }
            $client             = $this->company->getIdClientOwner();
            $this->bankAccounts = [$entityManager->getRepository(BankAccount::class)->getClientValidatedBankAccount($client)];
            if (WireTransferOutManager::TRANSFER_OUT_BY_PROJECT === $this->params[0]) {
                $this->bankAccounts = array_merge($this->bankAccounts, $partnerManager->getPartnerThirdPartyBankAccounts($this->project->getIdPartner()));
            }

            if ($this->request->isMethod('POST')) {
                if ($this->request->request->get('date')) {
                    $date = DateTime::createFromFormat('d/m/Y', $this->request->request->get('date'));
                } else {
                    $date = null;
                }

                if (null !== $date && $date <= new DateTime()) {
                    $_SESSION['freeow']['title']   = 'Transfert de fonds';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé. La date de transfert n\'est pas valide.';
                    header('Location: ' . $this->request->server->get('HTTP_REFERER'));
                    die;
                }

                $amount = $this->loadLib('ficelle')->cleanFormatedNumber($this->request->request->get('amount'));
                if ($amount <= 0) {
                    $_SESSION['freeow']['title']   = 'Transfert de fonds';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé. Montant n\'est pas valide.';
                    header('Location: ' . $this->lurl . '/dossiers/edit/' . $this->params[0]);
                    die;
                }

                if ($amount > $this->restFunds) {
                    $_SESSION['freeow']['title']   = 'Transfert de fonds';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé. Montant trop élévé.';
                    header('Location: ' . $this->request->server->get('HTTP_REFERER'));
                    die;
                }

                if (empty($this->project)) {
                    $this->project = $entityManager->getRepository(Projects::class)->find($this->request->request->getInt('project'));
                    if (null === $this->project) {
                        $_SESSION['freeow']['title']   = 'Transfert de fonds';
                        $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé. Projet non validé.';
                        header('Location: ' . $this->request->server->get('HTTP_REFERER'));
                        die;
                    }
                }

                $bankAccount = $entityManager->getRepository(BankAccount::class)->find($this->request->request->get('bank_account'));
                $wallet      = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::BORROWER);

                try {
                    $wireTransferOutManager->createTransfer($wallet, $amount, $bankAccount, $this->project, $this->userEntity, $date, $this->request->request->get('pattern'));
                } catch (\Exception $exception) {
                    $this->get('logger')->error($exception->getMessage(), ['methode' => __METHOD__]);
                    $_SESSION['freeow']['title']   = 'Transfert de fonds échoué';
                    $_SESSION['freeow']['message'] = 'Le transfert de fonds n\'a pas été créé';

                    header('Location: ' . $this->request->server->get('HTTP_REFERER'));
                    die;
                }

                $_SESSION['freeow']['title']   = 'Transfert de fonds';
                $_SESSION['freeow']['message'] = 'Le transfert de fonds a été créé avec succès ';
                header('Location: ' . $this->request->server->get('HTTP_REFERER'));
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

            $this->wireTransferOut       = $entityManager->getRepository(Virements::class)->find($this->params[0]);
            $this->bankAccountRepository = $entityManager->getRepository(BankAccount::class);
            $this->companyRepository     = $entityManager->getRepository(Companies::class);
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

            header('Location: ' . $this->request->server->get('HTTP_REFERER'));
            die;
        }
    }

    public function _projets_avec_retard()
    {
        /** @var BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if (
            $userManager->isGrantedRisk($this->userEntity)
            || (isset($this->params[0]) && 'risk' === $this->params[0] && $userManager->isUserGroupIT($this->userEntity))
        ) {
            $this->menu_admin = 'remboursement';
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\CompanyManager $companyManager */
            $companyManager = $this->get('unilend.service.company_manager');

            $projectsRepository        = $entityManager->getRepository(Projects::class);
            $receptionsRepository      = $entityManager->getRepository(Receptions::class);
            $paymentScheduleRepository = $entityManager->getRepository(EcheanciersEmprunteur::class);

            $totalPendingWireTransferInAmount = 0;
            $totalOverdueAmount               = 0;
            $projectsWithDebtCollection       = 0;
            $projectData                      = [];

            foreach ($projectsRepository->getProjectsWithLateRepayments() as $lateRepayment) {
                $project        = $projectsRepository->find($lateRepayment['idProject']);
                $overdueAmounts = $projectManager->getOverdueAmounts($project);

                $overdueAmount        = round(bcadd(bcadd($overdueAmounts['capital'], $overdueAmounts['interest'], 4), $overdueAmounts['commission'], 4), 2);
                $debtCollectionAmount = 0;

                /** @var \Unilend\Entity\DebtCollectionMission $mission */
                foreach ($project->getDebtCollectionMissions() as $mission) {
                    $entrustedAmount      = round(bcadd(bcadd($mission->getCapital(), $mission->getInterest(), 4), $mission->getCommissionVatIncl(), 4), 2);
                    $debtCollectionAmount = round(bcadd($debtCollectionAmount, $entrustedAmount, 4), 2);
                }

                if ($project->getDebtCollectionMissions()->count() > 0) {
                    $projectsWithDebtCollection++;
                }

                $pendingWireTransferInAmount           = $receptionsRepository->getTotalPendingWireTransferIn($project);
                $projectData[$project->getIdProject()] = [
                    'projectId'                   => $project->getIdProject(),
                    'siren'                       => $project->getIdCompany()->getSiren(),
                    'companyStatusLabel'          => $companyManager->getCompanyStatusNameByLabel($project->getIdCompany()->getIdStatus()->getLabel()),
                    'projectTitle'                => $project->getTitle(),
                    'projectStatusLabel'          => $lateRepayment['projectStatusLabel'],
                    'projectStatus'               => $project->getStatus(),
                    'overdueAmount'               => $overdueAmount,
                    'entrustedToDebtCollector'    => $debtCollectionAmount,
                    'pendingWireTransferInAmount' => $pendingWireTransferInAmount,
                    'closeOutNettingDate'         => $project->getCloseOutNettingDate(),
                    'overduePaymentScheduleCount' => $paymentScheduleRepository->getOverdueScheduleCount($project)
                ];
                $totalOverdueAmount                    = round(bcadd($totalOverdueAmount, $overdueAmount, 4), 2);
                $totalPendingWireTransferInAmount      = round(bcadd($totalPendingWireTransferInAmount, $projectData[$project->getIdProject()]['pendingWireTransferInAmount'], 4), 2);
            }
            $this->render(null, [
                'totalOverdueAmountToCollect'  => $totalOverdueAmount,
                'pendingWireTransferInAmount'  => $totalPendingWireTransferInAmount,
                'nbProjectsWithDebtCollection' => $projectsWithDebtCollection,
                'nbProjectsWithLateRepayments' => count($projectData) - $projectsWithDebtCollection,
                'projectWithPaymentProblems'   => $projectData
            ]);
        } else {
            header('Location: ' . $this->lurl . '/dossiers');
            die;
        }
    }

    /**
     * @param projects_notes $projectNotes
     *
     * @return array
     */
    private function addUnilendPrescoring(\projects_notes $projectNotes)
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $projectNotes->added);

        return [
            'value'  => $projectNotes->pre_scoring,
            'date'   => $date instanceof \DateTime ? $date->format('d/m/Y H:i') : '',
            'action' => 'Test d&#39éligibilité',
            'user'   => ''
        ];
    }

    /**
     * @return array
     */
    private function getSourcesList(): array
    {
        return [
            'Commercial Courtier',
            'Commercial Direct',
            'Franchise',
            'Test'
        ];
    }
}
