<?php

use Unilend\librairies\Altares;
use Psr\Log\LoggerInterface;

class depot_de_dossierController extends bootstrap
{
    const PAGE_NAME_STEP_2   = 'etape2';
    const PAGE_NAME_STEP_3   = 'etape3';
    const PAGE_NAME_FILES    = 'fichiers';
    const PAGE_NAME_PROSPECT = 'prospect';
    const PAGE_NAME_END      = 'fin';
    const PAGE_NAME_EMAILS   = 'emails';
    const PAGE_NAME_PARTNER  = 'partenaire';

    /**
     * @var attachment_helper
     */
    private $attachmentHelper;

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->companies                     = $this->loadData('companies');
        $this->companies_bilans              = $this->loadData('companies_bilans');
        $this->companies_actif_passif        = $this->loadData('companies_actif_passif');
        $this->projects                      = $this->loadData('projects');
        $this->projects_status               = $this->loadData('projects_status');
        $this->projects_status_history       = $this->loadData('projects_status_history');
        $this->clients                       = $this->loadData('clients');
        $this->clients_adresses              = $this->loadData('clients_adresses');
        $this->prescripteurs                 = $this->loadData('prescripteurs');
        $this->clients_prescripteur          = $this->loadData('clients');
        $this->clients_adresses_prescripteur = $this->loadData('clients_adresses');
        $this->companies_prescripteur        = $this->loadData('companies');
        $this->attachment                    = $this->loadData('attachment');
        $this->attachment_type               = $this->loadData('attachment_type');

        $this->navigateurActive = 3;

