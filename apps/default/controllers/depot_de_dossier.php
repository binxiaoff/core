<?php

use Unilend\librairies\Altares;
use Unilend\librairies\ULogger;

class depot_de_dossierController extends bootstrap
{
    const PAGE_NAME_STEP_2    = 'etape2';
    const PAGE_NAME_STEP_3    = 'etape3';
    const PAGE_NAME_FILES     = 'fichiers';
    const PAGE_NAME_PROSPECT  = 'prospect';
    const PAGE_NAME_THANK_YOU = 'merci';
    const PAGE_NAME_NOK       = 'nok';

    /**
     * @var attachment_helper
     */
    private $attachmentHelper;

    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        $this->companies               = $this->loadData('companies');
        $this->companies_bilans        = $this->loadData('companies_bilans');
        $this->companies_details       = $this->loadData('companies_details');
        $this->companies_actif_passif  = $this->loadData('companies_actif_passif');
        $this->projects                = $this->loadData('projects');
        $this->projects_status         = $this->loadData('projects_status');
        $this->projects_status_history = $this->loadData('projects_status_history');
        $this->clients                 = $this->loadData('clients');
        $this->clients_adresses        = $this->loadData('clients_adresses');
        $this->clients_prescripteur    = $this->loadData('clients');
        $this->prescripteurs           = $this->loadData('prescripteurs');
        $this->attachment              = $this->loadData('attachment');
        $this->attachment_type         = $this->loadData('attachment_type');

        $this->navigateurActive = 3;

