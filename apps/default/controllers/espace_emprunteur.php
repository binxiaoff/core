<?php


class espace_emprunteurController extends Bootstrap
{

    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        if ($command->Function !== 'securite') {

            $this->setHeader('header_account');

            if (!$this->clients->checkAccess()) {
                header('Location:' . $this->lurl);
                die;
            }
            $this->companies->get($_SESSION['client']['id_client'], 'id_client_owner');
            $aAllCompanyProjects = $this->companies->getProjectsForCompany($this->companies->id_company);

            if ((int)$aAllCompanyProjects[0]['project_status'] >= projects_status::A_TRAITER && (int)$aAllCompanyProjects[0]['project_status'] <= projects_status::PREP_FUNDING) {
                header('Location:' . $this->url . '/depot_de_dossier/fichiers/' . $aAllCompanyProjects[0]['hash']);
                die;
            }
        }

        $this->settings                 = $this->loadData('settings');
        $this->lng['espace-emprunteur'] = $this->ln->selectFront('espace-emprunteur', $this->language, $this->App);
        $this->projects                 = $this->loadData('projects');

        $this->clients->get($_SESSION['client']['id_client']);
        $this->companies->get($this->clients->id_client, 'id_client_owner');

        $this->dates = $this->loadLib('dates');

    }

    public function _default()
    {
        header('Location:' . $this->lurl . '/espace_emprunteur/projets');
        die;
    }

    public function _securite()
    {
        $this->loadCss('default/preteurs/new-style');

        $oTemporary_links = $this->loadData('temporary_links_login');

        if (isset($this->params[0])) {

            $oTemporary_links->get($this->params[0], 'token');

            $oNow         = new \datetime();
            $oLinkExpires = new \datetime($oTemporary_links->expires);


            if ($oLinkExpires <= $oNow) {

                $this->bLinkExpired = true;

            } else {

                $oTemporary_links->accessed = $oNow->format('Y-m-d H:i:s');
                $oTemporary_links->update();

                $this->clients->get($oTemporary_links->id_client);

                if (isset($_POST['form_secret_question'])) {

                    if (empty($_POST['pass'])) {
                        $_SESSION['forms']['mdp_question_emprunteur']['errors']['pass'] = true;
                    }
                    if (empty($_POST['pass2'])) {
                        $_SESSION['forms']['mdp_question_emprunteur']['errors']['pass2'] = true;

                    }
                    if (empty($_POST['secret-question']) || $_POST['secret-question'] == $this->lng['espace-emprunteur']['question-secrete']) {
                        $_SESSION['forms']['mdp_question_emprunteur']['errors']['secret-question'] = true;

                    }
                    if (empty($_POST['secret-response']) || $_POST['secret-response'] == $this->lng['espace-emprunteur']['response']) {
                        $_SESSION['forms']['mdp_question_emprunteur']['errors']['secret-response'] = true;

                    }

                    if (false === empty($_SESSION['forms']['mdp_question_emprunteur']['errors'])) {
                        $_SESSION['forms']['mdp_question_emprunteur']['values'] = $_POST;
                        header('Location: ' . $this->lurl . '/espace_emprunteur' . $this->params[0]);
                        die;

                    } else {

                        $this->clients->password         = md5($_POST['pass']);
                        $this->clients->secrete_question = $_POST['secret-question'];
                        $this->clients->secrete_reponse  = md5($_POST['secret-response']);

                        $this->clients->update();

                        header('Location: ' . $this->lurl);
                    }
                }
            }
        } else {
            header('Location: ' . $this->lurl);
        }

    }


    public function _contact()
    {

        $this->lng['contact'] = $this->ln->selectFront('contact', $this->language, $this->App);

        $oRequestSubjects       = $this->loadData('contact_request_subjects');
        $this->aRequestSubjects = $oRequestSubjects->getAllSubjects($this->language);


        foreach ($this->tree_elements->select('id_tree = 47 AND id_langue = "' . $this->language . '"') as $elt) {
            $this->elements->get($elt['id_element']);
            $this->content[ $this->elements->slug ] = $elt['value'];
        }

        $aContactRequest = isset($_SESSION['forms']['contact-emprunteur']['values']) ? $_SESSION['forms']['contact-emprunteur']['values'] : array();
        $this->aErrors   = isset($_SESSION['forms']['contact-emprunteur']['errors']) ? $_SESSION['forms']['contact-emprunteur']['errors'] : array();

        $this->aContactRequest = array(
            'siren'     => isset($aContactRequest['siren']) ? $aContactRequest['siren'] : $this->companies->siren,
            'company'   => isset($aContactRequest['company']) ? $aContactRequest['company'] : $this->companies->name,
            'prenom'    => isset($aContactRequest['prenom']) ? $aContactRequest['prenom'] : '',
            'nom'       => isset($aContactRequest['nom']) ? $aContactRequest['nom'] : '',
            'email'     => isset($aContactRequest['email']) ? $aContactRequest['email'] : '',
            'telephone' => isset($aContactRequest['telephone']) ? $aContactRequest['telephone'] : '',
            'demande'   => isset($aContactRequest['demande']) ? $aContactRequest['demande'] : '',
            'message'   => isset($aContactRequest['message']) ? $aContactRequest['message'] : ''
        );

        unset($_SESSION['forms']['contact-emprunteur']);

        if (isset($_POST['send_form_contact'])) {
            $this->contactForm();
        }
    }

    private function contactForm()
    {
        if (empty($_POST['siren'])) {
            $_SESSION['forms']['contact-emprunteur']['errors']['siren'] = true;
        }
        if (empty($_POST['company'])) {
            $_SESSION['forms']['contact-emprunteur']['errors']['company'] = true;
        }
        if (empty($_POST['prenom'])) {
            $_SESSION['forms']['contact-emprunteur']['errors']['prenom'] = true;
        }
        if (empty($_POST['nom'])) {
            $_SESSION['forms']['contact-emprunteur']['errors']['nom'] = true;
        }
        if (empty($_POST['telephone']) || false === $this->ficelle->isMobilePhoneNumber($_POST['telephone'], $this->language)) {
            $_SESSION['forms']['contact-emprunteur']['errors']['telephone'] = true;
        }
        if (empty($_POST['email']) || false === $this->ficelle->isEmail($_POST['email'])) {
            $_SESSION['forms']['contact-emprunteur']['errors']['email'] = true;
        }
        if (empty($_POST['demande'])) {
            $_SESSION['forms']['contact-emprunteur']['errors']['demande'] = true;
        }
        if (empty($_POST['message'])) {
            $_SESSION['forms']['contact-emprunteur']['errors']['message'] = true;
        }

        if (isset($_FILES) && empty($_FILES) === false) {

            $oUpload = new \upload;
            $oUpload->setUploadDir($this->path, 'protected/contact/');
            $oUpload->doUpload('attachement', $new_name = '', $erase = false);
            $sFilePath = $this->path . 'protected/contact/' . $oUpload->getName();

        }

        if (false === empty($_SESSION['forms']['contact-emprunteur']['errors'])) {
            $_SESSION['forms']['contact-emprunteur']['values'] = $_POST;
            header('Location: ' . $this->lurl . '/espace_emprunteur/contact');
            die;

        } else {

            $this->contactEmailClient();

            //Email Unilend
            $this->mails_text->get('notification-demande-de-contact-emprunteur', 'lang = "' . $this->language . '" AND type');
            $this->settings->get('Adresse emprunteur', 'type');

            $aReplacements = array(
                '[siren]'     => $_POST['siren'],
                '[company]'   => $_POST['company'],
                '[prenom]'    => $_POST['prenom'],
                '[nom]'       => $_POST['nom'],
                '[email]'     => $_POST['email'],
                '[telephone]' => $_POST['telephone'],
                '[demande]'   => $this->aRequestSubjects[ $_POST['demande'] ]['label'],
                '[message]'   => $_POST['message'],
                '[SURL]'      => $this->surl
            );

            $this->email = $this->loadLib('email', array());
            $this->email->setFrom($this->mails_text->exp_email, utf8_decode($this->mails_text->exp_name));
            $this->email->addRecipient(trim($this->settings->value));
            $this->email->setReplyTo(
                utf8_decode($_POST['email']),
                utf8_decode($_POST['nom']) . ' ' . utf8_decode($_POST['prenom']));
            $this->email->setSubject(stripslashes(utf8_decode($this->mails_text->subject)));

            $this->email->setHTMLBody(str_replace(array_keys($aReplacements), array_values($aReplacements), $this->mails_text->content));

            if (empty($sFilePath) === false) {
                $this->email->attach($sFilePath);
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                @unlink($sFilePath);
            } else {
                Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
            }
            $this->bSuccessMessage = true;
        }

    }


    public function _profil()
    {

    }


    public function _operations()
    {
        $this->aClientsProjects = $this->projects->select('id_company = ' . $this->companies->id_company);

        $this->documents();

        $oDateTimeStart              = new \datetime('NOW - 1 month');
        $this->sDisplayDateTimeStart = $oDateTimeStart->format('d/m/Y');

        $oDateTimeEnd              = new \datetime('NOW');
        $this->sDisplayDateTimeEnd = $oDateTimeEnd->format('d/m/Y');


    }

    private function documents()
    {

        $oProjectsPouvoir = $this->loadData('projects_pouvoir');
        $oClientsMandat   = $this->loadData('clients_mandats');

        foreach ($this->aClientsProjects as $iKey => $aProject) {

            $this->aClientsProjects[ $iKey ]['pouvoir'] = $oProjectsPouvoir->select('id_project = ' . $aProject['id_project']);
            $this->aClientsProjects[ $iKey ]['mandat']  = $oClientsMandat->select('id_project = ' . $aProject['id_project'], 'updated DESC');

            foreach ($this->aClientsProjects[ $iKey ]['mandat'] as $iMandatKey => $aMandat) {

                switch ($aMandat['status']) {
                    case clients_mandats::STATUS_EN_COURS:
                        $this->aClientsProjects[ $iKey ]['mandat'][ $iMandatKey ]['status-trad'] = 'mandat-en-cours';
                        break;
                    case clients_mandats::STATUS_SIGNE:
                        $this->aClientsProjects[ $iKey ]['mandat'][ $iMandatKey ]['status-trad'] = 'mandat-signe';
                        break;
                    case clients_mandats::STATUS_ANNULE:
                    case clients_mandats::STATUS_FAIL:
                        $this->aClientsProjects[ $iKey ]['mandat'][ $iMandatKey ]['status-trad'] = 'void';
                        break;
                }
            }
        }


        $oFactures        = $this->loadData('factures');
        $aClientsInvoices = $oFactures->select('id_company = ' . $this->companies->id_company);

        foreach ($aClientsInvoices as $iKey => $aInvoice) {

            switch ($aInvoice['type_commission']) {
                case factures::TYPE_COMMISSION_FINANCEMENT :
                    $aClientsInvoices[ $iKey ]['url'] = $this->url . '/pdf/facture_EF/' . $this->clients->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
                case factures::TYPE_COMMISSION_REMBOURSEMENT:
                    $aClientsInvoices[ $iKey ]['url'] = $this->url . '/pdf/facture_ER/' . $this->clients->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
            }
        }
        $this->aClientsInvoices = $aClientsInvoices;
    }

    public function _projets()
    {
        $this->aProjectsPreFunding  = $this->getProjectsPreFunding();
        $this->aProjectsFunding     = $this->getProjectsFunding();
        $this->aProjectsPostFunding = $this->getProjectsPostFunding();

        if (isset($_POST['confirm_cloture_anticipation'])) {

            $oProject = $this->loadData('projects');

            if (is_numeric($this->params[0])) {
                $oProject->get($this->params[0], 'id_project');
            } else {
                $oProject->get($this->params[0], 'hash');
            }

            if ($oProject->id_company === $this->companies->id_company) {

                $oNewClosureDate = new DateTime();
                $oNewClosureDate->modify("+5 minutes");

                $oProject->date_retrait_full = $oNewClosureDate->format('Y-m-d H:i:s');
                $oProject->date_retrait      = $oNewClosureDate->format('Y-m-d');
                $oProject->update();

                header('Location:' . $this->lurl . '/espace_emprunteur/projets');
                die;
            }
        }

        if (isset($_POST['valider_demande_projet'])) {

            if (empty($_POST['montant'])) {
                $_SESSION['forms']['nouvelle-demande']['errors']['montant'] = true;
            }
            if (empty($_POST['duree'])) {
                $_SESSION['forms']['nouvelle-demande']['errors']['duree'] = true;
            }
            if (empty($_POST['montant'])) {
                $_SESSION['forms']['nouvelle-demande']['errors']['commentaires'] = true;
            }

            if (empty ($_SESSION['forms']['nouvelle-demande']['errors'])) {

                $oClients = $this->loadData('clients');
                $oClients->get($_SESSION['client']['id_client']);

                $oCompanies = $this->loadData('companies');
                $oCompanies->get($oClients->id_client, 'id_client_owner');

                $oProject = $this->loadData('projects');

                $oProject->id_company                           = $oCompanies->id_company;
                $oProject->amount                               = str_replace(array(',', ' '), array('.', ''), $_POST['montant']);
                $oProject->ca_declara_client                    = 0;
                $oProject->resultat_exploitation_declara_client = 0;
                $oProject->fonds_propres_declara_client         = 0;
                $oProject->comments                             = $_POST['commentaires'];
                $oProject->period                               = $_POST['duree'];
                $oProject->create();

                $oProjectsStatusHistory = $this->loadData('projects_status_history');
                $oProjectsStatusHistory->addStatus(-2, \projects_status::A_TRAITER, $oProject->id_project);

                unset($_SESSION['forms']['nouvelle-demande']['errors']);

                header('Location:' . $this->lurl . '/espace_emprunteur/projets');
                die;
            }
        }
    }

    private function getProjectsPreFunding()
    {
        $aStatusPreFunding = array(\projects_status::REVUE_ANALYSTE,
            \projects_status::COMITE,
            \projects_status::REJET_ANALYSTE,
            \projects_status::REJET_COMITE,
            \projects_status::REJETE,
            \projects_status::PREP_FUNDING,
            \projects_status::A_FUNDER,
            \projects_status::A_TRAITER,
            \projects_status::EN_ATTENTE_PIECES
        );

        $aProjectsPreFunding = $this->companies->getProjectsForCompany($this->companies->id_company, $aStatusPreFunding);

        foreach ($aProjectsPreFunding as $iKey => $aProject) {

            switch ($aProject['project_status']) {
                case \projects_status::EN_ATTENTE_PIECES:
                case \projects_status::A_TRAITER:
                    $aProjectsPreFunding[ $iKey ]['project_status_label'] = 'en-attente-de-pieces';
                    break;
                case \projects_status::REVUE_ANALYSTE:
                case \projects_status::COMITE:
                    $aProjectsPreFunding[ $iKey ]['project_status_label'] = 'en-cours-d-etude';
                    break;
                case \projects_status::REJET_ANALYSTE:
                case \projects_status::REJET_COMITE:
                case \projects_status::REJETE:
                    $aProjectsPreFunding[ $iKey ]['project_status_label'] = 'refuse';
                    break;
                case \projects_status::PREP_FUNDING:
                case \projects_status::A_FUNDER:
                    $aProjectsPreFunding[ $iKey ]['project_status_label'] = 'en-attente-de-mise-en-ligne';
                    break;
            }
        }

        return $aProjectsPreFunding;
    }

    private function getProjectsFunding()
    {
        $aProjectsFunding = $this->companies->getProjectsForCompany($this->companies->id_company, \projects_status::EN_FUNDING);
        $oBids            = $this->loadData('bids');


        foreach ($aProjectsFunding as $iKey => $aProject) {
            $aProjectsFunding[ $iKey ]['AverageIR'] = $this->projects->calculateAvgInterestRate($aProject['id_project'], $aProject['project_status']);

            $iSumBids                                      = $oBids->getSoldeBid($aProject['id_project']);
            $aProjectsFunding[ $iKey ]['funding-progress'] = ((1 - (($aProject['amount'] - $iSumBids) / $aProject['amount']) * 100));
        }

        return $aProjectsFunding;
    }

    private function getProjectsPostFunding()
    {
        $aStatusPostFunding = array(\projects_status::REMBOURSE,
            \projects_status::REMBOURSEMENT,
            \projects_status::PROBLEME,
            \projects_status::RECOUVREMENT,
            \projects_status::DEFAUT,
            \projects_status::REMBOURSEMENT_ANTICIPE);


        $aProjectsPostFunding   = $this->companies->getProjectsForCompany($this->companies->id_company, $aStatusPostFunding);
        $oEcheanciersEmprunteur = $this->loadData('echeanciers_emprunteur');


        foreach ($aProjectsPostFunding as $iKey => $aProject) {
            $aProjectsPostFunding[ $iKey ]['AverageIR'] = $this->projects->calculateAvgInterestRate($aProject['id_project'], $aProject['project_status']);

            $aProjectsPostFunding[ $iKey ]['RemainingDueCapital'] = $this->calculateRemainingDueCapital($aProject['id_project']);

            $oClosingDate                                  = new \DateTime($aProject['date_retrait']);
            $aProjectsPostFunding[ $iKey ]['date_retrait'] = $oClosingDate->format('d-m-Y');

            $oEcheanciersEmprunteur->get($aProject['id_project'], 'ordre = 1 AND id_project');

            $aProjectsPostFunding[ $iKey ]['MonthlyPayment'] = (($oEcheanciersEmprunteur->montant + $oEcheanciersEmprunteur->commission + $oEcheanciersEmprunteur->tva) / 100);

            $oPaymentDate                                            = new \DateTime($oEcheanciersEmprunteur->date_echeance_emprunteur);
            $aProjectsPostFunding[ $iKey ]['DateNextMonthlyPayment'] = $oPaymentDate->format('d-m-Y');
        }

        return $aProjectsPostFunding;
    }

    private function calculateRemainingDueCapital($iProjectId)
    {
        $oEcheanciers = $this->loadData('echeanciers');

        $aEcheance      = $oEcheanciers->getLastOrder($iProjectId);
        $iEcheanceOrder = (isset($aEcheance)) ? $aEcheance['ordre'] + 1 : 1;

        return $oEcheanciers->reste_a_payer_ra($iProjectId, $iEcheanceOrder);
    }

    private function contactEmailClient()
    {
        $this->mails_text->get('demande-de-contact', 'lang = "' . $this->language . '" AND type');

        $this->settings->get('Facebook', 'type');
        $sFacebookURL = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $sTwitterURL = $this->settings->value;

        $aVariables = array(
            'surl'     => $this->surl,
            'url'      => $this->url,
            'prenom_c' => $_POST['prenom'],
            'projets'  => $this->lurl . '/' . $this->tree->getSlug(4, $this->language),
            'lien_fb'  => $sFacebookURL,
            'lien_tw'  => $sTwitterURL
        );

        $this->email = $this->loadLib('email', array());
        $this->email->setFrom($this->mails_text->exp_email, utf8_decode($this->mails_text->exp_name));
        $this->email->setSubject(stripslashes(utf8_decode($this->mails_text->subject)));
        $this->email->setHTMLBody(stripslashes(strtr(utf8_decode($this->mails_text->content), $this->tnmp->constructionVariablesServeur($aVariables))));

        $sRecipient = $_POST['email'];

        if ($this->Config['env'] == 'prod') {
            Mailer::sendNMP(
                $this->email,
                $this->mails_filer,
                $this->mails_text->id_textemail,
                $sRecipient,
                $aNMPResponse);
            $this->tnmp->sendMailNMP(
                $aNMPResponse,
                $aVariables,
                $this->mails_text->nmp_secure,
                $this->mails_text->id_nmp,
                $this->mails_text->nmp_unique,
                $this->mails_text->mode);
        } else {
            $this->email->addRecipient($sRecipient);
            Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
        }
    }

    public function _getCSVWithLenderDetails()
    {
        $this->projects->get($this->params[1], 'id_project');

        switch ($this->params[0]) {

            case 'l':
                $sFilename      = 'details_prets';
                $aColumnHeaders = array(utf8_decode('ID Préteur'), 'Nom ou Raison Sociale', utf8_decode('Prénom'), 'Mouvement', 'Montant', 'Date');
                $sType          = utf8_decode($this->lng['espace-emprunteur']['mouvement-deblocage-des-fonds']);
                $aData          = $this->projects->getLoansAndLendersForProject($this->projects->id_project);
                break;
            case 'e':
                $sFilename      = 'details_remboursements';
                $aColumnHeaders = array(utf8_decode('ID Préteur'), 'Nom ou Raison Sociale', utf8_decode('Prénom'), 'Mouvement', 'Montant', 'Capital', utf8_decode('Intérets'), 'Date');
                $sType          = utf8_decode($this->lng['espace-emprunteur']['mouvement-remboursement']);
                $aData          = $this->projects->getDuePaymentsAndLenders($this->projects->id_project, $this->params[2]);
                break;
            default:
                break;
        }

        foreach ($aData as $key => $row) {

            if (empty($row['name']) === false) {
                $aData[ $key ]['nom']    = $row['name'];
                $aData[ $key ]['prenom'] = null;
            }
            $aData[ $key ]['name'] = $sType;
            $aData[ $key ]['date'] = $this->dates->formatDate($row['date']);

            if (empty($row['amount']) === false) {
                $aData[ $key ]['amount'] = $row['amount'] / 100;
            }
        }

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename = ' . $sFilename . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $csvFile = fopen('php://output', 'w');
        fputcsv($csvFile, $aColumnHeaders);

        foreach ($aData as $row) {
            fputcsv($csvFile, $row);
        }

        exit();
    }

    public function _getCSVOperations()
    {
        $aBorrowerOperations = $this->clients->getDataForBorrowerOperations(
            $this->clients->id_client,
            $_SESSION['operations-filter']['projects'],
            $_SESSION['operations-filter']['start'],
            $_SESSION['operations-filter']['end'],
            $_SESSION['operations-filter']['transaction']
        );

        $sFilename      = 'operations';
        $aColumnHeaders = array(utf8_decode('Opération'), utf8_decode('Référence de projet'), utf8_decode('Date de l\'opération'), utf8_decode('Montant de l\'opération'), 'Dont TVA');


        foreach ($aBorrowerOperations as $aOperation) {

            $aData[] = array(
                utf8_decode($this->lng['espace-emprunteur'][ 'operations-type-' . $aOperation['type'] ]),
                $aOperation['id_project'],
                $this->dates->formatDateMysqltoShortFR($aOperation['date']),
                number_format($aOperation['montant'], 2, ',', ''),
                number_format($aOperation['tva'], 2, ',', '')
            );


        }
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename = ' . $sFilename . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $csvFile = fopen('php://output', 'w');
        fputcsv($csvFile, $aColumnHeaders);

        foreach ($aData as $row) {
            fputcsv($csvFile, $row);
        }
        exit();
    }

    public function _getPdfOperations()
    {
        include $this->path . '/apps/default/controllers/pdf.php';

        $oCommandPdf = new Command('pdf', 'setDisplay', $this->language);
        $oPdf        = new pdfController($oCommandPdf, $this->Config, 'default');

        $sPath          = $this->path . 'protected/operations_export_pdf/' . $this->clients->id_client . '/';
        $sNamePdfClient = 'operations_emprunteur_' . date('Y-m-d') . '.pdf';

        $oPdf->lng['espace-emprunteur']                 = $this->ln->selectFront('espace-emprunteur', $this->language, $this->App);
        $oPdf->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $oPdf->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);


        $oPdf->aBorrowerOperations = $this->clients->getDataForBorrowerOperations(
            $this->clients->id_client,
            $_SESSION['operations-filter']['projects'],
            $_SESSION['operations-filter']['start'],
            $_SESSION['operations-filter']['end'],
            $_SESSION['operations-filter']['transaction']
        );

        $oPdf->companies->get($this->clients->id_client, 'id_client_owner');

        $oPdf->setDisplay('operations_emprunteur_pdf_html');
        $oPdf->WritePdf($sPath . $sNamePdfClient, 'operations');
        $oPdf->ReadPdf($sPath . $sNamePdfClient, $sNamePdfClient);

    }


}