        $this->lng['depot-de-dossier-header'] = $this->ln->selectFront('depot-de-dossier-header', $this->language, $this->App);
        $this->lng['depot-de-dossier']        = $this->ln->selectFront('depot-de-dossier', $this->language, $this->App);
        $this->lng['etape2']                  = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);
        $this->lng['etape3']                  = $this->ln->selectFront('depot-de-dossier-etape-3', $this->language, $this->App);
    }

    public function _default()
    {
        $this->checkProjectHash('default');
    }

    public function _reprise()
    {
        $this->checkProjectHash('standby');
    }

    public function _stand_by()
    {
        $this->checkProjectHash('standby');
    }

    /**
     * @todo create/update methods may be called several times on some objects, optimize it
     */
    public function _etape1()
    {
        $this->page = 'depot_dossier_1';

        if (false === isset($_SESSION['forms']['depot-de-dossier']['values'])) {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
            die;
        }

        $this->lng['landing-page'] = $this->ln->selectFront('landing-page', $this->language, $this->App);

        $iAmount = $_SESSION['forms']['depot-de-dossier']['values']['montant'];
        $iSIREN  = $_SESSION['forms']['depot-de-dossier']['values']['siren'];

        $_SESSION['forms']['depot-de-dossier']['email'] = isset($_SESSION['forms']['depot-de-dossier']['values']['email']) && $this->ficelle->isEmail($_SESSION['forms']['depot-de-dossier']['values']['email']) ? $_SESSION['forms']['depot-de-dossier']['values']['email'] : '';

        unset($_SESSION['forms']['depot-de-dossier']['values']);

        if (isset($_SESSION['client'])) {
            $this->clients->handleLogout(false);
        }

        $this->clients->id_langue      = $this->language;

        $this->setSource($this->clients);

        if (empty($_SESSION['forms']['depot-de-dossier']['email']) || true === $this->clients->existEmail($_SESSION['forms']['depot-de-dossier']['email'])) { // Email does not exist in DB
            $this->clients->email = $_SESSION['forms']['depot-de-dossier']['email'];
        } else {
            $this->clients->email = $_SESSION['forms']['depot-de-dossier']['email'] . '-' . time();
        }

        $this->clients->create();

        if (false === is_numeric($this->clients->id_client) || $this->clients->id_client < 1) {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
            die;
        }
        $this->clients_adresses->id_client = $this->clients->id_client;
        $this->clients_adresses->create();

        $this->companies->id_client_owner               = $this->clients->id_client;
        $this->companies->siren                         = $iSIREN;
        $this->companies->status_adresse_correspondance = '1';
        $this->companies->email_dirigeant               = $_SESSION['forms']['depot-de-dossier']['email'];
        $this->companies->create();

        $this->projects->id_company                           = $this->companies->id_company;
        $this->projects->amount                               = $iAmount;
        $this->projects->ca_declara_client                    = 0;
        $this->projects->resultat_exploitation_declara_client = 0;
        $this->projects->fonds_propres_declara_client         = 0;
        $this->projects->create();

        $this->settings->get('Altares email alertes', 'type');
        $sAlertEmail = $this->settings->value;

        /** @var LoggerInterface $oLogger */
        $oLogger = $this->get('logger');

        try {
            $oAltares = new Altares();
            $oResult  = $oAltares->getEligibility($iSIREN);
        } catch (\Exception $oException) {
            $oLogger->error('Calling Altares::getEligibility() using SIREN ' . $iSIREN . ' - Exception message: ' . $oException->getMessage(), array('class' => __CLASS__, 'function' => __FUNCTION__, 'siren' => $iSIREN));

            mail($sAlertEmail, '[ALERTE] ERREUR ALTARES 2', 'Date ' . date('Y-m-d H:i:s') . '' . $oException->getMessage());
            $this->redirect(self::PAGE_NAME_STEP_2, \projects_status::COMPLETUDE_ETAPE_2);
        }

        if (false === empty($oResult->exception)) {
            $oLogger->error('Altares error code: ' . $oResult->exception->code . ' - Altares error description: ' . $oResult->exception->description . ' - Altares error: ' . $oResult->exception->erreur, array('class' => __CLASS__, 'function' => __FUNCTION__));

            mail($sAlertEmail, '[ALERTE] ERREUR ALTARES 1', 'Date ' . date('Y-m-d H:i:s') . 'SIREN : ' . $iSIREN . ' | ' . $oResult->exception->code . ' | ' . $oResult->exception->description . ' | ' . $oResult->exception->erreur);
            $this->redirect(self::PAGE_NAME_STEP_2, \projects_status::COMPLETUDE_ETAPE_2);
        }

        $this->projects->retour_altares = $oResult->myInfo->codeRetour;

        $oAltares->setCompanyData($this->companies, $oResult->myInfo);

        switch ($oResult->myInfo->eligibility) {
            case 'Oui':
                $oAltares->setProjectData($this->projects, $oResult->myInfo);
                $oAltares->setCompanyBalance($this->companies);

                $oCompanyCreationDate = new \DateTime($this->companies->date_creation);
                $oInterval            = $oCompanyCreationDate->diff(new \DateTime());

                $aAnnualAccounts = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'cloture_exercice_fiscal DESC', 0, 1);

                $this->projects->id_dernier_bilan = $aAnnualAccounts[0]['id_bilan'];
                $this->projects->update();

                if ($oInterval->days < \projects::MINIMUM_CREATION_DAYS_PROSPECT) {
                    $this->redirect(self::PAGE_NAME_PROSPECT, \projects_status::PAS_3_BILANS);
                }

                $this->redirect(self::PAGE_NAME_STEP_2, \projects_status::COMPLETUDE_ETAPE_2);
                break;
            case 'Non':
            default:
                $this->projects->update();

                if (in_array($oResult->myInfo->codeRetour, array(Altares::RESPONSE_CODE_NEGATIVE_CAPITAL_STOCK, Altares::RESPONSE_CODE_NEGATIVE_RAW_OPERATING_INCOMES))) {
                    $this->redirect(self::PAGE_NAME_PROSPECT, \projects_status::NOTE_EXTERNE_FAIBLE, $oResult->myInfo->motif);
                }

                $this->redirect(self::PAGE_NAME_END, \projects_status::NOTE_EXTERNE_FAIBLE, $oResult->myInfo->motif);
                break;
        }
    }

    public function _etape2()
    {
        $this->page = 'depot_dossier_2';

        $this->bDisplayTouchvibes = true;

        $this->checkProjectHash(self::PAGE_NAME_STEP_2);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-etape-2'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-etape-2'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-etape-2'];

        $this->settings->get('Durée moyenne financement', 'type');

        foreach (json_decode($this->settings->value) as $aAverageFundingDuration) {
            if ($this->projects->amount >= $aAverageFundingDuration->min && $this->projects->amount <= $aAverageFundingDuration->max) {
                $this->iAverageFundingDuration = $aAverageFundingDuration->heures / 24;
            }
        }

        if (is_null($this->iAverageFundingDuration)) {
            // @todo arbitrary choice
            $this->iAverageFundingDuration = 15;
        }

        $oCompanyCreationDate          = new \DateTime($this->companies->date_creation);
        $this->bAnnualAccountsQuestion = $oCompanyCreationDate->diff(new \DateTime())->days < \projects::MINIMUM_CREATION_DAYS;

        $this->settings->get('Lien conditions generales depot dossier', 'type');
        $this->lienConditionsGenerales = $this->settings->value;

        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->dureePossible = explode(',', $this->settings->value);

        $aForm = isset($_SESSION['forms']['depot-de-dossier-2']['values']) ? $_SESSION['forms']['depot-de-dossier-2']['values'] : array();

        $this->sStep1Email = isset($_SESSION['forms']['depot-de-dossier']['email']) ? $_SESSION['forms']['depot-de-dossier']['email'] : null;
        $this->aErrors     = isset($_SESSION['forms']['depot-de-dossier-2']['errors']) ? $_SESSION['forms']['depot-de-dossier-2']['errors'] : array();
        $this->aForm       = array(
            'raison_sociale'         => isset($aForm['raison_sociale']) ? $aForm['raison_sociale'] : $this->companies->name,
            'civilite'               => isset($aForm['civilite']) ? $aForm['civilite'] : $this->clients->civilite,
            'prenom'                 => isset($aForm['prenom']) ? $aForm['prenom'] : $this->clients->prenom,
            'nom'                    => isset($aForm['nom']) ? $aForm['nom'] : $this->clients->nom,
            'fonction'               => isset($aForm['fonction']) ? $aForm['fonction'] : $this->clients->fonction,
            'email'                  => isset($aForm['email']) ? $aForm['email'] : $this->removeEmailSuffix($this->clients->email),
            'telephone'              => isset($aForm['telephone']) ? $aForm['telephone'] : $this->clients->telephone,
            'civilite_prescripteur'  => isset($aForm['civilite_prescripteur']) ? $aForm['civilite_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->civilite),
            'prenom_prescripteur'    => isset($aForm['prenom_prescripteur']) ? $aForm['prenom_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->prenom),
            'nom_prescripteur'       => isset($aForm['nom_prescripteur']) ? $aForm['nom_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->nom),
            'fonction_prescripteur'  => isset($aForm['fonction_prescripteur']) ? $aForm['fonction_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->fonction),
            'email_prescripteur'     => isset($aForm['email_prescripteur']) ? $aForm['email_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->removeEmailSuffix($this->clients_prescripteur->email)),
            'telephone_prescripteur' => isset($aForm['telephone_prescripteur']) ? $aForm['telephone_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->telephone),
            'gerant'                 => isset($aForm['gerant']) ? $aForm['gerant'] : (empty($this->clients_prescripteur->id_client) ? 'oui' : 'non'),
            'bilans'                 => isset($aForm['bilans']) ? $aForm['bilans'] : '',
            'commentaires'           => isset($aForm['commentaires']) ? $aForm['commentaires'] : $this->projects->comments,
            'duree'                  => isset($aForm['duree']) ? $aForm['duree'] : $this->projects->period
        );

        unset($_SESSION['forms']['depot-de-dossier-2']);

        if (isset($_POST['send_form_depot_dossier'])) {
            $this->step2Form();
        }
    }

    private function step2Form()
    {
        if (empty($_POST['raison_sociale'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['raison_sociale'] = true;
        }
        if (empty($_POST['civilite'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['civilite'] = true;
        }
        if (empty($_POST['prenom'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['prenom'] = true;
        }
        if (empty($_POST['nom'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['nom'] = true;
        }
        if (empty($_POST['fonction'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['fonction'] = true;
        }
        if (empty($_POST['email']) || false === $this->ficelle->isEmail($_POST['email'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['email'] = true;
        }
        if (empty($_POST['telephone'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['telephone'] = true;
        }
        if (empty($_POST['gerant'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['gerant'] = true;
        }
        if ($this->bAnnualAccountsQuestion && empty($_POST['bilans'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['bilans'] = true;
        }
        if (empty($_POST['duree']) || false === in_array($_POST['duree'], $this->dureePossible)) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['duree'] = true;
        }
        if (empty($_POST['commentaires'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['commentaires'] = true;
        }
        if ('non' === $_POST['gerant']) {
            if (empty($_POST['civilite_prescripteur'])) {
                $_SESSION['forms']['depot-de-dossier-2']['errors']['civilite_prescripteur'] = true;
            }
            if (empty($_POST['prenom_prescripteur'])) {
                $_SESSION['forms']['depot-de-dossier-2']['errors']['prenom_prescripteur'] = true;
            }
            if (empty($_POST['nom_prescripteur'])) {
                $_SESSION['forms']['depot-de-dossier-2']['errors']['nom_prescripteur'] = true;
            }
            if (empty($_POST['fonction_prescripteur'])) {
                $_SESSION['forms']['depot-de-dossier-2']['errors']['fonction_prescripteur'] = true;
            }
            if (empty($_POST['email_prescripteur']) || false === $this->ficelle->isEmail($_POST['email_prescripteur'])) {
                $_SESSION['forms']['depot-de-dossier-2']['errors']['email_prescripteur'] = true;
            }
            if (empty($_POST['telephone_prescripteur'])) {
                $_SESSION['forms']['depot-de-dossier-2']['errors']['telephone_prescripteur'] = true;
            }
        } elseif ('oui' === $_POST['gerant'] && empty($_POST['cgv'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['cgv'] = true;
        }
        if (false === empty($_SESSION['forms']['depot-de-dossier-2']['errors'])) {
            $_SESSION['forms']['depot-de-dossier-2']['values'] = $_POST;
            $this->redirect(self::PAGE_NAME_STEP_2);
        }

        if (true === $this->clients->existEmail($_POST['email'])) { // Email does not exist in DB
            $this->clients->email = $_POST['email'];
        } elseif ($this->removeEmailSuffix($this->clients->email) !== $_POST['email']) { // Email exists but is different from previous one
            $this->clients->email = $_POST['email'] . '-' . time();
        }

        $this->clients->civilite          = $_POST['civilite'];
        $this->clients->prenom            = $_POST['prenom'];
        $this->clients->nom               = $_POST['nom'];
        $this->clients->fonction          = $_POST['fonction'];
        $this->clients->telephone         = $_POST['telephone'];
        $this->clients->id_langue         = 'fr';
        $this->clients->slug              = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);
        $this->clients->status_transition = 1; // Used in bootstrap and ajax depot de dossier
        $this->clients->update();

        $this->companies->name          = $_POST['raison_sociale'];
        $this->companies->email_facture = $_POST['email'];
        $this->companies->update();

        if ('non' === $_POST['gerant']) {
            if (true === $this->clients_prescripteur->existEmail($_POST['email_prescripteur'])) { // Email does not exist in DB
                $this->clients_prescripteur->email = $_POST['email_prescripteur'];
            } elseif ($this->removeEmailSuffix($this->clients_prescripteur->email) !== $_POST['email_prescripteur']) { // Email exists but is different from previous one
                $this->clients_prescripteur->email = $_POST['email_prescripteur'] . '-' . time();
            }

            $this->clients_prescripteur->civilite  = $_POST['civilite_prescripteur'];
            $this->clients_prescripteur->prenom    = $_POST['prenom_prescripteur'];
            $this->clients_prescripteur->nom       = $_POST['nom_prescripteur'];
            $this->clients_prescripteur->fonction  = $_POST['fonction_prescripteur'];
            $this->clients_prescripteur->telephone = $_POST['telephone_prescripteur'];
            $this->clients_prescripteur->slug      = $this->bdd->generateSlug($this->clients_prescripteur->prenom . '-' . $this->clients_prescripteur->nom);

            if (empty($this->clients_prescripteur->id_client)) {
                $this->clients_prescripteur->create();

                $this->clients_adresses_prescripteur->id_client = $this->clients_prescripteur->id_client;
                $this->clients_adresses_prescripteur->civilite  = $_POST['civilite_prescripteur'];
                $this->clients_adresses_prescripteur->prenom    = $_POST['prenom_prescripteur'];
                $this->clients_adresses_prescripteur->nom       = $_POST['nom_prescripteur'];
                $this->clients_adresses_prescripteur->telephone = $_POST['telephone_prescripteur'];
                $this->clients_adresses_prescripteur->create();

                $this->companies_prescripteur->create();

                $this->prescripteurs->id_client = $this->clients_prescripteur->id_client;
                $this->prescripteurs->id_entite = $this->companies_prescripteur->id_company;
                $this->prescripteurs->create();

                $this->projects->id_prescripteur = $this->prescripteurs->id_prescripteur;
            } else {
                $this->clients_prescripteur->update();
                $this->prescripteurs->update();
            }
        } else {
            $this->projects->id_prescripteur = 0;

            $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
            if ($this->acceptations_legal_docs->get($this->lienConditionsGenerales, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc')) {
                $this->acceptations_legal_docs->update();
            } else {
                $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
                $this->acceptations_legal_docs->id_client    = $this->clients->id_client;
                $this->acceptations_legal_docs->create();
            }
        }

        $this->projects->comments = $_POST['commentaires'];
        $this->projects->period   = $_POST['duree'];
        $this->projects->update();

        if ($this->bAnnualAccountsQuestion && $_POST['bilans'] === 'non') {
            $this->redirect(self::PAGE_NAME_END, \projects_status::PAS_3_BILANS);
        }

        $this->redirect(self::PAGE_NAME_STEP_3, \projects_status::COMPLETUDE_ETAPE_3);
    }

    public function _etape3()
    {
        $this->page = 'depot_dossier_3';

        $this->checkProjectHash(self::PAGE_NAME_STEP_3);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-etape-3'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-etape-3'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-etape-3'];

        $oFinancial = new \PHPExcel_Calculation_Financial();

        $this->settings->get('Tri par taux intervalles', 'type');
        $aRatesIntervals      = explode(';', $this->settings->value);
        $aMinimumRateInterval = explode('-', $aRatesIntervals[0]);
        $aMaximumRateInterval = explode('-', end($aRatesIntervals));

        $this->settings->get('TVA', 'type');
        $fVATRate = (float) $this->settings->value;

        $this->settings->get('Commission remboursement', 'type');
        $fCommission = ($oFinancial->PMT($this->settings->value / 12, $this->projects->period, - $this->projects->amount) - $oFinancial->PMT(0, $this->projects->period, - $this->projects->amount)) * (1 + $fVATRate);

        $this->iMinimumMonthlyPayment = round($oFinancial->PMT($aMinimumRateInterval[0] / 100 / 12, $this->projects->period, - $this->projects->amount) + $fCommission);
        $this->iMaximumMonthlyPayment = round($oFinancial->PMT($aMaximumRateInterval[1] / 100 / 12, $this->projects->period, - $this->projects->amount) + $fCommission);

        $aAnnualAccounts = $this->companies_bilans->select('id_company = ' . $this->companies->id_company, 'cloture_exercice_fiscal DESC', 0, 1);

        if (false === empty($aAnnualAccounts)) {
            $this->companies_actif_passif->get($aAnnualAccounts[0]['id_bilan'], 'id_bilan');
        }

        $iAltaresCapitalStock     = empty($this->companies_actif_passif->capitaux_propres) ? 0 : $this->companies_actif_passif->capitaux_propres;
        $iAltaresOperationIncomes = empty($aAnnualAccounts[0]['resultat_exploitation']) ? 0 : $aAnnualAccounts[0]['resultat_exploitation'];
        $iAltaresRevenue          = empty($aAnnualAccounts[0]['ca']) ? 0 : $aAnnualAccounts[0]['ca'];

        $this->iCapitalStock     = isset($_SESSION['forms']['depot-de-dossier-3']['values']['fonds_propres']) ? $_SESSION['forms']['depot-de-dossier-3']['values']['fonds_propres'] : (empty($this->projects->fonds_propres_declara_client) ? $iAltaresCapitalStock : $this->projects->fonds_propres_declara_client);
        $this->iOperatingIncomes = isset($_SESSION['forms']['depot-de-dossier-3']['values']['resultat_brute_exploitation']) ? $_SESSION['forms']['depot-de-dossier-3']['values']['resultat_brute_exploitation'] : (empty($this->projects->resultat_exploitation_declara_client) ? $iAltaresOperationIncomes : $this->projects->resultat_exploitation_declara_client);
        $this->iRevenue          = isset($_SESSION['forms']['depot-de-dossier-3']['values']['ca']) ? $_SESSION['forms']['depot-de-dossier-3']['values']['ca'] : (empty($this->projects->ca_declara_client) ? $iAltaresRevenue : $this->projects->ca_declara_client);

        $this->aErrors = isset($_SESSION['forms']['depot-de-dossier-3']['errors']) ? $_SESSION['forms']['depot-de-dossier-3']['errors'] : array();

        unset($_SESSION['forms']['depot-de-dossier-3']);

        if (isset($_POST['send_form_etape_3'])) {
            $_SESSION['forms']['depot-de-dossier-3']['values'] = $_POST;

            if (false === isset($_POST['fonds_propres']) || $_POST['fonds_propres'] == '') {
                $_SESSION['forms']['depot-de-dossier-3']['errors']['fonds_propres'] = true;
            }
            if (false === isset($_POST['resultat_brute_exploitation']) || $_POST['resultat_brute_exploitation'] == '') {
                $_SESSION['forms']['depot-de-dossier-3']['errors']['resultat_brute_exploitation'] = true;
            }
            if (false === isset($_POST['ca']) || $_POST['ca'] == '') {
                $_SESSION['forms']['depot-de-dossier-3']['errors']['ca'] = true;
            }
            if (empty($_FILES['liasse_fiscal']['name'])) {
                $_SESSION['forms']['depot-de-dossier-3']['errors']['liasse_fiscale'] = true;
            }
            if (false === $this->uploadAttachment('liasse_fiscal', attachment_type::DERNIERE_LIASSE_FISCAL)) {
                $_SESSION['forms']['depot-de-dossier-3']['errors']['liasse_fiscale'] = $this->upload->getErrorType();
            }
            if (false === empty($_FILES['autre']['name']) && false === $this->uploadAttachment('autre', attachment_type::AUTRE1)) {
                $_SESSION['forms']['depot-de-dossier-3']['errors']['autre'] = $this->upload->getErrorType();
            }
            if (false === empty($_SESSION['forms']['depot-de-dossier-3']['errors']))  {
                $this->redirect(self::PAGE_NAME_STEP_3);
            }

            $bUpdateDeclaration                   = false;
            $_POST['fonds_propres']               = $this->ficelle->cleanFormatedNumber($_POST['fonds_propres']);
            $_POST['resultat_brute_exploitation'] = $this->ficelle->cleanFormatedNumber($_POST['resultat_brute_exploitation']);
            $_POST['ca']                          = $this->ficelle->cleanFormatedNumber($_POST['ca']);

            if ($iAltaresCapitalStock != $_POST['fonds_propres']) {
                $this->projects->fonds_propres_declara_client = $_POST['fonds_propres'];
                $bUpdateDeclaration = true;
            } elseif (false === empty($this->projects->fonds_propres_declara_client) && $iAltaresCapitalStock == $_POST['fonds_propres']) {
                $this->projects->fonds_propres_declara_client = 0;
                $bUpdateDeclaration = true;
            }

            if ($iAltaresOperationIncomes != $_POST['resultat_brute_exploitation']) {
                $this->projects->resultat_exploitation_declara_client = $_POST['resultat_brute_exploitation'];
                $bUpdateDeclaration = true;
            } elseif (false === empty($this->projects->resultat_exploitation_declara_client) && $iAltaresOperationIncomes == $_POST['resultat_brute_exploitation']) {
                $this->projects->resultat_exploitation_declara_client = 0;
                $bUpdateDeclaration = true;
            }

            if ($iAltaresRevenue != $_POST['ca']) {
                $this->projects->ca_declara_client = $_POST['ca'];
                $bUpdateDeclaration = true;
            } elseif (false === empty($this->projects->ca_declara_client) && $iAltaresRevenue == $_POST['ca']) {
                $this->projects->ca_declara_client = 0;
                $bUpdateDeclaration = true;
            }

            if ($bUpdateDeclaration) {
                $this->projects->update();
            }

            if ($_POST['fonds_propres'] < 0) {
                $this->redirect(self::PAGE_NAME_PROSPECT, \projects_status::NOTE_EXTERNE_FAIBLE, 'Fonds propres négatifs');
            }

            if ($_POST['resultat_brute_exploitation'] < 0) {
                $this->redirect(self::PAGE_NAME_PROSPECT, \projects_status::NOTE_EXTERNE_FAIBLE, 'REX négatif');
            }

            if ($_POST['ca'] < \projects::MINIMUM_REVENUE) {
                $this->redirect(self::PAGE_NAME_PROSPECT, \projects_status::NOTE_EXTERNE_FAIBLE, 'CA trop faibles');
            }

            if (isset($_POST['procedure_acceleree'])) {
                $this->projects->process_fast = 1;
                $this->projects->update();

                $this->redirect(self::PAGE_NAME_FILES);
            } else {
                $this->sendSubscriptionConfirmationEmail();

                $this->clients->status = 1;
                $this->clients->update();

                $this->redirect(self::PAGE_NAME_END, \projects_status::A_TRAITER);
            }
        }
    }

    public function _partenaire()
    {
        $this->page = 'depot_dossier_partenaire';

        $this->checkProjectHash(self::PAGE_NAME_PARTNER);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-etape-2'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-etape-2'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-etape-2'];

        $this->lng['partenaire'] = $this->ln->selectFront('depot-de-dossier-partenaire-' . $_SESSION['depot-de-dossier']['partner'], $this->language, $this->App);

        $this->settings->get('Lien conditions generales depot dossier', 'type');
        $this->lienConditionsGenerales = $this->settings->value;

        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->dureePossible = empty($this->settings->value) ? array(24, 36, 48, 60) : explode(',', $this->settings->value);

        $aForm = isset($_SESSION['forms']['depot-de-dossier-partenaire']['values']) ? $_SESSION['forms']['depot-de-dossier-partenaire']['values'] : array();

        $this->aErrors = isset($_SESSION['forms']['depot-de-dossier-partenaire']['errors']) ? $_SESSION['forms']['depot-de-dossier-partenaire']['errors'] : array();
        $this->aForm   = array(
            'raison_sociale'         => isset($aForm['raison_sociale']) ? $aForm['raison_sociale'] : $this->companies->name,
            'civilite'               => isset($aForm['civilite']) ? $aForm['civilite'] : $this->clients->civilite,
            'prenom'                 => isset($aForm['prenom']) ? $aForm['prenom'] : $this->clients->prenom,
            'nom'                    => isset($aForm['nom']) ? $aForm['nom'] : $this->clients->nom,
            'fonction'               => isset($aForm['fonction']) ? $aForm['fonction'] : $this->clients->fonction,
            'email'                  => isset($aForm['email']) ? $aForm['email'] : $this->removeEmailSuffix($this->clients->email),
            'telephone'              => isset($aForm['telephone']) ? $aForm['telephone'] : $this->clients->telephone,
            'duree'                  => isset($aForm['duree']) ? $aForm['duree'] : $this->projects->period
        );

        $aAttachmentTypes = $this->attachment_type->getAllTypesForProjects($this->language, true, array(
            \attachment_type::PRESENTATION_ENTRERPISE,
            \attachment_type::RIB,
            \attachment_type::CNI_PASSPORTE_DIRIGEANT,
            \attachment_type::CNI_PASSPORTE_VERSO,
            \attachment_type::DERNIERE_LIASSE_FISCAL,
            \attachment_type::LIASSE_FISCAL_N_1,
            \attachment_type::LIASSE_FISCAL_N_2,
            \attachment_type::RAPPORT_CAC,
            \attachment_type::PREVISIONNEL,
            \attachment_type::BALANCE_CLIENT,
            \attachment_type::BALANCE_FOURNISSEUR,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_1,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_2,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_3,
            \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3,
            \attachment_type::SITUATION_COMPTABLE_INTERMEDIAIRE,
            \attachment_type::DERNIERS_COMPTES_CONSOLIDES,
            \attachment_type::STATUTS,
            \attachment_type::PRESENTATION_PROJET,
            \attachment_type::DERNIERE_LIASSE_FISCAL_HOLDING,
            \attachment_type::KBIS_HOLDING,
            \attachment_type::AUTRE1,
            \attachment_type::AUTRE2
        ));
        $this->aAttachmentTypes = $this->attachment_type->changeLabelWithDynamicContent($aAttachmentTypes);

        unset($_SESSION['forms']['depot-de-dossier-partenaire']);

        if (isset($_POST['send_form_depot_dossier'])) {
            $this->partnerForm();
        }
    }

    private function partnerForm()
    {
        if (empty($_POST['raison_sociale'])) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['raison_sociale'] = true;
        }
        if (empty($_POST['civilite'])) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['civilite'] = true;
        }
        if (empty($_POST['prenom'])) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['prenom'] = true;
        }
        if (empty($_POST['nom'])) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['nom'] = true;
        }
        if (empty($_POST['fonction'])) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['fonction'] = true;
        }
        if (empty($_POST['email']) || false === $this->ficelle->isEmail($_POST['email'])) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['email'] = true;
        }
        if (empty($_POST['telephone'])) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['telephone'] = true;
        }
        if (empty($_POST['duree']) || false === in_array($_POST['duree'], $this->dureePossible)) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['duree'] = true;
        }
        if (empty($_POST['cgv'])) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['cgv'] = true;
        }
        foreach (array_keys($_FILES) as $iAttachmentType) {
            $this->uploadAttachment($iAttachmentType, $iAttachmentType);
        }
        if (true === $this->error_fichier) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['errors']['files'] = $this->upload->getErrorType();
        }
        if (false === empty($_SESSION['forms']['depot-de-dossier-partenaire']['errors'])) {
            $_SESSION['forms']['depot-de-dossier-partenaire']['values'] = $_POST;
            $this->redirect(self::PAGE_NAME_PARTNER);
        }

        if (true === $this->clients->existEmail($_POST['email'])) { // Email does not exist in DB
            $this->clients->email = $_POST['email'];
        } elseif ($this->removeEmailSuffix($this->clients->email) !== $_POST['email']) { // Email exists but is different from previous one
            $this->clients->email = $_POST['email'] . '-' . time();
        }

        $this->clients->civilite          = $_POST['civilite'];
        $this->clients->prenom            = $_POST['prenom'];
        $this->clients->nom               = $_POST['nom'];
        $this->clients->fonction          = $_POST['fonction'];
        $this->clients->telephone         = $_POST['telephone'];
        $this->clients->id_langue         = 'fr';
        $this->clients->slug              = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);
        $this->clients->status_transition = 1; // Used in bootstrap and ajax depot de dossier
        $this->clients->update();

        $this->companies->name          = $_POST['raison_sociale'];
        $this->companies->email_facture = $_POST['email'];
        $this->companies->update();

        $this->projects->id_prescripteur = 0;

        $this->acceptations_legal_docs = $this->loadData('acceptations_legal_docs');
        if ($this->acceptations_legal_docs->get($this->lienConditionsGenerales, 'id_client = "' . $this->clients->id_client . '" AND id_legal_doc')) {
            $this->acceptations_legal_docs->update();
        } else {
            $this->acceptations_legal_docs->id_legal_doc = $this->lienConditionsGenerales;
            $this->acceptations_legal_docs->id_client    = $this->clients->id_client;
            $this->acceptations_legal_docs->create();
        }

        $this->projects->comments = '';
        $this->projects->period   = $_POST['duree'];
        $this->projects->update();

        $this->sendSubscriptionConfirmationEmail();

        $this->clients->status = 1;
        $this->clients->update();

        $this->redirect(self::PAGE_NAME_END, \projects_status::A_TRAITER);
    }

    public function _prospect()
    {
        $this->page = 'depot_dossier_prospect';

        $this->checkProjectHash(self::PAGE_NAME_PROSPECT);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-prospect'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-prospect'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-prospect'];

        // @todo may probably be factorized with step 2 form
        $aForm = isset($_SESSION['forms']['depot-de-dossier-prospect']['values']) ? $_SESSION['forms']['depot-de-dossier-prospect']['values'] : array();

        $this->aErrors = isset($_SESSION['forms']['depot-de-dossier-prospect']['errors']) ? $_SESSION['forms']['depot-de-dossier-prospect']['errors'] : array();
        $this->aForm   = array(
            'raison_sociale'         => isset($aForm['raison_sociale']) ? $aForm['raison_sociale'] : $this->companies->name,
            'civilite'               => isset($aForm['civilite']) ? $aForm['civilite'] : $this->clients->civilite,
            'prenom'                 => isset($aForm['prenom']) ? $aForm['prenom'] : $this->clients->prenom,
            'nom'                    => isset($aForm['nom']) ? $aForm['nom'] : $this->clients->nom,
            'fonction'               => isset($aForm['fonction']) ? $aForm['fonction'] : $this->clients->fonction,
            'email'                  => isset($aForm['email']) ? $aForm['email'] : $this->removeEmailSuffix($this->clients->email),
            'telephone'              => isset($aForm['telephone']) ? $aForm['telephone'] : $this->clients->telephone
        );

        unset($_SESSION['forms']['depot-de-dossier-prospect']);

        if (isset($_POST['send_form_depot_dossier'])) {
            $this->prospectForm();
        }
    }

    private function prospectForm()
    {
        $_SESSION['forms']['depot-de-dossier-prospect']['values'] = $_POST;

        if (empty($_POST['raison_sociale'])) {
            $_SESSION['forms']['depot-de-dossier-prospect']['errors']['raison_sociale'] = true;
        }
        if (empty($_POST['civilite'])) {
            $_SESSION['forms']['depot-de-dossier-prospect']['errors']['civilite'] = true;
        }
        if (empty($_POST['prenom'])) {
            $_SESSION['forms']['depot-de-dossier-prospect']['errors']['prenom'] = true;
        }
        if (empty($_POST['nom'])) {
            $_SESSION['forms']['depot-de-dossier-prospect']['errors']['nom'] = true;
        }
        if (empty($_POST['fonction'])) {
            $_SESSION['forms']['depot-de-dossier-prospect']['errors']['fonction'] = true;
        }
        if (empty($_POST['email']) || false === $this->ficelle->isEmail($_POST['email'])) {
            $_SESSION['forms']['depot-de-dossier-prospect']['errors']['email'] = true;
        }
        if (empty($_POST['telephone'])) {
            $_SESSION['forms']['depot-de-dossier-prospect']['errors']['telephone'] = true;
        }
        if (false === empty($_SESSION['forms']['depot-de-dossier-prospect']['errors'])) {
            $this->redirect(self::PAGE_NAME_PROSPECT);
        }

        if (true === $this->clients->existEmail($_POST['email'])) { // Email does not exist in DB
            $this->clients->email = $_POST['email'];
        } elseif ($this->removeEmailSuffix($this->clients->email) !== $_POST['email']) { // Email exists but is different from previous one
            $this->clients->email = $_POST['email'] . '-' . time();
        }

        $this->clients->civilite          = $_POST['civilite'];
        $this->clients->prenom            = $_POST['prenom'];
        $this->clients->nom               = $_POST['nom'];
        $this->clients->fonction          = $_POST['fonction'];
        $this->clients->telephone         = $_POST['telephone'];
        $this->clients->id_langue         = 'fr';
        $this->clients->slug              = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);
        $this->clients->status_transition = 1; // Used in bootstrap and ajax depot de dossier
        $this->clients->update();

        $this->companies->name          = $_POST['raison_sociale'];
        $this->companies->email_facture = $_POST['email'];
        $this->companies->update();

        $this->redirect(self::PAGE_NAME_END);
    }

    public function _fichiers()
    {
        $this->page = 'depot_dossier_fichiers';

        $this->checkProjectHash(self::PAGE_NAME_FILES);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-fichiers'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-fichiers'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-fichiers'];

        $this->lng['espace-emprunteur'] = $this->ln->selectFront('depot-de-dossier-espace-emprunteur', $this->language, $this->App);

        $this->sAttachmentList  = '';
        $aAttachmentTypes       = $this->attachment_type->getAllTypesForProjects($this->language, false);
        $this->aAttachmentTypes = $this->attachment_type->changeLabelWithDynamicContent($aAttachmentTypes);

        $this->sYearLessTwo   = date('Y') - 2;
        $this->sYearLessThree = date('Y') - 3;

        $this->projects_last_status_history = $this->loadData('projects_last_status_history');
        $this->projects_last_status_history->get($this->projects->id_project, 'id_project');
        $this->projects_status_history->get($this->projects_last_status_history->id_project_status_history, 'id_project_status_history');

        if (false === empty($this->projects_status_history->content)) {
            $oDOMElement = new DOMDocument();
            $oDOMElement->loadHTML($this->projects_status_history->content);
            $oList = $oDOMElement->getElementsByTagName('ul');
            if ($oList->length > 0 && $oList->item(0)->childNodes->length > 0) {
                $this->sAttachmentList = $oList->item(0)->C14N();
            }
        }

        if (isset($_SESSION['forms']['depot-de-dossier-fichiers'])) {
            $this->aForm   = isset($_SESSION['forms']['depot-de-dossier-fichiers']['values']) ? $_SESSION['forms']['depot-de-dossier-fichiers']['values'] : array();
            $this->aErrors = isset($_SESSION['forms']['depot-de-dossier-fichiers']['errors']) ? $_SESSION['forms']['depot-de-dossier-fichiers']['errors'] : array();
            unset($_SESSION['forms']['depot-de-dossier-fichiers']);
        }

        if (false === empty($_POST) || false === empty($_FILES)) {
            foreach (array_keys($_FILES) as $iAttachmentType) {
                $this->uploadAttachment($iAttachmentType, $iAttachmentType);
            }

            if (true === $this->error_fichier) {
                $_SESSION['forms']['depot-de-dossier-fichiers']['errors']['files'] = $this->upload->getErrorType();
                $this->redirect(self::PAGE_NAME_FILES);
            }

            $this->sendCommercialEmail('notification-ajout-document-dossier');

            $this->redirect(self::PAGE_NAME_END);
        }
    }

    public function _fin()
    {
        $this->page = 'depot_dossier_fin';

        $this->checkProjectHash(self::PAGE_NAME_END);

        $this->lng['depot-de-dossier-fin'] = $this->ln->selectFront('depot-de-dossier-fin', $this->language, $this->App);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-fin'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-fin'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-fin'];

        $this->bDisplayContact = false;
        $this->sMessage        = $this->lng['depot-de-dossier-fin']['contenu-non-eligible'];
        $this->bDisplayTouchvibes = false;

        switch ($this->projects_status->status) {
            case \projects_status::ABANDON:
                $this->sMessage = $this->lng['depot-de-dossier-fin']['abandon'];
                break;
            CASE \projects_status::PAS_3_BILANS:
                $this->sMessage = $this->lng['depot-de-dossier-fin']['pas-3-bilans'];
                break;
            case \projects_status::REVUE_ANALYSTE:
            case \projects_status::COMITE:
            case \projects_status::PREP_FUNDING:
                $this->bDisplayContact = true;
                $this->sMessage        = $this->lng['depot-de-dossier-fin']['analyse'];
                break;
            case \projects_status::COMPLETUDE_ETAPE_3:
            case \projects_status::A_TRAITER:
            case \projects_status::EN_ATTENTE_PIECES:
                if (1 == $this->projects->process_fast) {
                    $this->sMessage = $this->lng['depot-de-dossier-fin']['procedure-acceleree'];
                } else {
                    $this->sMessage = $this->lng['depot-de-dossier-fin']['contenu'];
                }
                break;
            case \projects_status::NOTE_EXTERNE_FAIBLE:
                switch ($this->projects->retour_altares) {
                    case Altares::RESPONSE_CODE_PROCEDURE:
                        $this->sMessage = $this->lng['depot-de-dossier-fin']['procedure-en-cours'];
                        break;
                    case Altares::RESPONSE_CODE_INACTIVE:
                    case Altares::RESPONSE_CODE_UNKNOWN_SIREN:
                    $this->sMessage = $this->lng['depot-de-dossier-fin']['no-siren'];
                    break;
                    case Altares::RESPONSE_CODE_NOT_REGISTERED:
                        $this->sMessage = $this->lng['depot-de-dossier-fin']['no-rcs'];
                        break;
                    case Altares::RESPONSE_CODE_NEGATIVE_CAPITAL_STOCK:
                    case Altares::RESPONSE_CODE_NEGATIVE_RAW_OPERATING_INCOMES:
                        $this->sMessage = $this->lng['depot-de-dossier-fin']['rex-nega'];
                        break;
                    case Altares::RESPONSE_CODE_ELIGIBLE:
                        if (
                            $this->projects->fonds_propres_declara_client < 0
                            || $this->projects->resultat_exploitation_declara_client < 0
                            || $this->projects->ca_declara_client <= \projects::MINIMUM_REVENUE
                        ) {
                            $this->sMessage = $this->lng['depot-de-dossier-fin']['rex-nega'];
                        }
                        break;
                }
                $this->bDisplayTouchvibes = true;
                break;
        }

        if ($this->bDisplayContact) {
            $this->lng['contact']  = $this->ln->selectFront('contact', $this->language, $this->App);
            $this->demande_contact = $this->loadData('demande_contact');

            foreach ($this->tree_elements->select('id_tree = 47 AND id_langue = "' . $this->language . '"') as $elt) {
                $this->elements->get($elt['id_element']);
                $this->content[$this->elements->slug] = $elt['value'];
            }

            if (isset($_POST['send_form_contact'])) {
                include $this->path . 'apps/default/controllers/root.php';

                $oCommand = new \Command('root', '_default', array(), $this->language);
                $oRoot    = new \rootController($oCommand, $this->Config, 'default');
                $oRoot->contactForm();

                $this->demande_contact = $oRoot->demande_contact;
                $this->form_ok         = $oRoot->form_ok;
                $this->error_demande   = $oRoot->error_demande;
                $this->error_message   = $oRoot->error_message;
                $this->error_nom       = $oRoot->error_nom;
                $this->error_prenom    = $oRoot->error_prenom;
                $this->error_email     = $oRoot->error_email;
                $this->error_captcha   = $oRoot->error_captcha;

                if ($this->form_ok) {
                    $this->confirmation = $this->lng['contact']['confirmation'];
                }
            }
        }
    }

    public function _emails()
    {
        $this->page = 'depot_dossier_emails';

        $this->checkProjectHash(self::PAGE_NAME_EMAILS);

        $this->projects->stop_relances = 1;
        $this->projects->update();

        $this->sendCommercialEmail('notification-stop-relance-dossier');
    }

    private function sendSubscriptionConfirmationEmail()
    {
        /** @var \mail_templates $oMailTemplate */
        $oMailTemplate = $this->loadData('mail_templates');
        $oMailTemplate->get('confirmation-depot-de-dossier', 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

        $aVariables = array(
            'prenom'               => empty($this->clients_prescripteur->id_client) ? $this->clients->prenom : $this->clients_prescripteur->prenom,
            'raison_sociale'       => $this->companies->name,
            'lien_reprise_dossier' => $this->surl . '/depot_de_dossier/reprise/' . $this->projects->hash,
            'lien_fb'              => $this->like_fb,
            'lien_tw'              => $this->twitter,
            'sujet'                => htmlentities($oMailTemplate->subject, null, 'UTF-8'),
            'surl'                 => $this->surl,
            'url'                  => $this->url,
        );

        $sRecipient = empty($this->clients_prescripteur->id_client) ? $this->clients->email : $this->clients_prescripteur->email;
        $sRecipient = $this->removeEmailSuffix(trim($sRecipient));

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($oMailTemplate->type, $aVariables);
        $message->setTo($sRecipient);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    private function sendCommercialEmail($sEmailType)
    {
        if ($this->projects->id_commercial > 0) {
            $this->users = $this->loadData('users');
            $this->users->get($this->projects->id_commercial, 'id_user');

            $oMailTemplate = $this->loadData('mail_templates');
            $oMailTemplate->get($sEmailType, 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND locale = "' . $this->getParameter('locale') . '" AND type');

            $aReplacements = array(
                '[ID_PROJET]'      => $this->projects->id_project,
                '[LIEN_BO_PROJET]' => $this->aurl . '/dossiers/edit/' . $this->projects->id_project,
                '[RAISON_SOCIALE]' => utf8_decode($this->companies->name),
                '[SURL]'           => $this->surl
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($oMailTemplate->type, $aReplacements, false);
            $message->setSubject(stripslashes(utf8_decode(str_replace('[ID_PROJET]', $this->projects->id_project, $oMailTemplate->subject))));
            $message->setTo(trim($this->users->email));
            $mailer = $this->get('mailer');
            $mailer->send($message);
        }
    }

    /**
     * @param string $sFieldName
     * @param integer $iAttachmentType
     * @return bool
     */
    private function uploadAttachment($sFieldName, $iAttachmentType)
    {
        if (false === isset($this->upload) || false === $this->upload instanceof \upload) {
            $this->upload = $this->loadLib('upload');
        }

        if (false === isset($this->attachment) || false === $this->attachment instanceof \attachment) {
            $this->attachment = $this->loadData('attachment');
        }

        if (false === isset($this->attachment_type) || false === $this->attachment_type instanceof \attachment_type) {
            $this->attachment_type = $this->loadData('attachment_type');
        }

        if (false === isset($this->attachmentHelper) || false === $this->attachmentHelper instanceof \attachment_helper) {
            $this->attachmentHelper = $this->loadLib('attachment_helper', array($this->attachment, $this->attachment_type, $this->path));;
        }

        $resultUpload = false;
        if (isset($_FILES[$sFieldName]['name']) && $aFileInfo = pathinfo($_FILES[$sFieldName]['name'])) {
            $sFileName    = $aFileInfo['filename'] . '_' . $this->projects->id_project;
            $resultUpload = $this->attachmentHelper->upload($this->projects->id_project, \attachment::PROJECT, $iAttachmentType, $sFieldName, $this->upload, $sFileName);
        }

        if (false === $resultUpload) {
            $this->form_ok       = false;
            $this->error_fichier = true;
        }

        return $resultUpload;
    }

    /**
     * Check that hash is present in URL and valid
     * If hash is valid, check status and redirect to appropriate page
     * @param string $sPage
     */
    private function checkProjectHash($sPage)
    {
        if (false === isset($this->params[0]) || false === $this->projects->get($this->params[0], 'hash')) {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
            die;
        }

        $this->companies->get($this->projects->id_company);
        $this->clients->get($this->companies->id_client_owner);

        if (false === empty($this->projects->id_prescripteur)) {
            $this->prescripteurs->get($this->projects->id_prescripteur, 'id_prescripteur');
            $this->clients_prescripteur->get($this->prescripteurs->id_client, 'id_client');
            $this->clients_adresses_prescripteur->get($this->prescripteurs->id_client, 'id_client');
            $this->companies_prescripteur->get($this->prescripteurs->id_entite, 'id_company');
        }

        $this->projects_status->getLastStatut($this->projects->id_project);

        if (self::PAGE_NAME_EMAILS === $sPage) {
            return;
        }

        switch ($this->projects_status->status) {
            case \projects_status::PAS_3_BILANS:
            case \projects_status::NOTE_EXTERNE_FAIBLE:
                if (false === in_array($sPage, array(self::PAGE_NAME_END, self::PAGE_NAME_PROSPECT))) {
                    $this->redirect(self::PAGE_NAME_END);
                }
                break;
            case \projects_status::COMPLETUDE_ETAPE_2:
                if ($sPage !== self::PAGE_NAME_STEP_2 && empty($_SESSION['depot-de-dossier']['partner'])) {
                    $this->redirect(self::PAGE_NAME_STEP_2);
                } elseif ($sPage !== self::PAGE_NAME_PARTNER && false === empty($_SESSION['depot-de-dossier']['partner'])) {
                    $this->redirect(self::PAGE_NAME_PARTNER);
                }
                break;
            case \projects_status::COMPLETUDE_ETAPE_3:
                if ($this->projects->process_fast == 1 && false === in_array($sPage, array(self::PAGE_NAME_END, self::PAGE_NAME_FILES))) {
                    $this->redirect(self::PAGE_NAME_FILES);
                } elseif ($this->projects->process_fast == 0 && $sPage !== self::PAGE_NAME_STEP_3) {
                    $this->redirect(self::PAGE_NAME_STEP_3);
                }
                break;
            case \projects_status::A_TRAITER:
            case \projects_status::EN_ATTENTE_PIECES:
            case \projects_status::ATTENTE_ANALYSTE:
                if (false === in_array($sPage, array(self::PAGE_NAME_END, self::PAGE_NAME_FILES))) {
                    if (empty($_SESSION['depot-de-dossier']['partner'])) {
                        $this->redirect(self::PAGE_NAME_FILES);
                    } else {
                        $this->redirect(self::PAGE_NAME_END);
                    }
                }
                break;
            case \projects_status::ABANDON:
            default: // Should correspond to "Revue analyste" and above
                if ($sPage !== self::PAGE_NAME_END) {
                    $this->redirect(self::PAGE_NAME_END);
                }
                break;
        }
    }

    /**
     * Redirect to corresponding page and update status
     * @param string $sPage          Page to redirect to
     * @param int    $iProjectStatus Project status
     */
    private function redirect($sPage, $iProjectStatus = null, $sRejectionMessage = '')
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->get('unilend.service.project_manager');

        if (false === is_null($iProjectStatus) && $this->projects_status->status != $iProjectStatus) {
            $oProjectManager->addProjectStatus(\users::USER_ID_FRONT, $iProjectStatus, $this->projects, 0, $sRejectionMessage);
        }

        header('Location: ' . $this->lurl . '/depot_de_dossier/' . $sPage . '/' . $this->projects->hash);
        die;
    }

    private function removeEmailSuffix($sEmail)
    {
        return preg_replace('/^(.*)-[0-9]+$/', '$1', $sEmail);
    }
}