        $this->lng['depot-de-dossier-header'] = $this->ln->selectFront('depot-de-dossier-header', $this->language, $this->App);
        $this->lng['etape1']                  = $this->ln->selectFront('depot-de-dossier-etape-1', $this->language, $this->App);
        $this->lng['etape2']                  = $this->ln->selectFront('depot-de-dossier-etape-2', $this->language, $this->App);
        $this->lng['etape3']                  = $this->ln->selectFront('depot-de-dossier-etape-3', $this->language, $this->App);
        $this->lng['espace-emprunteur']       = $this->ln->selectFront('depot-de-dossier-espace-emprunteur', $this->language, $this->App);
    }

    public function _default()
    {
        $this->checkProjectHash('default');
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
        $this->page = 1;

        if (false === isset($_SESSION['forms']['depot-de-dossier']['values'])) {
            header('Location: ' . $this->lurl . '/lp-depot-de-dossier');
            die;
        }

        $this->lng['landing-page'] = $this->ln->selectFront('landing-page', $this->language, $this->App);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-etape-1'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-etape-1'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-etape-1'];

        $iAmount = $_SESSION['forms']['depot-de-dossier']['values']['montant'];
        $iSIREN  = $_SESSION['forms']['depot-de-dossier']['values']['siren'];
        $sEmail  = isset($_SESSION['forms']['depot-de-dossier']['values']['email']) && $this->ficelle->isEmail($_SESSION['forms']['depot-de-dossier']['values']['email']) ? $_SESSION['forms']['depot-de-dossier']['values']['email'] : null;

        unset($_SESSION['forms']['depot-de-dossier']['values']);

        if (isset($_SESSION['client'])) {
            $this->clients->handleLogout(false);
        }

        $this->clients->id_langue    = $this->language;
        $this->clients->slug_origine = $this->tree->slug;
        $this->clients->source       = $_SESSION['utm_source'];
        $this->clients->source2      = $_SESSION['utm_source2'];

        if (false === is_null($sEmail)) {
            if (false === $this->clients->existEmail($sEmail)) {
                $sEmail .= '-' . time();
            }
            $this->clients->email = $sEmail;
        }

        $this->clients->create();

        $this->clients_adresses->id_client = $this->clients->id_client;
        $this->clients_adresses->create();

        $this->companies->id_client_owner               = $this->clients->id_client;
        $this->companies->siren                         = $iSIREN;
        $this->companies->status_adresse_correspondance = '1';
        $this->companies->create();

        $this->companies_details->id_company = $this->companies->id_company;
        $this->companies_details->create();

        $this->projects->id_company = $this->companies->id_company;
        $this->projects->amount     = $iAmount;
        $this->projects->create();

        /**
         * 1 : activé
         * 2 : activé mais prend pas en compte le résultat
         * 3 : désactivé (DC)
         */
        $this->settings->get('Altares debrayage', 'type');
        $iStatusSetting = $this->settings->value;

        if ($iStatusSetting == 3) {
            $this->redirect(self::PAGE_NAME_NOK, \projects_status::NOTE_EXTERNE_FAIBLE);
        }

        $this->settings->get('Altares email alertes', 'type');
        $sAlertEmail = $this->settings->value;

        try {
            $oAltares = new Altares($this->bdd);
            $oResult  = $oAltares->getEligibility($iSIREN);
        } catch (\Exception $oException) {
            $oLogger = new ULogger('connection', $this->logPath, 'altares.log');
            $oLogger->addRecord(ULogger::ALERT, $oException->getMessage(), array('siren' => $iSIREN));

            mail($sAlertEmail, '[ALERTE] ERREUR ALTARES 2', 'Date ' . date('Y-m-d H:i:s') . '' . $oException->getMessage());
            $this->redirect(self::PAGE_NAME_NOK, \projects_status::NOTE_EXTERNE_FAIBLE);
        }

        if (false === empty($oResult->exception)) {
            $oLogger = new ULogger('connection', $this->logPath, 'altares.log');
            $oLogger->addRecord(ULogger::ALERT, $oResult->exception->code . ' | ' . $oResult->exception->description . ' | ' . $oResult->exception->erreur, array('siren' => $iSIREN));

            mail($sAlertEmail, '[ALERTE] ERREUR ALTARES 1', 'Date ' . date('Y-m-d H:i:s') . 'SIREN : ' . $iSIREN . ' | ' . $oResult->exception->code . ' | ' . $oResult->exception->description . ' | ' . $oResult->exception->erreur);
            $this->redirect(self::PAGE_NAME_NOK, \projects_status::NOTE_EXTERNE_FAIBLE);
        }

        if ($iStatusSetting == 2) {
            $oLogger = new ULogger('connection', $this->logPath, 'altares.log');
            $oLogger->addRecord(ULogger::INFO, 'Tentative évaluation', array('siren' => $iSIREN));

            mail($sAlertEmail, '[ALERTE] Altares Tentative evaluation', 'Date ' . date('Y-m-d H:i:s') . ' siren : ' . $iSIREN);
        }

        $this->projects->retour_altares = $oResult->myInfo->codeRetour;
        $this->projects->update();

        $this->companies->altares_eligibility = $oResult->myInfo->eligibility;
        $this->companies->altares_codeRetour  = $oResult->myInfo->codeRetour;
        $this->companies->altares_motif       = $oResult->myInfo->motif;
        $this->companies->update();

        // @todo patch before Altares fixes bug
        if (9 == $oResult->myInfo->codeRetour) {
            $oResult->myInfo->eligibility = 'Oui';
        }

        switch ($oResult->myInfo->eligibility) {
            case 'Oui':
                $oIdentity                 = $oResult->myInfo->identite;
                $oScore                    = $oResult->myInfo->score;
                $sLastAccountStatementDate = isset($oIdentity->dateDernierBilan) && strlen($oIdentity->dateDernierBilan) > 0 ? substr($oIdentity->dateDernierBilan, 0, 10) : (date('Y') - 1) . '-12-31';
                $aLastAccountStatementDate = explode('-', $sLastAccountStatementDate);

                $this->companies->name                          = $oIdentity->raisonSociale;
                $this->companies->forme                         = $oIdentity->formeJuridique;
                $this->companies->capital                       = $oIdentity->capital;
                $this->companies->code_naf                      = $oIdentity->naf5EntreCode;
                $this->companies->libelle_naf                   = $oIdentity->naf5EntreLibelle;
                $this->companies->adresse1                      = $oIdentity->rue;
                $this->companies->city                          = $oIdentity->ville;
                $this->companies->zip                           = $oIdentity->codePostal;
                $this->companies->phone                         = str_replace(' ', '', $oResult->myInfo->siege->telephone);
                $this->companies->rcs                           = $oIdentity->rcs;
                $this->companies->siret                         = $oIdentity->siret;
                $this->companies->status_adresse_correspondance = '1';
                $this->companies->date_creation                 = substr($oIdentity->dateCreation, 0, 10);
                $this->companies->altares_niveauRisque          = $oScore->niveauRisque;
                $this->companies->altares_scoreVingt            = $oScore->scoreVingt;
                $this->companies->altares_scoreSectorielCent    = $oScore->scoreSectorielCent;
                $this->companies->altares_dateValeur            = substr($oScore->dateValeur, 0, 10);
                $this->companies->update();

                $this->companies_details->date_dernier_bilan        = $sLastAccountStatementDate;
                $this->companies_details->date_dernier_bilan_mois   = $aLastAccountStatementDate[1];
                $this->companies_details->date_dernier_bilan_annee  = $aLastAccountStatementDate[0];
                $this->companies_details->date_dernier_bilan_publie = $sLastAccountStatementDate;
                $this->companies_details->update();

                /**
                 * We only keep N to N - 4 annual accounts
                 * If N to N - 2 are not created, we create them (empty)
                 * Annual accounts are sorted by year
                 */
                $iCurrentYear    = (int) date('Y');
                $aAnnualAccounts = array();
                if (isset($oResult->myInfo->bilans) && is_array($oResult->myInfo->bilans)) {
                    foreach ($oResult->myInfo->bilans as $iIndex => $oAccounts) {
                        $iYear = (int) substr($oAccounts->bilan->dateClotureN, 0, 4);

                        if ($iYear >= $iCurrentYear - 4 && $iYear <= $iCurrentYear) {
                            $aAnnualAccounts[$iYear] = $iIndex;
                        }
                    }
                }

                for ($iYear = $iCurrentYear; $iYear >= $iCurrentYear - 2; $iYear--) {
                    if (false === isset($aAnnualAccounts[$iYear])) {
                        $aAnnualAccounts[$iYear] = null;
                    }
                }

                krsort($aAnnualAccounts);

                $iOrder = 1;
                foreach ($aAnnualAccounts as $iYear => $iBalanceSheetIndex) {
                    $this->companies_bilans->id_company = $this->companies->id_company;
                    $this->companies_bilans->date       = $iYear;

                    $this->companies_actif_passif->annee      = $iYear;
                    $this->companies_actif_passif->ordre      = $iOrder;
                    $this->companies_actif_passif->id_company = $this->companies->id_company;

                    if (false === is_null($iBalanceSheetIndex)) {
                        $oBalanceSheet        = $oResult->myInfo->bilans[$iBalanceSheetIndex];
                        $aFormattedAssetsDebt = array();
                        $aAssetsDebt          = array_merge($oBalanceSheet->bilanRetraiteInfo->posteActifList, $oBalanceSheet->bilanRetraiteInfo->postePassifList);

                        $this->companies_bilans->ca                          = $oBalanceSheet->syntheseFinanciereInfo->syntheseFinanciereList[0]->montantN;
                        $this->companies_bilans->resultat_exploitation       = $oBalanceSheet->syntheseFinanciereInfo->syntheseFinanciereList[1]->montantN;
                        $this->companies_bilans->resultat_brute_exploitation = $oBalanceSheet->soldeIntermediaireGestionInfo->SIGList[9]->montantN;
                        $this->companies_bilans->investissements             = $oBalanceSheet->bilan->posteList[0]->valeur;

                        foreach ($aAssetsDebt as $oAssetsDebtLine) {
                            $aFormattedAssetsDebt[$oAssetsDebtLine->posteCle] = $oAssetsDebtLine->montant;
                        }

                        $this->companies_actif_passif->immobilisations_corporelles        = $aFormattedAssetsDebt['posteBR_IMCOR'];
                        $this->companies_actif_passif->immobilisations_incorporelles      = $aFormattedAssetsDebt['posteBR_IMMINC'];
                        $this->companies_actif_passif->immobilisations_financieres        = $aFormattedAssetsDebt['posteBR_IMFI'];
                        $this->companies_actif_passif->stocks                             = $aFormattedAssetsDebt['posteBR_STO'];
                        $this->companies_actif_passif->creances_clients                   = $aFormattedAssetsDebt['posteBR_BV'] + $aFormattedAssetsDebt['posteBR_BX'] + $aFormattedAssetsDebt['posteBR_ACCCA'] + $aFormattedAssetsDebt['posteBR_ACHE_']; // Créances_clients = avances et acomptes + créances clients + autres créances et cca + autres créances hors exploitation
                        $this->companies_actif_passif->disponibilites                     = $aFormattedAssetsDebt['posteBR_CF'];
                        $this->companies_actif_passif->valeurs_mobilieres_de_placement    = $aFormattedAssetsDebt['posteBR_CD'];
                        $this->companies_actif_passif->capitaux_propres                   = $aFormattedAssetsDebt['posteBR_CPRO'] + $aFormattedAssetsDebt['posteBR_NONVAL']; // capitaux propres = capitaux propres + non valeurs
                        $this->companies_actif_passif->provisions_pour_risques_et_charges = $aFormattedAssetsDebt['posteBR_PROVRC'] + $aFormattedAssetsDebt['posteBR_PROAC']; // provisions pour risques et charges = provisions pour risques et charges + provisions actif circulant
                        $this->companies_actif_passif->amortissement_sur_immo             = $aFormattedAssetsDebt['posteBR_AMPROVIMMO'];
                        $this->companies_actif_passif->dettes_financieres                 = $aFormattedAssetsDebt['posteBR_EMP'] + $aFormattedAssetsDebt['posteBR_VI'] + $aFormattedAssetsDebt['posteBR_EH']; // dettes financières = emprunts + dettes groupe et associés + concours bancaires courants
                        $this->companies_actif_passif->dettes_fournisseurs                = $aFormattedAssetsDebt['posteBR_DW'] + $aFormattedAssetsDebt['posteBR_DX']; // dettes fournisseurs = avances et acomptes clients + dettes fournisseurs
                        $this->companies_actif_passif->autres_dettes                      = $aFormattedAssetsDebt['posteBR_AUTDETTEXPL'] + $aFormattedAssetsDebt['posteBR_DZ'] + $aFormattedAssetsDebt['posteBR_AUTDETTHEXPL']; // autres dettes = autres dettes exploitation + dettes sur immos et comptes rattachés + autres dettes hors exploitation
                    }

                    $this->companies_bilans->create();
                    $this->companies_actif_passif->create();

                    ++$iOrder;
                }

                $oCompanyCreationDate = new \DateTime($this->companies->date_creation);
                $oInterval            = $oCompanyCreationDate->diff(new \DateTime());

                if ($oInterval->days < \projects::MINIMUM_CREATION_DAYS_PROSPECT) {
                    $this->redirect(self::PAGE_NAME_PROSPECT, \projects_status::PAS_3_BILANS);
                }

                $this->redirect(self::PAGE_NAME_STEP_2, \projects_status::COMPLETUDE_ETAPE_2);
                break;
            case 'Non':
            default:
                $this->redirect(self::PAGE_NAME_NOK, \projects_status::NOTE_EXTERNE_FAIBLE);
                break;
        }
    }

    public function _etape2()
    {
        $this->page = 2;

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
            $this->iAverageFundingDuration = 360;
        }

        $oCompanyCreationDate          = new \DateTime($this->companies->date_creation);
        $this->bAnnualAccountsQuestion = $oCompanyCreationDate->diff(new \DateTime())->days < \projects::MINIMUM_CREATION_DAYS;

        $this->settings->get('Lien conditions generales depot dossier', 'type');
        $this->lienConditionsGenerales = $this->settings->value;

        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->dureePossible = empty($this->settings->value) ? array(24, 36, 48, 60) : explode(',', $this->settings->value);

        $aForm = isset($_SESSION['forms']['depot-de-dossier-2']['values']) ? $_SESSION['forms']['depot-de-dossier-2']['values'] : array();

        $this->aErrors = isset($_SESSION['forms']['depot-de-dossier-2']['errors']) ? $_SESSION['forms']['depot-de-dossier-2']['errors'] : array();
        $this->aForm   = array(
            'raison_sociale'        => isset($aForm['raison_sociale']) ? $aForm['raison_sociale'] : $this->companies->name,
            'civilite'              => isset($aForm['civilite']) ? $aForm['civilite'] : $this->clients->civilite,
            'prenom'                => isset($aForm['prenom']) ? $aForm['prenom'] : $this->clients->prenom,
            'nom'                   => isset($aForm['nom']) ? $aForm['nom'] : $this->clients->nom,
            'fonction'              => isset($aForm['fonction']) ? $aForm['fonction'] : $this->clients->fonction,
            'email'                 => isset($aForm['email']) ? $aForm['email'] : $this->removeEmailSuffix($this->clients->email),
            'mobile'                => isset($aForm['mobile']) ? $aForm['mobile'] : $this->clients->mobile,
            'civilite_prescripteur' => isset($aForm['civilite_prescripteur']) ? $aForm['civilite_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->civilite),
            'prenom_prescripteur'   => isset($aForm['prenom_prescripteur']) ? $aForm['prenom_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->prenom),
            'nom_prescripteur'      => isset($aForm['nom_prescripteur']) ? $aForm['nom_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->nom),
            'fonction_prescripteur' => isset($aForm['fonction_prescripteur']) ? $aForm['fonction_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->fonction),
            'email_prescripteur'    => isset($aForm['email_prescripteur']) ? $aForm['email_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->removeEmailSuffix($this->clients_prescripteur->email)),
            'mobile_prescripteur'   => isset($aForm['mobile_prescripteur']) ? $aForm['mobile_prescripteur'] : (empty($this->clients_prescripteur->id_client) ? '' : $this->clients_prescripteur->mobile),
            'gerant'                => isset($aForm['gerant']) ? $aForm['gerant'] : (empty($this->clients_prescripteur->id_client) ? 'oui' : 'non'),
            'bilans'                => isset($aForm['bilans']) ? $aForm['bilans'] : '',
            'commentaires'          => isset($aForm['commentaires']) ? $aForm['commentaires'] : $this->projects->comments,
            'duree'                 => isset($aForm['duree']) ? $aForm['duree'] : $this->projects->period
        );

        unset($_SESSION['forms']['depot-de-dossier-2']);

        if (isset($_POST['send_form_depot_dossier'])) {
            $this->step2Form();
        }
    }

    private function step2Form()
    {
        $_SESSION['forms']['depot-de-dossier-2']['values'] = $_POST;

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
        if (empty($_POST['mobile'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['mobile'] = true;
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
            if (empty($_POST['mobile_prescripteur'])) {
                $_SESSION['forms']['depot-de-dossier-2']['errors']['mobile_prescripteur'] = true;
            }
        } elseif ('oui' === $_POST['gerant'] && empty($_POST['cgv'])) {
            $_SESSION['forms']['depot-de-dossier-2']['errors']['cgv'] = true;
        }
        if (false === empty($_SESSION['forms']['depot-de-dossier-2']['errors'])) {
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
        $this->clients->mobile            = $_POST['mobile'];
        $this->clients->id_langue         = 'fr';
        $this->clients->slug              = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);
        $this->clients->status_transition = 1; // Used in bootstrap and ajax depot de dossier
        $this->clients->update();

        $this->companies->name          = $_POST['raison_sociale'];
        $this->companies->email_facture = $_POST['email'];
        $this->companies->update();

        $this->companies_details->update();

        if ('non' === $_POST['gerant']) {
            if (true === $this->clients_prescripteur->existEmail($_POST['email_prescripteur'])) { // Email does not exist in DB
                $this->clients_prescripteur->email = $_POST['email_prescripteur'];
            } elseif ($this->removeEmailSuffix($this->clients_prescripteur->email) !== $_POST['email_prescripteur']) { // Email exists but is different from previous one
                $this->clients_prescripteur->email = $_POST['email_prescripteur'] . '-' . time();
            }

            $this->clients_prescripteur->civilite = $_POST['civilite_prescripteur'];
            $this->clients_prescripteur->prenom   = $_POST['prenom_prescripteur'];
            $this->clients_prescripteur->nom      = $_POST['nom_prescripteur'];
            $this->clients_prescripteur->fonction = $_POST['fonction_prescripteur'];
            $this->clients_prescripteur->mobile   = $_POST['mobile_prescripteur'];
            $this->clients_prescripteur->slug     = $this->bdd->generateSlug($this->clients_prescripteur->prenom . '-' . $this->clients_prescripteur->nom);

            if (empty($this->clients_prescripteur->id_client)) {
                $this->clients_prescripteur->create();

                $this->prescripteurs->id_client = $this->clients_prescripteur->id_client;
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

        $this->projects->period = $_POST['duree'];
        $this->projects->update();

        if ($this->bAnnualAccountsQuestion && $_POST['bilans'] === 'non') {
            $this->redirect(self::PAGE_NAME_NOK, \projects_status::PAS_3_BILANS);
        }

        $this->redirect(self::PAGE_NAME_STEP_3, \projects_status::COMPLETUDE_ETAPE_3);
    }

    public function _etape3()
    {
        $this->page = 3;

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

        // year considered for "latest liasse fiscal" necessary to get the information from bilans and actif_passif
        $iLastAnnualAccountsYear  = date('Y') - 1;
        $aAnnualAccounts          = $this->companies_bilans->select('id_company = ' . $this->companies->id_company . ' AND date = ' . $iLastAnnualAccountsYear);
        $aAssetsDebts             = $this->companies_actif_passif->select('id_company = ' . $this->companies->id_company . ' AND annee = ' . $iLastAnnualAccountsYear);
        $iAltaresCapitalStock     = $aAssetsDebts[0]['capitaux_propres'];
        $iAltaresOperationIncomes = $aAnnualAccounts[0]['resultat_exploitation'];
        $iAltaresRevenue          = $aAnnualAccounts[0]['ca'];

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
            if (empty($_FILES['liasse_fiscal']['name']) || false === $this->uploadAttachment('liasse_fiscal', attachment_type::DERNIERE_LIASSE_FISCAL)) {
                $_SESSION['forms']['depot-de-dossier-3']['errors']['liasse_fiscale'] = true;
            }
            if (false === empty($_FILES['autre']) && false === $this->uploadAttachment('autre', attachment_type::AUTRE1)) {
                $_SESSION['forms']['depot-de-dossier-3']['errors']['autre'] = true;
            }
            if (false === empty($_SESSION['forms']['depot-de-dossier-3']['errors']))  {
                $this->redirect(self::PAGE_NAME_STEP_3);
            }

            $bUpdateDeclaration                   = false;
            $_POST['fonds_propres']               = str_replace(array(' ', ','), array('', '.'), $_POST['fonds_propres']);
            $_POST['resultat_brute_exploitation'] = str_replace(array(' ', ','), array('', '.'), $_POST['resultat_brute_exploitation']);
            $_POST['ca']                          = str_replace(array(' ', ','), array('', '.'), $_POST['ca']);

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

            if ($_POST['fonds_propres'] < 10000 || $_POST['resultat_brute_exploitation'] < 0 || $_POST['ca'] < 100000) {
                $this->redirect(self::PAGE_NAME_NOK, \projects_status::NOTE_EXTERNE_FAIBLE);
            }

            if (isset($_POST['procedure_acceleree'])) {
                $this->projects->process_fast = 1;
                $this->projects->update();

                $this->redirect(self::PAGE_NAME_FILES, \projects_status::COMPLETUDE_ETAPE_3);
            } else {
                // @todo change person recieving the email from client or prescripteur if there is one

                /*
                // ENVOI DU MAIL CONFIRMATION INSCRIPTION
                // Recuperation du modele de mail
                $this->mails_text->get('confirmation-depot-de-dossier', 'lang = "' . $this->language . '" AND type');

                $surl  = $this->surl;
                $url   = $this->lurl;
                $login = $this->clients->email;
                //$mdp = $lemotdepasse;

                $this->settings->get('Facebook', 'type');
                $lien_fb = $this->settings->value;

                $this->settings->get('Twitter', 'type');
                $lien_tw = $this->settings->value;

                $varMail = array(
                    'surl'     => $surl,
                    'url'      => $url,
                    'password' => $lemotdepasse,
                    'lien_fb'  => $lien_fb,
                    'lien_tw'  => $lien_tw
                );

                $tabVars = $this->tnmp->constructionVariablesServeur($varMail);

                $sujetMail = strtr(utf8_decode($this->mails_text->subject), $tabVars);
                $texteMail = strtr(utf8_decode($this->mails_text->content), $tabVars);
                $exp_name  = strtr(utf8_decode($this->mails_text->exp_name), $tabVars);

                $this->email = $this->loadLib('email', array());
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->setSubject(stripslashes($sujetMail));
                $this->email->setHTMLBody(stripslashes($texteMail));



                // @todo supprimer les chiffres en fin d'email



                if ($this->Config['env'] == 'prod') {
                    Mailer::sendNMP($this->email, $this->mails_filer, $this->mails_text->id_textemail, $this->clients->email, $tabFiler);
                    $this->tnmp->sendMailNMP($tabFiler, $varMail, $this->mails_text->nmp_secure, $this->mails_text->id_nmp, $this->mails_text->nmp_unique, $this->mails_text->mode);
                } else {
                    $this->email->addRecipient(trim($this->clients->email));
                    Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                }

                // ENVOI DU MAIL NOTIFICATION INSCRIPTION
                // destinataire
                $this->settings->get('Adresse notification inscription emprunteur', 'type');
                $destinataire = $this->settings->value;

                // Recuperation du modele de mail
                $this->mails_text->get('notification-depot-de-dossier', 'lang = "' . $this->language . '" AND type');

                $surl         = $this->surl;
                $url          = $this->lurl;
                $nom_societe  = utf8_decode($this->companies->name);
                $montant_pret = $this->projects->amount;
                $lien         = $this->aurl . '/emprunteurs/edit/' . $this->clients->id_client;

                $sujetMail = htmlentities($this->mails_text->subject);
                eval("\$sujetMail = \"$sujetMail\";");

                $texteMail = $this->mails_text->content;
                eval("\$texteMail = \"$texteMail\";");

                $exp_name = $this->mails_text->exp_name;
                eval("\$exp_name = \"$exp_name\";");

                $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                $this->email = $this->loadLib('email', array());
                $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                $this->email->addRecipient(trim($destinataire));

                $this->email->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
                $this->email->setHTMLBody($texteMail);
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                */

                $this->clients->status = 1;
                $this->clients->update();

                $this->redirect(self::PAGE_NAME_THANK_YOU, \projects_status::A_TRAITER);
            }
        }
    }

    public function _prospect()
    {
        $this->page = 'prospect';

        $this->checkProjectHash(self::PAGE_NAME_PROSPECT);

        $this->lng['depot-de-dossier'] = $this->ln->selectFront('depot-de-dossier', $this->language, $this->App);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-prospect'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-prospect'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-prospect'];

        $this->settings->get('Lien conditions generales depot dossier', 'type');
        $this->lienConditionsGenerales = $this->settings->value;

        // load date for form (client, company, project and prescripteur
        // Form depot de dossier etape 2
        if (isset($_POST['send_form_coordonnees'])) {
            $bForm_ok = true;

            if (! isset($_POST['sex_representative']) || $_POST['sex_representative'] == '') {
                $bForm_ok = false;
            }
            if (! isset($_POST['nom_representative']) || $_POST['nom_representative'] == '' || $_POST['nom_representative'] == $this->lng['etape2']['nom']) {
                $bForm_ok = false;
            }
            if (! isset($_POST['prenom_representative']) || $_POST['prenom_representative'] == '' || $_POST['prenom_representative'] == $this->lng['etape2']['prenom']) {
                $bForm_ok = false;
            }
            if (! isset($_POST['portable_representative']) ||
                $_POST['portable_representative'] == '' ||
                $_POST['portable_representative'] == $this->lng['etape2']['telephone'] ||
                strlen($_POST['portable_representative']) < 9 ||
                strlen($_POST['portable_representative']) > 14
            ) {
                $bForm_ok = false;
            }
            if (! isset($_POST['fonction_representative']) || $_POST['fonction_representative'] == '' || $_POST['fonction_representative'] == $this->lng['etape2']['fonction']) {
                $bForm_ok = false;
            }
            if (! isset($_POST['email_representative']) ||
                $_POST['email_representative'] == '' ||
                $_POST['email_representative'] == $this->lng['etape2']['email'] ||
                $this->ficelle->isEmail($_POST['email_representative']) == false ||
                $_POST['email_representative'] != $_POST['conf_email_representative']
            ) {
                $bForm_ok = false;
            }

            $this->clients->civilite = $_POST['sex_representative'];
            $this->clients->nom      = $_POST['nom_representative'];
            $this->clients->prenom   = $_POST['prenom_representative'];
            $this->clients->fonction = $_POST['fonction_representative'];
            $this->clients->mobile   = $_POST['portable_representative'];
            $this->clients->email    = $_POST['email_representative'];

            if (! isset($_POST['raison-sociale']) || $_POST['raison-sociale'] == '' || $_POST['raison-sociale'] == $this->lng['etape2']['raison-sociale']) {
                $bForm_ok = false;
            }
            $this->companies->name = $_POST['raison-sociale'];


            if (isset($_POST['gerant']) && $_POST['gerant'] == 3) {
                if (! isset($_POST['gender_prescripteur']) || $_POST['gender_prescripteur'] == '') {
                    $bForm_ok = false;
                }
                if (! isset($_POST['prescripteur_nom']) || $_POST['prescripteur_nom'] == '' || $_POST['prescripteur_nom'] == $this->lng['etape2']['nom']) {
                    $bForm_ok = false;
                }
                if (! isset($_POST['prescripteur_prenom']) || $_POST['prescripteur_prenom'] == '' || $_POST['prescripteur_prenom'] == $this->lng['etape2']['prenom']) {
                    $bForm_ok = false;
                }

                if (! isset($_POST['prescripteur_email']) ||
                    $_POST['prescripteur_email'] == '' ||
                    $_POST['prescripteur_email'] == $this->lng['etape2']['email'] ||
                    $this->ficelle->isEmail($_POST['prescripteur_email']) == false ||
                    $_POST['prescripteur_email'] != $_POST['prescripteur_conf_email'] ||
                    $this->clients->existEmail($_POST['prescripteur_email']) == false
                ) {
                    $bForm_ok = false;
                }

                if (! isset($_POST['prescripteur_phone']) ||
                    $_POST['prescripteur_phone'] == '' ||
                    $_POST['prescripteur_phone'] == $this->lng['etape2']['telephone'] ||
                    strlen($_POST['prescripteur_phone']) < 9 ||
                    strlen($_POST['prescripteur_phone']) > 14
                ) {
                    $bForm_ok = false;
                }

                $this->prescripteurs->civilite = $_POST['gender_prescripteur'];
                $this->prescripteurs->nom      = $_POST['prescripteur_nom'];
                $this->prescripteurs->prenom   = $_POST['prescripteur_prenom'];
                $this->prescripteurs->mobile   = $_POST['prescripteur_phone'];
                $this->prescripteurs->email    = $_POST['prescripteur_email'];

            } // end if prescripteur

            if ($bForm_ok) {

                if ($_POST['gerant'] == 3) {
                    if (is_numeric($this->prescripteurs->id_prescripteur)) {
                        $this->prescripteurs->update();
                    } else {
                        $this->prescripteurs->id_prescripteur = $this->prescripteurs->create();
                    }
                    $this->projects->id_prescripteur = $this->prescripteurs->id_prescripteur;
                }

                // clients
                $this->clients->id_langue = 'fr';
                $this->clients->slug      = $this->bdd->generateSlug($this->clients->prenom . '-' . $this->clients->nom);

                // l'email facture est la meme que l'email client a la creation
                $this->companies->email_facture = $this->clients->email;

                // On fait une mise à jour
                $this->clients->update();
                $this->companies->update();
                $this->companies_details->update();
                $this->projects->update();
                $this->prescripteurs->update();

                $this->redirect(self::PAGE_NAME_THANK_YOU, \projects_status::PAS_3_BILANS);
            }
        }
    }

    public function _fichiers()
    {
        $this->page = 'fichiers';

        $this->checkProjectHash(self::PAGE_NAME_FILES);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-fichiers'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-fichiers'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-fichiers'];

        // @todo use trads for Types of files
        $this->aAttachmentTypes = $this->attachment_type->getAllTypesForProjects();

        if (isset($_SESSION['forms']['depot-de-dossier-fichiers'])) {
            $this->aForm = $_SESSION['forms']['depot-de-dossier-fichiers'];
            unset($_SESSION['forms']['depot-de-dossier-fichiers']);
        }

        if (false === empty($_POST) || false === empty($_FILES)) {
            foreach (array_keys($_FILES) as $iAttachmentType) {
                $this->uploadAttachment($iAttachmentType, $iAttachmentType);
            }

            if (true === $this->error_fichier) {
                $_SESSION['forms']['depot-de-dossier-fichiers']['errors']['files'] = true;
            } else {
                $_SESSION['forms']['depot-de-dossier-fichiers']['success']['files'] = true;
            }

            $this->redirect(self::PAGE_NAME_FILES, \projects_status::A_TRAITER);
        }
    }

    public function _merci()
    {
        $this->checkProjectHash(self::PAGE_NAME_THANK_YOU);

        $this->lng['depot-de-dossier'] = $this->ln->selectFront('depot-de-dossier', $this->language, $this->App);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-merci'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-merci'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-merci'];

        // the idea is to display exactly the same contact form as in the contact section of the page.

        //all data that is needed for the form, just as it is called in the root controller
        //Recuperation des element de traductions
        $this->lng['contact']  = $this->ln->selectFront('contact', $this->language, $this->App);
        $this->demande_contact = $this->loadData('demande_contact');

        foreach ($this->tree_elements->select('id_tree = 47 AND id_langue = "' . $this->language . '"') as $elt) {
            $this->elements->get($elt['id_element']);
            $this->content[$this->elements->slug]    = $elt['value'];
            $this->complement[$this->elements->slug] = $elt['complement'];
        }

        $this->breadCrumb   = $this->tree->getBreadCrumb(47, $this->language);
        $this->nbBreadCrumb = count($this->breadCrumb);

        // @todo get rid of the contact messages

        if (isset($_POST['send_form_contact'])) {
            include $this->path . 'apps/default/controllers/root.php';

            $oCommand = new Command('root', '_default', array(), $this->language);
            $oRoot    = new rootController($oCommand, $this->Config, 'default');
            $oRoot->contact();
            $this->confirmation = $this->lng['contact']['confirmation'];
        }
    }

    public function _nok()
    {
        $this->checkProjectHash(self::PAGE_NAME_NOK);

        $this->lng['depot-de-dossier-nok'] = $this->ln->selectFront('depot-de-dossier-nok', $this->language, $this->App);

        $this->meta_title       = $this->lng['depot-de-dossier-header']['meta-title-nok'];
        $this->meta_description = $this->lng['depot-de-dossier-header']['meta-description-nok'];
        $this->meta_keywords    = $this->lng['depot-de-dossier-header']['meta-keywords-nok'];

        switch ($this->projects->retour_altares) {
            case '1': // Etablissement Inactif
            case '7': // SIREN inconnu
                $this->sErrorMessage = $this->lng['depot-de-dossier-nok']['no-siren'];
                break;
            case '2': // Etablissement sans RCS
                $this->sErrorMessage = $this->lng['depot-de-dossier-nok']['no-rcs'];
                break;
            case '5': // Fonds Propres Négatifs
            case '6': // EBE Négatif
                $this->sErrorMessage = $this->lng['depot-de-dossier-nok']['rex-nega'];
                break;
            case '8':
                if ($this->projects_status->status == \projects_status::PAS_3_BILANS) {
                    $this->sErrorMessage = $this->lng['depot-de-dossier-nok']['pas-3-bilans'];
                    break;
                }
            case '3': // Procédure Active
            case '4': // Bilan de plus de 450 jours
            case '9': // bilan sup 450 jours
            default:
                $this->sErrorMessage = $this->lng['depot-de-dossier-nok']['contenu-non-eligible'];
                break;
        }
    }

    /**
     * @param string $sFieldName
     * @param integer $iAttachmentType
     * @return bool
     */
    private function uploadAttachment($sFieldName, $iAttachmentType)
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

        $resultUpload = false;
        if (isset($_FILES[$sFieldName]['name']) && $aFileInfo = pathinfo($_FILES[$sFieldName]['name'])) {
            $sFileName    = $aFileInfo['filename'] . '_' . $this->projects->id_project;
            $resultUpload = $this->attachmentHelper->upload($this->projects->id_project, attachment::PROJECT, $iAttachmentType, $sFieldName, $this->upload, $sFileName);
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
            $this->clients_prescripteur->get($this->prescripteurs->id_client);
        }

        $this->projects_status->getLastStatut($this->projects->id_project);

        switch ($this->projects_status->status) {
            case \projects_status::PAS_3_BILANS:
            case \projects_status::NOTE_EXTERNE_FAIBLE:
                if (false === in_array($sPage, array(self::PAGE_NAME_NOK, self::PAGE_NAME_PROSPECT, self::PAGE_NAME_THANK_YOU))) {
                    $this->redirect(self::PAGE_NAME_NOK);
                }
                break;
            case \projects_status::COMPLETUDE_ETAPE_2:
                if ($sPage !== self::PAGE_NAME_STEP_2) {
                    $this->redirect(self::PAGE_NAME_STEP_2);
                }
                break;
            case \projects_status::COMPLETUDE_ETAPE_3:
                if ($sPage !== self::PAGE_NAME_STEP_3) {
                    $this->redirect(self::PAGE_NAME_STEP_3);
                }
                break;
            case \projects_status::A_TRAITER:
            case \projects_status::EN_ATTENTE_PIECES:
                if (1 == $this->projects->process_fast && $sPage !== self::PAGE_NAME_FILES) {
                    $this->redirect(self::PAGE_NAME_FILES);
                } elseif (0 == $this->projects->process_fast && $sPage !== self::PAGE_NAME_THANK_YOU) {
                    $this->redirect(self::PAGE_NAME_THANK_YOU);
                }
                break;
            case \projects_status::ABANDON:
            default: // Should correspond to "Revue analyste" and above
                if ($sPage !== self::PAGE_NAME_THANK_YOU) {
                    $this->redirect(self::PAGE_NAME_THANK_YOU);
                }
                break;
        }
    }

    /**
     * Redirect to corresponding page and update status
     * @param $sPage          string Page to redirect to
     * @param $iProjectStatus int    Project status
     */
    private function redirect($sPage, $iProjectStatus = null)
    {
        if (false === is_null($iProjectStatus)) {
            $this->projects_status_history->addStatus(-2, $iProjectStatus, $this->projects->id_project);
        }

        header('Location: ' . $this->lurl . '/depot_de_dossier/' . $sPage . '/' . $this->projects->hash);
        die;
    }

    private function removeEmailSuffix($sEmail)
    {
        return preg_replace('/^(.+)-[0-9]+$/', '$1', $sEmail);
    }
}
