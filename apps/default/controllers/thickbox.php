<?php

class thickboxController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;
    }

    public function _openTraduc()
    {
        $this->ln = $this->loadData('textes');
        $this->ln->get($this->params[0], 'id_texte');
    }

    public function _pop_up_upload_particulier()
    {
        $this->clients = $this->loadData('clients');

        //Recuperation des element de traductions
        $this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2', $this->language, $this->App);

        // On recupere les client
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash')) {

        }
    }

    public function _pop_up_upload_particulier_modif()
    {
        $this->clients = $this->loadData('clients');

        //Recuperation des element de traductions
        $this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2', $this->language, $this->App);

        // On recupere les client
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client')) {

        }
    }

    public function _pop_up_upload_company()
    {
        $this->clients   = $this->loadData('clients');
        $this->companies = $this->loadData('companies');

        //Recuperation des element de traductions
        $this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2', $this->language, $this->App);

        // On recupere les client
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'hash')) {
            $this->companies->get($this->clients->id_client, 'id_client_owner');
        }
    }

    public function _pop_up_upload_company_modif()
    {
        $this->clients   = $this->loadData('clients');
        $this->companies = $this->loadData('companies');

        //Recuperation des element de traductions
        $this->lng['etape2'] = $this->ln->selectFront('inscription-preteur-etape-2', $this->language, $this->App);

        // On recupere les client
        if (isset($this->params[0]) && $this->clients->get($this->params[0], 'id_client')) {
            $this->companies->get($this->clients->id_client, 'id_client_owner');
        }
    }

    public function _pop_up_mdp()
    {
        //Recuperation des element de traductions
        $this->lng['pop-up-mdp'] = $this->ln->selectFront('pop-up-mdp', $this->language, $this->App);

    }

    public function _pop_up_qs()
    {
        //Recuperation des element de traductions
        $this->lng['etape1']           = $this->ln->selectFront('inscription-preteur-etape-1', $this->language, $this->App);
        $this->lng['preteur-synthese'] = $this->ln->selectFront('preteur-synthese', $this->language, $this->App);
        if (! $this->clients->checkAccess()) {
            header('Location:' . $this->lurl);
            die;
        }
    }

    public function _pop_up_modifier()
    {
        $this->lng['create-project'] = $this->ln->selectFront('emprunteur-create-project', $this->language, $this->App);

        $this->projects = $this->loadData('projects');
        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {

        }
    }

    public function _pop_up_upload_mandat()
    {
        //Recuperation des element de traductions
        $this->lng['profile'] = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

        if (isset($this->params[0]) && $this->params[0] == 2) {
            $this->urlRedirect = $this->lurl . '/unilend_emprunteur/';
        } else {
            $this->urlRedirect = $this->lurl . '/profile/2';
        }
    }

    public function _pop_up_fast_pret()
    {
        //Recuperation des element de traductions
        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->projects = $this->loadData('projects');
        $this->bids     = $this->loadData('bids');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            // Pret min
            $this->settings->get('Pret min', 'type');
            $this->pretMin = $this->settings->value;

            // la sum des encheres
            $this->soldeBid = $this->bids->getSoldeBid($this->projects->id_project);
            $this->txLenderMax = '10.10';

            if ($this->soldeBid >= $this->projects->amount) {
                $this->lEnchereRate = $this->bids->select('id_project = ' . $this->projects->id_project, 'rate ASC,added ASC');
                $leSoldeE           = 0;
                foreach ($this->lEnchereRate as $k => $e) {
                    // on parcour les encheres jusqu'au montant de l'emprunt
                    if ($leSoldeE < $this->projects->amount) {
                        // le montant preteur (x100)
                        $amount = $e['amount'];

                        // le solde total des encheres
                        $leSoldeE += ($e['amount'] / 100);
                        $this->txLenderMax = $e['rate'];
                    }
                }
            }

            // on génère un token
            $this->tokenBid       = sha1('tokenBid-' . time() . '-' . $this->clients->id_client);
            $_SESSION['tokenBid'] = $this->tokenBid;
        }
    }

    public function _pop_valid_pret()
    {
        //Recuperation des element de traductions
        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->projects = $this->loadData('projects');
        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            // Pret min
            $this->settings->get('Pret min', 'type');
            $this->pretMin = $this->settings->value;

            // on génère un token
            $this->tokenBid       = sha1('tokenBid-' . time() . '-' . $this->clients->id_client);
            $_SESSION['tokenBid'] = $this->tokenBid;
        }
    }

    public function _pop_valid_pret_mobile()
    {
        //Recuperation des element de traductions
        $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

        $this->projects = $this->loadData('projects');
        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            // Pret min
            $this->settings->get('Pret min', 'type');
            $this->pretMin = $this->settings->value;

            // on génère un token
            $this->tokenBid       = sha1('tokenBid-' . time() . '-' . $this->clients->id_client);
            $_SESSION['tokenBid'] = $this->tokenBid;
        }
    }

    public function _pop_up_alerte_retrait()
    {
        if (! $this->clients->checkAccess()) {
            header('Location:' . $this->lurl);
            die;
        }

        //Recuperation des element de traductions
        $this->lng['preteur-alimentation'] = $this->ln->selectFront('preteur-alimentation', $this->language, $this->App);
        $this->clients_status              = $this->loadData('clients_status');

        $this->clients_status->getLastStatut($this->clients->id_client);
    }

    public function _pop_up_cgv()
    {
        $this->blocs->get('cgv', 'slug');
        $lElements = $this->blocs_elements->select('id_bloc = ' . $this->blocs->id_bloc . ' AND id_langue = "' . $this->language . '"');
        foreach ($lElements as $b_elt) {
            $this->elements->get($b_elt['id_element']);
            $this->bloc_cgv[$this->elements->slug]           = $b_elt['value'];
            $this->bloc_cgvComplement[$this->elements->slug] = $b_elt['complement'];
        }

        $this->lng['preteur-profile'] = $this->ln->selectFront('preteur-profile', $this->language, $this->App);

        if (in_array($this->clients->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
            $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
            $this->lienConditionsGenerales_header = $this->settings->value;
        } else {
            $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
            $this->lienConditionsGenerales_header = $this->settings->value;
        }

        $listeAccept_header = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);

        if (in_array($this->lienConditionsGenerales_header, $listeAccept_header)) {
            $this->accept_ok_header     = true;
            $this->update_accept_header = false;
        } else {
            $this->accept_ok_header     = false;
            $this->update_accept_header = false;

            if ($listeAccept_header != false) {
                $this->update_accept_header = true;
                $this->iLoansCount          = 0;

                $this->settings->get('Date nouvelles CGV avec 2 mandats', 'type');
                $sNewTermsOfServiceDate = $this->settings->value;

                /** @var \lenders_accounts $oLenderAccount */
                $oLenderAccount = $this->loadData('lenders_accounts');
                $oLenderAccount->get($this->clients->id_client, 'id_client_owner');

                /** @var \loans $oLoans */
                $oLoans            = $this->loadData('loans');
                $this->iLoansCount = $oLoans->counter('id_lender = ' . $oLenderAccount->id_lender_account . ' AND added < "' . $sNewTermsOfServiceDate . '"');
            }
        }
    }

    public function _pop_up_offer_mobile()
    {
        if (false === $this->clients->checkAccess()) {
            header('Location: ' . $this->lurl);
            die;
        }

        $this->projects = $this->loadData('projects');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->lng['preteur-projets'] = $this->ln->selectFront('preteur-projets', $this->language, $this->App);

            $this->settings->get('Pret min', 'type');
            $this->pretMin = $this->settings->value;

            $this->soldeBid    = $this->bids->getSoldeBid($this->projects->id_project);
            $this->txLenderMax = $this->soldeBid >= $this->projects->amount ? $this->bids->getProjectMaxRate($this->projects) : 10;
        }
    }

    public function _pop_up_anticipation()
    {
        $this->lng['espace-emprunteur'] = $this->ln->selectFront('espace-emprunteur', $this->language, $this->App);
        $this->projects                 = $this->loadData('projects');

        if (is_numeric($this->params[0])) {
            $this->projects->get($this->params[0], 'id_project');
        } else {
            $this->projects->get($this->params[0], 'hash');
        }

        $this->fIR = $this->projects->getAverageInterestRate($this->projects->id_project);
    }

    public function _pop_up_nouveau_projet()
    {
        $this->settings->get('Durée des prêts autorisées', 'type');
        $this->dureePossible = empty($this->settings->value) ? array(24, 36, 48, 60) : explode(',', $this->settings->value);

        $this->settings->get('Somme à emprunter min', 'type');
        $this->sommeMin = $this->settings->value;

        $this->settings->get('Somme à emprunter max', 'type');
        $this->sommeMax = $this->settings->value;

        $this->lng['espace-emprunteur'] = $this->ln->selectFront('espace-emprunteur', $this->language, $this->App);
    }

    public function _signTaxExemption()
    {
        /** @var \clients client */
        $this->client = $this->loadData('clients');
        if (false === $this->client->checkAccess()) {
            header('Location:' . $this->lurl);
            die;
        }
        $this->client->get($_SESSION['client']['id_client']);

        /** @var \tax_type $taxType */
        $this->taxType = $this->loadData('tax_type');
        /** @var \settings $settings */
        $settings = $this->loadData('settings');
        /** @var \clients_adresses clients_adresses */
        $clientAadresse = $this->loadData('clients_adresses');
        /** @var \pays_v2 taxCountry */
        $taxCountry = $this->loadData('pays_v2');
        /** @var \lender_tax_exemption $lenderTaxExemption */
        $lenderTaxExemption = $this->loadData('lender_tax_exemption');

        $clientAadresse->get($this->client->id_client, 'id_client');

        $this->fiscalAddress['address'] = (false === empty($clientAadresse->adresse_fiscal)) ? $clientAadresse->adresse_fiscal : $clientAadresse->adresse1 . ' ' . $clientAadresse->adresse2 . ' ' . $clientAadresse->adresse3;
        $this->fiscalAddress['zipCode'] = (false === empty($clientAadresse->cp_fiscal)) ? $clientAadresse->cp_fiscal : $clientAadresse->cp;
        $this->fiscalAddress['city']    = (false === empty($clientAadresse->ville_fiscal)) ? $clientAadresse->ville_fiscal : $clientAadresse->ville;

        if (false === empty($this->clientAadresse->id_pays_fiscal)) {
            $taxCountry->get($clientAadresse->id_pays_fiscal, 'id_pays');
        } else {
            $taxCountry->get($clientAadresse->id_pays, 'id_pays');
        }
        $this->fiscalAddress['country'] = $taxCountry->fr;

        $this->taxType->get(\tax_type::TYPE_INCOME_TAX);

        $this->currentYear = date('Y', time());
        $this->lastYear    = $this->currentYear - 1;
        $this->nextYear    = $this->currentYear + 1;

        $this->taxExemptionRequestLimitDate = strftime('%d %B %Y', $lenderTaxExemption->getTaxExemptionDateRange()['taxExemptionRequestLimitDate']->getTimestamp());
        $settings->get('incomeTaxReferenceSingleAmount', 'type');
        $this->incomeTaxReferenceSingleAmount = $settings->value;
        $settings->get('incomeTaxReferenceCommonAmount', 'type');
        $this->incomeTaxReferenceCommonAmount = $settings->value;

        $this->lng['lender-dashboard'] = $this->ln->selectFront('lender-dashboard', $this->language, $this->App);
    }
}
