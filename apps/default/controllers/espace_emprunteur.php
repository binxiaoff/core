<?php

class espace_emprunteurController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        if ($command->Function !== 'securite') {
            $this->setHeader('header_account');
            $this->page = 'faq';

            if ( ! $this->clients->checkAccess()) {
                header('Location:' . $this->lurl);
                die;
            }

            $this->clients->get($_SESSION['client']['id_client']);
            $this->clients->checkAccessBorrower();
            $this->companies->get($_SESSION['client']['id_client'], 'id_client_owner');
            $aAllCompanyProjects = array_shift($this->companies->getProjectsForCompany($this->companies->id_company));

            if ((int)$aAllCompanyProjects['project_status'] >= projects_status::A_TRAITER && (int)$aAllCompanyProjects['project_status'] < projects_status::PREP_FUNDING) {
                header('Location:' . $this->url . '/depot_de_dossier/fichiers/' . $aAllCompanyProjects['hash']);
                die;
            }
        }

        $this->settings                 = $this->loadData('settings');
        $this->lng['espace-emprunteur'] = $this->ln->selectFront('espace-emprunteur', $this->language, $this->App);
        $this->projects                 = $this->loadData('projects');

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

        $oTemporary_links   = $this->loadData('temporary_links_login');
        $this->bLinkExpired = false;

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
                if (isset($_POST['form_mdp_question_emprunteur'])) {
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
                    } else {
                        $this->clients->password         = md5($_POST['pass']);
                        $this->clients->secrete_question = $_POST['secret-question'];
                        $this->clients->secrete_reponse  = md5($_POST['secret-response']);
                        $this->clients->status           = 1;
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
        $this->page = 'contact';
        $this->lng['contact']   = $this->ln->selectFront('contact', $this->language, $this->App);
        $oRequestSubjects       = $this->loadData('contact_request_subjects');
        $this->aRequestSubjects = $oRequestSubjects->getAllSubjects($this->language);

        foreach ($this->tree_elements->select('id_tree = 47 AND id_langue = "' . $this->language . '"') as $elt) {
            $this->elements->get($elt['id_element']);
            $this->content[$this->elements->slug] = $elt['value'];
        }

        $aContactRequest       = isset($_SESSION['forms']['contact-emprunteur']['values']) ? $_SESSION['forms']['contact-emprunteur']['values'] : array();
        $this->aErrors         = isset($_SESSION['forms']['contact-emprunteur']['errors']) ? $_SESSION['forms']['contact-emprunteur']['errors'] : array();
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

        $sFilePath = '';
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
            $this->settings->get('Adresse emprunteur', 'type');

            $aReplacements = array(
                '[siren]'     => $_POST['siren'],
                '[company]'   => $_POST['company'],
                '[prenom]'    => $_POST['prenom'],
                '[nom]'       => $_POST['nom'],
                '[email]'     => $_POST['email'],
                '[telephone]' => $_POST['telephone'],
                '[demande]'   => $this->aRequestSubjects[$_POST['demande']]['label'],
                '[message]'   => $_POST['message'],
                '[SURL]'      => $this->surl
            );

            /** @var \Unilend\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-demande-de-contact-emprunteur', $this->language, $aReplacements, false);
            $message->setTo(trim($this->settings->value));
            if (empty($sFilePath) === false) {
                $message->attach(Swift_Attachment::fromPath($sFilePath));
            }
            $mailer = $this->get('mailer');
            $mailer->send($message);

            @unlink($sFilePath);
            $this->bSuccessMessage = true;
        }
    }

    public function _profil()
    {
        $this->page = 'profil';

    }

    public function _operations()
    {
        $this->page = 'operations';

        $this->aClientsProjects      = $this->getProjectsPostFunding();

        $oDateTimeStart              = new \datetime('NOW - 1 month');
        $this->sDisplayDateTimeStart = $oDateTimeStart->format('d/m/Y');

        $oDateTimeEnd                = new \datetime('NOW');
        $this->sDisplayDateTimeEnd   = $oDateTimeEnd->format('d/m/Y');

        $this->documents();
    }

    private function documents()
    {
        $oProjectsPouvoir = $this->loadData('projects_pouvoir');
        $oClientsMandat   = $this->loadData('clients_mandats');

        foreach ($this->aClientsProjects as $iKey => $aProject) {
            $this->aClientsProjects[$iKey]['pouvoir'] = $oProjectsPouvoir->select('id_project = ' . $aProject['id_project']);
            $this->aClientsProjects[$iKey]['mandat']  = $oClientsMandat->select('id_project = ' . $aProject['id_project'], 'updated DESC');

            foreach ($this->aClientsProjects[$iKey]['mandat'] as $iMandatKey => $aMandat) {
                switch ($aMandat['status']) {
                    case \clients_mandats::STATUS_PENDING:
                        $this->aClientsProjects[$iKey]['mandat'][$iMandatKey]['status-trad'] = 'mandat-en-cours';
                        break;
                    case \clients_mandats::STATUS_SIGNED:
                    case \clients_mandats::STATUS_CANCELED:
                    case \clients_mandats::STATUS_FAILED:
                    case \clients_mandats::STATUS_ARCHIVED:
                    default:
                        $this->aClientsProjects[$iKey]['mandat'][$iMandatKey]['status-trad'] = 'void';
                        break;
                }
            }
        }

        $oInvoices        = $this->loadData('factures');
        $aClientsInvoices = $oInvoices->select('id_company = ' . $this->companies->id_company, 'date DESC');

        foreach ($aClientsInvoices as $iKey => $aInvoice) {
            switch ($aInvoice['type_commission']) {
                case factures::TYPE_COMMISSION_FINANCEMENT :
                    $aClientsInvoices[$iKey]['url'] = $this->url . '/pdf/facture_EF/' . $this->clients->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
                case factures::TYPE_COMMISSION_REMBOURSEMENT:
                    $aClientsInvoices[$iKey]['url'] = $this->url . '/pdf/facture_ER/' . $this->clients->hash . '/' . $aInvoice['id_project'] . '/' . $aInvoice['ordre'];
                    break;
            }
        }
        $this->aClientsInvoices = $aClientsInvoices;
    }

    public function _projets()
    {
        $this->page = 'projets';

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

                $_SESSION['cloture_anticipe'] = true;

                header('Location:' . $this->lurl . '/espace_emprunteur/projets');
                die;
            }
        }

        if (isset($_POST['valider_demande_projet'])) {
            unset($_SESSION['forms']['nouvelle-demande']);

            $this->settings->get('Somme à emprunter max', 'type');
            $fMaxAmount = $this->settings->value;

            $this->settings->get('Somme à emprunter min', 'type');
            $fMinAmount = $this->settings->value;

            if (empty($_POST['montant']) || $fMinAmount > $_POST['montant'] || $fMaxAmount < $_POST['montant']) {
                $_SESSION['forms']['nouvelle-demande']['errors']['montant'] = true;
            }
            if (empty($_POST['duree'])) {
                $_SESSION['forms']['nouvelle-demande']['errors']['duree'] = true;
            }
            if (empty($_POST['commentaires'])) {
                $_SESSION['forms']['nouvelle-demande']['errors']['commentaires'] = true;
            }
            if (empty($_SESSION['forms']['nouvelle-demande']['errors'])) {
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

                /** @var \Unilend\Service\ProjectManager $oProjectManager */
                $oProjectManager = $this->get('unilend.service.project_manager');
                $oProjectManager->addProjectStatus(\users::USER_ID_FRONT, \projects_status::A_TRAITER, $oProject);

                header('Location:' . $this->lurl . '/espace_emprunteur/projets');
                die;
            }
        }
    }

    private function getProjectsPreFunding()
    {
        $aStatusPreFunding = array(
            \projects_status::A_FUNDER,
            \projects_status::A_TRAITER,
            \projects_status::COMITE,
            \projects_status::EN_ATTENTE_PIECES,
            \projects_status::PREP_FUNDING,
            \projects_status::REJETE,
            \projects_status::REJET_ANALYSTE,
            \projects_status::REJET_COMITE,
            \projects_status::REVUE_ANALYSTE
        );
        $aProjectsPreFunding = $this->companies->getProjectsForCompany($this->companies->id_company, $aStatusPreFunding);

        foreach ($aProjectsPreFunding as $iKey => $aProject) {
            switch ($aProject['project_status']) {
                case \projects_status::EN_ATTENTE_PIECES:
                case \projects_status::A_TRAITER:
                    $aProjectsPreFunding[$iKey]['project_status_label'] = 'en-attente-de-pieces';
                    break;
                case \projects_status::REVUE_ANALYSTE:
                case \projects_status::COMITE:
                    $aProjectsPreFunding[$iKey]['project_status_label'] = 'en-cours-d-etude';
                    break;
                case \projects_status::REJET_ANALYSTE:
                case \projects_status::REJET_COMITE:
                case \projects_status::REJETE:
                    $aProjectsPreFunding[$iKey]['project_status_label'] = 'refuse';
                    break;
                case \projects_status::PREP_FUNDING:
                case \projects_status::A_FUNDER:
                    $aProjectsPreFunding[$iKey]['project_status_label'] = 'en-attente-de-mise-en-ligne';
                    break;
            }
            $fPredictAmountAutoBid = $this->get('unilend.service.autobid_settings_manager')->predictAmount($aProject['risk'], $aProject['period']);
            $aProjectsPreFunding[$iKey]['predict_autobid'] = round(($fPredictAmountAutoBid / $aProject['amount']) * 100, 1);
        }
        return $aProjectsPreFunding;
    }

    private function getProjectsFunding()
    {
        $aProjectsFunding   = $this->companies->getProjectsForCompany($this->companies->id_company, \projects_status::EN_FUNDING);
        $oBids              = $this->loadData('bids');
        $this->oDateTimeNow = new \DateTime('NOW');

        foreach ($aProjectsFunding as $iKey => $aProject) {
            $aProjectsFunding[$iKey]['AverageIR']        = $this->projects->getAverageInterestRate($aProject['id_project'], $aProject['project_status']);
            $iSumBids                                    = $oBids->getSoldeBid($aProject['id_project']);
            $aProjectsFunding[$iKey]['funding-progress'] = ((1 - ($aProject['amount'] - $iSumBids) / $aProject['amount']) * 100);
            $oDateTimeEnd                                = DateTime::createFromFormat('Y-m-d H:i:s', $aProject['date_retrait_full']);
            $aProjectsFunding[$iKey]['oInterval']        = $oDateTimeEnd->diff($this->oDateTimeNow);
        }
        return $aProjectsFunding;
    }

    private function getProjectsPostFunding()
    {
        $aStatusPostFunding = array(
            \projects_status::DEFAUT,
            \projects_status::FUNDE,
            \projects_status::PROBLEME,
            \projects_status::RECOUVREMENT,
            \projects_status::REMBOURSE,
            \projects_status::REMBOURSEMENT,
            \projects_status::REMBOURSEMENT_ANTICIPE
        );

        $aProjectsPostFunding   = $this->companies->getProjectsForCompany($this->companies->id_company, $aStatusPostFunding);
        $oRepaymentSchedule     = $this->loadData('echeanciers_emprunteur');

        foreach ($aProjectsPostFunding as $iKey => $aProject) {
            $aProjectsPostFunding[$iKey]['AverageIR']              = $this->projects->getAverageInterestRate($aProject['id_project'], $aProject['project_status']);
            $aProjectsPostFunding[$iKey]['RemainingDueCapital']    = $this->calculateRemainingDueCapital($aProject['id_project']);

            $aNextRepayment                                        = array_shift($oRepaymentSchedule->select('status_emprunteur = 0 AND id_project = ' . $aProject['id_project'], 'date_echeance_emprunteur ASC', '', 1));
            $aProjectsPostFunding[$iKey]['MonthlyPayment']         = ($aNextRepayment['montant'] + $aNextRepayment['commission'] + $aNextRepayment['tva']) / 100;
            $aProjectsPostFunding[$iKey]['DateNextMonthlyPayment'] = $aNextRepayment[0]['date_echeance_emprunteur'];
        }

        usort($aProjectsPostFunding, function ($aFirstArray, $aSecondArray) {
            return $aFirstArray['date_retrait'] < $aSecondArray['date_retrait'];
        });

        return $aProjectsPostFunding ;
    }

    private function calculateRemainingDueCapital($iProjectId)
    {
        $oPaymentSchedule = $this->loadData('echeanciers');

        $aPayment     = $oPaymentSchedule->getLastOrder($iProjectId);
        $iPaymentOrder = (isset($aPayment)) ? $aPayment['ordre'] + 1 : 1;

        return $oPaymentSchedule->reste_a_payer_ra($iProjectId, $iPaymentOrder);
    }

    private function contactEmailClient()
    {
        /** @var \settings $oSettings */
        $oSettings = $this->loadData('settings');
        $oSettings->get('Facebook', 'type');
        $sFacebookURL = $this->settings->value;

        $oSettings->get('Twitter', 'type');
        $sTwitterURL = $this->settings->value;

        $aVariables = array(
            'surl'     => $this->surl,
            'url'      => $this->url,
            'prenom_c' => $_POST['prenom'],
            'projets'  => $this->lurl . '/' . $this->tree->getSlug(4, $this->language),
            'lien_fb'  => $sFacebookURL,
            'lien_tw'  => $sTwitterURL
        );

        /** @var \Unilend\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('demande-de-contact', $this->language, $aVariables);
        $message->setTo($_POST['email']);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    public function _getCSVWithLenderDetails()
    {
        $this->projects->get($this->params[1], 'id_project');
        switch ($this->params[0]) {
            case 'l':
                $aColumnHeaders = array('ID Préteur', 'Nom ou Raison Sociale', 'Prénom', 'Mouvement', 'Montant', 'Date');
                $sType          = $this->lng['espace-emprunteur']['mouvement-deblocage-des-fonds'];
                $aData          = $this->projects->getLoansAndLendersForProject($this->projects->id_project);
                $sFilename      = 'details_prets';
                break;
            case 'e':
                $aColumnHeaders = array(
                    'ID Préteur',
                    'Nom ou Raison Sociale',
                    'Prénom',
                    'Mouvement',
                    'Montant',
                    'Capital',
                    'Intérets',
                    'Date'
                );
                $sType          = $this->lng['espace-emprunteur']['mouvement-remboursement'];
                $aData          = $this->projects->getDuePaymentsAndLenders($this->projects->id_project, $this->params[2]);
                $oDateTime      = DateTime::createFromFormat('Y-m-d H:i:s', $aData[0]['date']);
                $sDate          = $oDateTime->format('mY');
                $sFilename      = 'details_remboursements_' . $this->params[1] . '_' . $sDate;
                break;
            default:
                break;
        }

        foreach ($aData as $key => $row) {
            if (empty($row['name']) === false) {
                $aData[$key]['nom']    = $row['name'];
                $aData[$key]['prenom'] = null;
            }
            $aData[$key]['name'] = $sType;
            $aData[$key]['date'] = $this->dates->formatDate($row['date']);

            if (empty($row['amount']) === false) {
                $aData[$key]['amount'] = $row['amount'] / 100;
            }

            if (empty($row['montant']) === false) {
                $aData[$key]['montant'] = $row['montant'] / 100;
                $aData[$key]['capital'] = $row['capital'] / 100;
                $aData[$key]['interets'] = $row['interets'] / 100;
            }
        }

        $this->exportCSV($aColumnHeaders, $aData, $sFilename);

    }

    public function _getCSVOperations()
    {
        $aBorrowerOperations = $this->clients->getDataForBorrowerOperations(
            $_SESSION['operations-filter']['projects'],
            $_SESSION['operations-filter']['start'],
            $_SESSION['operations-filter']['end'],
            $_SESSION['operations-filter']['transaction'],
            $this->clients->id_client
        );

        $sFilename      = 'operations';
        $aColumnHeaders = array('Opération', 'Référence de projet', 'Date de l\'opération', 'Montant de l\'opération', 'Dont TVA');

        foreach ($aBorrowerOperations as $aOperation) {
            $aData[] = array(
                $this->lng['espace-emprunteur']['operations-type-' . $aOperation['type']],
                $aOperation['id_project'],
                $this->dates->formatDateMysqltoShortFR($aOperation['date']),
                number_format($aOperation['montant'], 2, ',', ''),
                (empty($aOperation['tva']) === false) ? number_format($aOperation['tva'], 2, ',', '') : '0'
            );
        }

        $this->exportCSV($aColumnHeaders, $aData, $sFilename);
    }

    public function _getPdfOperations()
    {
        include $this->path . '/apps/default/controllers/pdf.php';

        $oCommandPdf    = new Command('pdf', 'setDisplay', $this->language);
        $oPdf           = new pdfController($oCommandPdf, $this->Config, 'default');
        $sPath          = $this->path . 'protected/operations_export_pdf/' . $this->clients->id_client . '/';
        $sNamePdfClient = 'operations_emprunteur_' . date('Y-m-d') . '.pdf';

        $oPdf->lng['espace-emprunteur']                 = $this->ln->selectFront('espace-emprunteur', $this->language, $this->App);
        $oPdf->lng['preteur-operations-vos-operations'] = $this->ln->selectFront('preteur-operations-vos-operations', $this->language, $this->App);
        $oPdf->lng['preteur-operations-pdf']            = $this->ln->selectFront('preteur-operations-pdf', $this->language, $this->App);

        $oPdf->aBorrowerOperations = $this->clients->getDataForBorrowerOperations(
            $_SESSION['operations-filter']['projects'],
            $_SESSION['operations-filter']['start'],
            $_SESSION['operations-filter']['end'],
            $_SESSION['operations-filter']['transaction'],
            $this->clients->id_client
        );

        $oPdf->companies->get($this->clients->id_client, 'id_client_owner');
        $oPdf->setDisplay('operations_emprunteur_pdf_html');
        $oPdf->WritePdf($sPath . $sNamePdfClient, 'operations');
        $oPdf->ReadPdf($sPath . $sNamePdfClient, $sNamePdfClient);

    }

    public function _faq()
    {

        $this->tree = $this->loadData('tree');
        $this->tree->get(array('id_tree' => '441', 'id_langue' => $this->language));

        $aContent = $this->tree_elements->select('id_tree = "441" AND id_langue = "' . $this->language . '"');

        foreach ($aContent as $aElement) {
            $this->elements->get($aElement['id_element']);
            $this->content[$this->elements->slug] = $aElement['value'];
        }
    }

    private function exportCSV($aColumnHeaders, $aData, $sFilename)
    {
        $sSeparator  = "\t";
        $sEol = "\n";
        $sCSV  =  count($aColumnHeaders) ? '"'. implode('"'.$sSeparator.'"', $aColumnHeaders).'"'.$sEol : '';

        foreach ($aData as $row) {
            $sCSV .= '"'. implode('"'.$sSeparator.'"', $row).'"'.$sEol;
        }

        $sEncodedCSV = mb_convert_encoding($sCSV, 'UTF-16LE', 'UTF-8');

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="'.$sFilename.'.csv"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: '. strlen($sEncodedCSV));
        echo chr(255) . chr(254) . $sEncodedCSV;
        exit;

    }
}
