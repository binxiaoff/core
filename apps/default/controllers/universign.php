<?php

use Unilend\librairies\ULogger;
use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Client;

class universignController extends bootstrap
{
    /**
     * @var ULogger
     */
    private $oLogger;

    public function universignController($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->uni_url = $this->Config['universign_url'][$this->Config['env']];
        $this->oLogger = new ULogger('Universign', $this->logPath, 'universign.log');
    }

    public function _default()
    {
        $clients          = $this->loadData('clients');
        $clients_mandats  = $this->loadData('clients_mandats');
        $companies        = $this->loadData('companies');
        $projects_pouvoir = $this->loadData('projects_pouvoir');
        $projects         = $this->loadData('projects');


        // Retour pdf Mandat
        if (isset($this->params[2]) && isset($this->params[1]) && $this->params[1] == 'mandat' && $clients_mandats->get($this->params[2], 'id_mandat') && $clients_mandats->status == 0) {
            if ($this->params[0] == 'success') {
                //used variables
                $uni_url = $this->uni_url;
                $uni_id  = $clients_mandats->id_universign; // a collection id

                //create the request
                $c = new Client($uni_url);
                $f = new Request('requester.getDocumentsByTransactionId', array(new Value($uni_id, "string")));

                //Send request and analyse response
                $r = &$c->send($f);
                $this->oLogger->addRecord(ULogger::INFO, 'Mandat send to Universign', array($clients_mandats->id_project));
                if (!$r->faultCode()) {
                    //if the request succeeded
                    $doc['name']    = $r->value()->arrayMem(0)->structMem('name')->scalarVal();
                    $doc['content'] = $r->value()->arrayMem(0)->structMem('content')->scalarVal();

                    // On met a jour le pdf en bdd
                    file_put_contents($doc['name'], $doc['content']);
                    $clients_mandats->status = 1;
                    $clients_mandats->update();
                    $this->oLogger->addRecord(ULogger::INFO, 'Mandat Ok', array($clients_mandats->id_project));
                    // redirection sur page confirmation : mandat signé
                    // on verif si on a le mandat de déjà signé
                    if ($projects_pouvoir->get($clients_mandats->id_project, 'id_project') && $projects_pouvoir->status == 1) {
                        $this->settings->get('Adresse notification pouvoir mandat signe', 'type');
                        $sRecipient = $this->settings->value;

                        $projects->get($projects_pouvoir->id_project, 'id_project');
                        $companies->get($projects->id_company, 'id_company');

                        $aVarEmail = array(
                            '$surl'         => $this->surl,
                            '$id_projet'    => $projects->id_project,
                            '$nomProjet'    => $projects->title_bo,
                            '$nomCompany'   => $companies->name,
                            '$lien_pouvoir' => $this->lurl . $projects_pouvoir->url_pdf,
                            '$lien_mandat'  => $this->lurl . $clients_mandats->url_pdf,
                        );

                        try {
                            /** @var unilend_email $oUnilendEmail */
                            $oUnilendEmail = $this->loadLib('unilend_email');
                            $oUnilendEmail->addAllMailVars($aVarEmail);
                            $oUnilendEmail->setTemplate('notification-pouvoir-mandat-signe', $this->language);
                            $oUnilendEmail->addRecipient($sRecipient);
                            $oUnilendEmail->sendToStaff();
                        } catch (\Exception $oException) {
                            $oMailLogger = new ULogger('mail', $this->logPath, 'mail.log');
                            $oMailLogger->addRecord(ULogger::CRITICAL, 'Caught Exception: ' . $oException->getMessage() . ' ' . $oException->getTraceAsString());
                        }

                        $this->oLogger->addRecord(ULogger::INFO, 'Mandat and Pouvoir Ok', array($clients_mandats->id_project));
                    } else {
                        $this->oLogger->addRecord(ULogger::INFO, 'Mandat Ok but Pouvoir not signed.', array($clients_mandats->id_project));
                    }
                } else {
                    //displays the error code and the fault message
                    $this->oLogger->addRecord(ULogger::ERROR, 'Return Universign Mandat NOK. ERROR : ' . $r->faultCode() . ' ; REASON : ' . $r->faultString(), array($clients_mandats->id_project));
                    mail(implode(',', $this->Config['DebugMailIt']), 'unilend erreur universign reception', 'id mandat : ' . $clients_mandats->id_mandat . ' | An error occurred: Code: ' . $r->faultCode() . ' Reason: "' . $r->faultString());
                }
            } elseif ($this->params[0] == 'fail') {
                $this->oLogger->addRecord(ULogger::ERROR, 'Mandat Fail.', array($clients_mandats->id_project));
                $clients_mandats->status = 3;
                $clients_mandats->update();
            } elseif ($this->params[0] == 'cancel') {
                $this->oLogger->addRecord(ULogger::ERROR, 'Mandat Cancel.', array($clients_mandats->id_project));
                //echo 'cancel';
                $clients_mandats->status = 2;
                $clients_mandats->update();
            }

            header('Location: ' . $this->lurl . '/universign/confirmation/mandat/' . $clients_mandats->id_mandat);
            die;
        } elseif (isset($this->params[2]) && isset($this->params[1]) && $this->params[1] == 'pouvoir' &&
            $projects_pouvoir->get($this->params[2], 'id_pouvoir') && $projects_pouvoir->status == 0
        ) {// Retour pouvoir
            if ($this->params[0] == 'success') {
                //used variables
                $uni_url = $this->uni_url;
                $uni_id  = $projects_pouvoir->id_universign; // a collection id

                //create the request
                $c = new Client($uni_url);
                $f = new Request('requester.getDocumentsByTransactionId', array(new Value($uni_id, "string")));

                //Send request and analyse response
                $r = &$c->send($f);
                $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir send to Universign', array($projects_pouvoir->id_project));
                if (!$r->faultCode()) {
                    //if the request succeeded
                    $doc['name']    = $r->value()->arrayMem(0)->structMem('name')->scalarVal();
                    $doc['content'] = $r->value()->arrayMem(0)->structMem('content')->scalarVal();

                    // On met a jour le pdf en bdd
                    file_put_contents($doc['name'], $doc['content']);
                    $projects_pouvoir->status = 1;
                    $projects_pouvoir->update();
                    $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir Ok', array($projects_pouvoir->id_project));
                    // on verif si on a le mandat de déjà signé
                    if ($clients_mandats->get($projects_pouvoir->id_project, 'id_project') && $clients_mandats->status == 1) {
                        // mail notifiaction admin
                        // Adresse notifications
                        $this->settings->get('Adresse notification pouvoir mandat signe', 'type');
                        $sRecipient = $this->settings->value;

                        $projects->get($projects_pouvoir->id_project, 'id_project');
                        $companies->get($projects->id_company, 'id_company');

                        $aVarEmail = array(
                            '$surl'         => $this->surl,
                            '$id_projet'    => $projects->id_project,
                            '$nomProjet'    => $projects->title_bo,
                            '$nomCompany'   => $companies->name,
                            '$lien_pouvoir' => $this->lurl . $projects_pouvoir->url_pdf,
                            '$lien_mandat'  => $this->lurl . $clients_mandats->url_pdf,
                        );

                        try {
                            /** @var unilend_email $oUnilendEmail */
                            $oUnilendEmail = $this->loadLib('unilend_email');
                            $oUnilendEmail->addAllMailVars($aVarEmail);
                            $oUnilendEmail->setTemplate('notification-pouvoir-mandat-signe', $this->language);
                            $oUnilendEmail->addRecipient($sRecipient);
                            $oUnilendEmail->sendToStaff();
                        } catch (\Exception $oException) {
                            $oMailLogger = new ULogger('mail', $this->logPath, 'mail.log');
                            $oMailLogger->addRecord(ULogger::CRITICAL, 'Caught Exception: ' . $oException->getMessage() . ' ' . $oException->getTraceAsString());
                        }
                        $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir and Mandat Ok', array($projects_pouvoir->id_project));
                    } else {
                        $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir Ok but Mandat not signed.', array($projects_pouvoir->id_project));
                    }
                } else {
                    //displays the error code and the fault message
                    $this->oLogger->addRecord(ULogger::ERROR, 'Pouvoir NOK. ERROR : ' . $r->faultCode() . ' ; REASON : ' . $r->faultString(), array($projects_pouvoir->id_project));
                    mail(implode(',', $this->Config['DebugMailIt']), 'unilend erreur universign reception', 'id pouvoir : ' . $projects_pouvoir->id_pouvoir . ' | An error occurred: Code: ' . $r->faultCode() . ' Reason: "' . $r->faultString());
                }
            } elseif ($this->params[0] == 'fail') {
                $this->oLogger->addRecord(ULogger::ERROR, 'Pouvoir Fail.', array($projects_pouvoir->id_project));
                $projects_pouvoir->status = 3;
                $projects_pouvoir->update();
            } elseif ($this->params[0] == 'cancel') {
                $this->oLogger->addRecord(ULogger::ERROR, 'Pouvoir Cancel.', array($projects_pouvoir->id_project));
                $projects_pouvoir->status = 2;
                $projects_pouvoir->update();
            }
            header('Location: ' . $this->lurl . '/universign/confirmation/pouvoir/' . $projects_pouvoir->id_pouvoir);
            die;
        } elseif (isset($this->params[1], $this->params[2]) && $this->params[1] === 'cgv_emprunteurs') {
            $oProjectCgv = $this->loadData('project_cgv');

            if (false === $oProjectCgv->get($this->params[2], 'id') || $oProjectCgv->status != project_cgv::STATUS_NO_SIGN) {
                header('Location: ' . $this->lurl);
                die;
            }

            if ($this->params[0] === 'success') {
                $uni_url = $this->uni_url;
                $uni_id  = $oProjectCgv->id_universign; // a collection id

                $c = new Client($uni_url);
                $f = new Request('requester.getDocumentsByTransactionId', array(new Value($uni_id, "string")));

                $r = &$c->send($f);
                $this->oLogger->addRecord(ULogger::INFO, 'CGV emprunteur send to Universign', array($oProjectCgv->id_project));

                if (! $r->faultCode()) {
                    $doc['name']    = $r->value()->arrayMem(0)->structMem('name')->scalarVal();
                    $doc['content'] = $r->value()->arrayMem(0)->structMem('content')->scalarVal();

                    file_put_contents($doc['name'], $doc['content']);

                    $oProjectCgv->status = project_cgv::STATUS_SIGN_UNIVERSIGN;
                    $oProjectCgv->update();

                    $this->oLogger->addRecord(ULogger::INFO, 'CGV emprunteur Ok', array($oProjectCgv->id_project));

                    $oClients   = $this->loadData('clients');
                    $oProjects  = $this->loadData('projects');
                    $oCompanies = $this->loadData('companies');
                    $oUsers     = $this->loadData('users');

                    if (! $oProjects->get($oProjectCgv->id_project, 'id_project')) {
                        header('Location: ' . $this->lurl);
                        return;
                    }
                    if (! $oCompanies->get($oProjects->id_company, 'id_company')) {
                        header('Location: ' . $this->lurl);
                        return;
                    }
                    if (! $oClients->get($oCompanies->id_client_owner, 'id_client')) {
                        header('Location: ' . $this->lurl);
                        return;
                    }

                    if (false === empty($oProjects->id_commercial) && $oUsers->get($oProjects->id_commercial, 'id_user')) {
                        $sRecipient = $oUsers->email;
                    } else {
                        $this->settings->get('Adresse notification cgv emprunteur signe', 'type');
                        $sRecipient = $this->settings->value;
                    }

                    $aReplacements = array(
                        '[AURL]'         => $this->aurl,
                        '[SURL]'         => $this->surl,
                        '[PROJECT_ID]'   => $oProjects->id_project,
                        '[COMPANY_NAME]' => $oCompanies->name,
                        '[PROJECT_NAME]' => $oProjects->title_bo,
                        '[CGV_BORROWER]' => $this->lurl . $oProjectCgv->getUrlPath()
                    );

                    try {
                        /** @var unilend_email $oUnilendEmail */
                        $oUnilendEmail = $this->loadLib('unilend_email');
                        $oUnilendEmail->addAllMailVars($aReplacements);
                        $oUnilendEmail->setTemplate('notification-cgv-projet-signe', $this->language);
                        $oUnilendEmail->addRecipient($sRecipient);
                        $oUnilendEmail->sendToStaff();
                    } catch (\Exception $oException) {
                        $oMailLogger = new ULogger('mail', $this->logPath, 'mail.log');
                        $oMailLogger->addRecord(ULogger::CRITICAL, 'Caught Exception: ' . $oException->getMessage() . ' ' . $oException->getTraceAsString());
                    }

                    $this->oLogger->addRecord(ULogger::INFO, 'CGV emprunteur notification mail sent', array($oProjectCgv->id_project));
                } else {
                    $this->oLogger->addRecord(ULogger::ERROR, 'CGV emprunteur NOK. ERROR : ' . $r->faultCode() . ' ; REASON : ' . $r->faultString(), array($oProjectCgv->id_project));
                    mail(implode(',', $this->Config['DebugMailIt']), 'unilend erreur universign reception', 'id cgv_project : ' . $oProjectCgv->id . ' | An error occurred: Code: ' . $r->faultCode() . ' Reason: "' . $r->faultString());
                }
            } elseif ($this->params[0] === 'fail') {
                $this->oLogger->addRecord(ULogger::ERROR, 'CGV emprunteur failed.', array($oProjectCgv->id_project));

                $oProjectCgv->status = project_cgv::STATUS_SIGN_FAILED;
                $oProjectCgv->update();
                // redirection sur page confirmation : une erreur est parvenue essayez plus tard
            } elseif ($this->params[0] === 'cancel') {
                $this->oLogger->addRecord(ULogger::ERROR, 'CGV emprunteur cancelled.', array($oProjectCgv->id_project));

                $oProjectCgv->status = project_cgv::STATUS_SIGN_CANCELLED;
                $oProjectCgv->update();
                // redirection sur page confirmation : vous avez annulé voulez vous signer votre mandat ?
            }

            header('Location: ' . $this->lurl . '/universign/confirmation/cgv_emprunteurs/' . $oProjectCgv->id . '/' . sha1($oProjectCgv->id_project . '_' . $oProjectCgv->id_tree));
            die;
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    public function _mandat()
    {
        $clients         = $this->loadData('clients');
        $clients_mandats = $this->loadData('clients_mandats');

        if ($clients_mandats->get($this->params[0], 'id_mandat') && $clients_mandats->status != 1) {
            if ($clients_mandats->url_universign != '' && $clients_mandats->status == 0) {
                $this->oLogger->addRecord(ULogger::INFO, 'Mandat not signed. Redirection to universign.', array($clients_mandats->id_project));
                header('Location: ' . $clients_mandats->url_universign);
                die;
            } else {
                switch ($clients_mandats->status) {
                    case 0:
                        $sMandatStatus = 'not signed';
                        break;
                    case 2:
                        $sMandatStatus = 'cancel';
                        break;
                    case 3:
                        $sMandatStatus = 'fail';
                        break;
                }
                $this->oLogger->addRecord(ULogger::INFO, 'Mandat status : ' . $sMandatStatus . '. Creation of pdf for send to universign.', array($clients_mandats->id_project));
                $clients->get($clients_mandats->id_client, 'id_client');

                $uni_url     = $this->uni_url; // address of the universign server with basic authentication
                $firstname   = $clients->prenom; // the signatory first name
                $lastname    = $clients->nom; // the signatory last name
                $phoneNumber = str_replace(' ', '', $clients->telephone); // the signatory mobile phone number
                $email       = $clients->email; // the signatory mobile phone number
                $doc_name    = $this->path . 'protected/pdf/mandat/' . $clients_mandats->name; // the name of the PDF document to sign
                $doc_content = file_get_contents($doc_name); // the binary content of the PDF file
                $returnPage  = array(
                    "success" => $this->lurl . "/universign/success/mandat/" . $clients_mandats->id_mandat,
                    "fail"    => $this->lurl . "/universign/fail/mandat/" . $clients_mandats->id_mandat,
                    "cancel"  => $this->lurl . "/universign/cancel/mandat/" . $clients_mandats->id_mandat
                );

                // positionnement signature
                $page = 1;
                $x    = 255;
                $y    = 314;

                //create the request
                $c = new Client($uni_url);

                $docSignatureField = array(
                    "page"        => new Value($page, "int"),
                    "x"           => new Value($x, "int"),
                    "y"           => new Value($y, "int"),
                    "signerIndex" => new Value(0, "int"),
                    "label"       => new Value("Unilend", "string")
                );

                $signer = array(
                    "firstname"    => new Value($firstname, "string"),
                    "lastname"     => new Value($lastname, "string"),
                    "phoneNum"     => new Value($phoneNumber, "string"),
                    "emailAddress" => new Value($email, "string")
                );

                $doc = array(
                    "content"         => new Value($doc_content, "base64"),
                    "name"            => new Value($doc_name, "string"),
                    "signatureFields" => new Value(array(new Value($docSignatureField, "struct")), "array")
                );

                $language = "fr";

                $signers = array(new Value($signer, "struct"));

                $request = array(
                    "documents"          => new Value(array(new Value($doc, "struct")), "array"),
                    "signers"            => new Value($signers, "array"),
                    // the return urls
                    "successURL"         => new Value($returnPage["success"], "string"),
                    "failURL"            => new Value($returnPage["fail"], "string"),
                    "cancelURL"          => new Value($returnPage["cancel"], "string"),
                    //the types of accepted certificate : timestamp for simple signature
                    "certificateTypes"   => new Value(array(new Value("timestamp", "string")), "array"),
                    "language"           => new Value($language, "string"),
                    //The OTP will be sent by Email
                    "identificationType" => new Value("sms", "string"),
                    "description"        => new Value("Mandat id : " . $clients_mandats->id_mandat, "string")
                );

                $f = new Request('requester.requestTransaction', array(new Value($request, "struct")));

                //send request and stores response values
                $r = &$c->send($f);
                $this->oLogger->addRecord(ULogger::INFO, 'Mandat send to Universign', array($clients_mandats->id_project), array($clients_mandats->id_project));
                if (!$r->faultCode()) {
                    //if the request succeeded
                    $url = $r->value()->structMem('url')->scalarVal(); //you should redirect the signatory to this url
                    $id  = $r->value()->structMem('id')->scalarVal(); //you should store this id

                    $clients_mandats->id_universign  = $id;
                    $clients_mandats->url_universign = $url;
                    $clients_mandats->status         = 0;
                    $clients_mandats->update();
                    $this->oLogger->addRecord(ULogger::INFO, 'Mandat response generation from universign : OK. Redirection to universign to sign.', array($clients_mandats->id_project));
                    header('Location: ' . $url);
                    die;

                } else {
                    //displays the error code and the fault message
                    $this->oLogger->addRecord(ULogger::ERROR, 'Mandat response generation from universign : NOK. ERROR : ' . $r->faultCode() . ' ; REASON : ' . $r->faultString(), array($clients_mandats->id_project));
                    mail(implode(',', $this->Config['DebugMailIt']), 'unilend erreur universign reception', ' creatioon mandat id mandat : ' . $clients_mandats->id_mandat . ' | An error occurred: Code: ' . $r->faultCode() . ' Reason: "' . $r->faultString());
                }
            }
        }
    }

    public function _pouvoir()
    {
        $companies        = $this->loadData('companies');
        $projects_pouvoir = $this->loadData('projects_pouvoir');
        $clients          = $this->loadData('clients');
        $projects         = $this->loadData('projects');

        // on check les id et si le pdf n'est pas deja signé
        if ($projects_pouvoir->get($this->params[0], 'id_pouvoir') && $projects_pouvoir->status != 1) {
            // on check si deja existant en bdd avec l'url universign et si encore en cours
            if (isset($this->params[1]) && $this->params[1] == 'NoUpdateUniversign' && $projects_pouvoir->url_universign != '' && $projects_pouvoir->status == 0) {// si le meme jour alors on regenere pas le pdf universign
                $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir not signed but flag bdd exist. Redirection to universign.', array($projects_pouvoir->id_project));
                header('Location: ' . $projects_pouvoir->url_universign);
                die;
            } else {// Sinon on crée
                switch ($projects_pouvoir->status) {
                    case 0:
                        $sPouvoirStatus = 'not signed';
                        break;
                    case 2:
                        $sPouvoirStatus = 'cancel';
                        break;
                    case 3:
                        $sPouvoirStatus = 'fail';
                        break;
                }
                $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir status : ' . $sPouvoirStatus . '. Creation of pdf for send to universign.', array($projects_pouvoir->id_project));

                // on recup le projet
                $projects->get($projects_pouvoir->id_project, 'id_project');
                // on recup la companie
                $companies->get($projects->id_company, 'id_company');
                // on recup l'emprunteur
                $clients->get($companies->id_client_owner, 'id_client');

                //used variables
                $uni_url      = $this->uni_url; // address of the universign server with basic authentication
                $firstname    = $clients->prenom; // the signatory first name
                $lastname     = $clients->nom; // the signatory last name
                $organization = $companies->name;
                $phoneNumber  = str_replace(' ', '', $clients->telephone); // the signatory mobile phone number
                $email        = $clients->email; // the signatory mobile phone number
                $doc_name     = $this->path . 'protected/pdf/pouvoir/' . $projects_pouvoir->name; // the name of the PDF document to sign
                $doc_content  = file_get_contents($doc_name); // the binary content of the PDF file
                $returnPage   = array(
                    "success" => $this->lurl . "/universign/success/pouvoir/" . $projects_pouvoir->id_pouvoir,
                    "fail"    => $this->lurl . "/universign/fail/pouvoir/" . $projects_pouvoir->id_pouvoir,
                    "cancel"  => $this->lurl . "/universign/cancel/pouvoir/" . $projects_pouvoir->id_pouvoir
                );
                // positionnement signature
                $page = 1;
                $x    = 335;
                $y    = 370;

                //create the request
                $c = new Client($uni_url);

                $docSignatureField = array(
                    "page"        => new Value($page, "int"),
                    "x"           => new Value($x, "int"),
                    "y"           => new Value($y, "int"),
                    "signerIndex" => new Value(0, "int"),
                    "label"       => new Value("Unilend", "string")
                );

                $signer = array(
                    "firstname"    => new Value($firstname, "string"),
                    "lastname"     => new Value($lastname, "string"),
                    "organization" => new Value($organization, "string"),
                    "phoneNum"     => new Value($phoneNumber, "string"),
                    "emailAddress" => new Value($email, "string")
                );

                $doc = array(
                    "content"         => new Value($doc_content, "base64"),
                    "name"            => new Value($doc_name, "string"),
                    "signatureFields" => new Value(array(new Value($docSignatureField, "struct")), "array")
                );

                $language = "fr";

                $signers = array(new Value($signer, "struct"));

                $request = array(
                    "documents"          => new Value(array(new Value($doc, "struct")), "array"),
                    "signers"            => new Value($signers, "array"),
                    // the return urls
                    "successURL"         => new Value($returnPage["success"], "string"),
                    "failURL"            => new Value($returnPage["fail"], "string"),
                    "cancelURL"          => new Value($returnPage["cancel"], "string"),
                    //the types of accepted certificate : timestamp for simple signature
                    "certificateTypes"   => new Value(array(new Value("timestamp", "string")), "array"),
                    "language"           => new Value($language, "string"),
                    //The OTP will be sent by Email
                    "identificationType" => new Value("sms", "string"),
                    "description"        => new Value("Pouvoir id : " . $projects_pouvoir->id_pouvoir, "string"),
                );

                $f = new Request('requester.requestTransaction', array(new Value($request, "struct")));

                //send request and stores response values
                $r = &$c->send($f);
                $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir send to Universign', array($projects_pouvoir->id_project), array($projects_pouvoir->id_project));
                if (!$r->faultCode()) {
                    //if the request succeeded
                    $url = $r->value()->structMem('url')->scalarVal(); //you should redirect the signatory to this url
                    $id  = $r->value()->structMem('id')->scalarVal(); //you should store this id

                    $projects_pouvoir->id_universign  = $id;
                    $projects_pouvoir->url_universign = $url;
                    $projects_pouvoir->status         = 0;
                    $projects_pouvoir->update();
                    $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir response generation from universign : OK. Redirection to universign to sign.', array($projects_pouvoir->id_project));
                    header('Location: ' . $url);
                    die;
                } else {
                    //displays the error code and the fault message
                    $this->oLogger->addRecord(ULogger::ERROR, 'Pouvoir response generation from universign : NOK. ERROR : ' . $r->faultCode() . ' ; REASON : ' . $r->faultString(), array($projects_pouvoir->id_project));
                    mail(implode(',', $this->Config['DebugMailIt']), 'unilend erreur universign reception', 'id mandat : ' . $projects_pouvoir->id_pouvoir . ' | An error occurred: Code: ' . $r->faultCode() . ' Reason: "' . $r->faultString());
                }
            }
        } else {
            echo 'error';
        }
    }

    public function _confirmation()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = true;
        $this->autoFireHead   = true;
        $this->autoFireFooter = true;

        $clients          = $this->loadData('clients');
        $clients_mandats  = $this->loadData('clients_mandats');
        $companies        = $this->loadData('companies');
        $projects_pouvoir = $this->loadData('projects_pouvoir');
        $projects         = $this->loadData('projects');

        // Si on a 2 parmas
        if (isset($this->params[0]) && isset($this->params[1])) {
            // si on a le mandat
            if ($this->params[0] == 'mandat' && $clients_mandats->get($this->params[1], 'id_mandat')) {
                $clients->get($clients_mandats->id_client, 'id_client');

                $this->lien_pdf = $this->lurl . $clients_mandats->url_pdf;

                // si mandat ok
                if ($clients_mandats->status == 1) {
                    $this->titre   = 'Confirmation mandat';
                    $this->message = 'Votre mandat a bien été signé';
                    $this->oLogger->addRecord(ULogger::INFO, 'Mandat confirmation : signed.', array($clients_mandats->id_project));
                } elseif ($clients_mandats->status == 2) {// mandat annulé
                    $this->titre   = 'Confirmation mandat';
                    $this->message = 'Votre mandat a bien été annulé vous pouvez le signer plus tard.';
                    $this->oLogger->addRecord(ULogger::INFO, 'Mandat confirmation : cancelled.', array($clients_mandats->id_project));
                } elseif ($clients_mandats->status == 3) {// mandat fail
                    $this->titre   = 'Confirmation mandat';
                    $this->message = 'Une erreur s\'est produite ressayez plus tard';
                    $this->oLogger->addRecord(ULogger::ERROR, 'Mandat confirmation : error.', array($clients_mandats->id_project));
                } else {
                    $this->titre   = 'Confirmation mandat';
                    $this->message = 'Vous n\'avez pas encore signé votre mandat';
                    $this->oLogger->addRecord(ULogger::INFO, 'Mandat confirmation : not signed.', array($clients_mandats->id_project));
                }
            } elseif ($this->params[0] == 'pouvoir' && $projects_pouvoir->get($this->params[1], 'id_pouvoir')) {// si on a le pouvoir
                // on recup le projet
                $projects->get($projects_pouvoir->id_project, 'id_project');
                // on recup la companie
                $companies->get($projects->id_company, 'id_company');
                // on recup l'emprunteur
                $clients->get($companies->id_client_owner, 'id_client');

                $this->titre    = 'Confirmation pouvoir';
                $this->lien_pdf = $this->lurl . $projects_pouvoir->url_pdf;

                // si pouvoir ok
                if ($projects_pouvoir->status == 1) {
                    $this->message = 'Votre pouvoir a bien été signé';
                    $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir confirmation : signed.', array($projects_pouvoir->id_project));
                } elseif ($projects_pouvoir->status == 2) {// pouvoir annulé
                    $this->message = 'Votre pouvoir a bien été annulé vous pouvez le signer plus tard.';
                    $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir confirmation : cancelled.', array($projects_pouvoir->id_project));
                } elseif ($projects_pouvoir->status == 3) {// pouvoir fail
                    $this->message = 'Une erreur s\'est produite ressayez plus tard';
                    $this->oLogger->addRecord(ULogger::ERROR, 'Pouvoir confirmation : error.', array($projects_pouvoir->id_project));
                } else {
                    $this->message = 'Vous n\'avez pas encore signé votre pouvoir';
                    $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir confirmation : not signed.', array($projects_pouvoir->id_project));
                }
            } elseif ($this->params[0] == 'cgv_emprunteurs') {
                // CGV Emprunteur (project)
                $oProjectCgv = $this->loadData('project_cgv');

                if (
                    false === isset($this->params[1], $this->params[2])
                    || false === $oProjectCgv->get($this->params[1], 'id')
                    || $this->params[2] !== sha1($oProjectCgv->id_project . '_' . $oProjectCgv->id_tree)
                ) {
                    header('Location: ' . $this->lurl);
                    die;
                }

                $this->setView('cgv_emprunteurs');

                $projects->get($oProjectCgv->id_project, 'id_project');
                $companies->get($projects->id_company, 'id_company');
                $clients->get($companies->id_client_owner, 'id_client');

                $this->status   = $oProjectCgv->status;
                $this->lien_pdf = $this->lurl . $oProjectCgv->getUrlPath();

                if ($oProjectCgv->status == project_cgv::STATUS_NO_SIGN) {
                    $this->oLogger->addRecord(ULogger::INFO, 'CGV borrower confirmation : not signed.', array($oProjectCgv->id_project));
                }
            } else {
                $this->oLogger->addRecord(ULogger::INFO, 'Unknown document for confirmation. Redirection home page.');
                header('Location: ' . $this->lurl);
                die;
            }
        } else {
            $this->oLogger->addRecord(ULogger::INFO, 'Unknown document for confirmation. Redirection home page.');
            header('Location: ' . $this->lurl);
            die;
        }
    }

    public function _cgv_emprunteurs()
    {
        if (false === isset($this->params[0]) || false === isset($this->params[1]) || false === is_numeric($this->params[0])) {
            header('Location: ' . $this->lurl);
            return;
        }

        $oProjectCgv = $this->loadData('project_cgv');
        $oClients    = $this->loadData('clients');
        $oProjects   = $this->loadData('projects');
        $oCompanies  = $this->loadData('companies');

        // on check les id et si le pdf n'est pas deja signé
        if ($oProjectCgv->get($this->params[0], 'id') && project_cgv::STATUS_NO_SIGN == $oProjectCgv->status) {
            if($this->params[1] !== $oProjectCgv->name) {
                header('Location: ' . $this->lurl);
                return;
            }
            // on check si deja existant en bdd avec l'url universign et si encore en cours
            if ($oProjectCgv->url_universign != '' && date('Y-m-d', strtotime($oProjectCgv->updated)) === date('Y-m-d')) {
                // If it's the same day, we don't regenerate the universign
                header('Location: ' . $oProjectCgv->url_universign);
                return;
            }
            // If not we create it.
            if (! $oProjects->get($oProjectCgv->id_project, 'id_project')) {
                header('Location: ' . $this->lurl);
                return;
            }
            if (! $oCompanies->get($oProjects->id_company, 'id_company')) {
                header('Location: ' . $this->lurl);
                return;
            }
            if (! $oClients->get($oCompanies->id_client_owner, 'id_client')) {
                header('Location: ' . $this->lurl);
                return;
            }

            $uni_url      = $this->uni_url; // address of the universign server with basic authentication
            $firstname    = $oClients->prenom; // the signatory first name
            $lastname     = $oClients->nom; // the signatory last name
            $organization = $oCompanies->name;
            $phoneNumber  = str_replace(' ', '', $oClients->telephone); // the signatory mobile phone number
            $email        = $oClients->email; // the signatory mobile phone number
            $doc_name     = $this->path . 'protected/pdf/cgv_emprunteurs/' . $oProjectCgv->name; // the name of the PDF document to sign

            if (false === file_exists($doc_name)) {
                header('Location: ' . $this->lurl);
                return;
            }

            $doc_content = file_get_contents($doc_name); // the binary content of the PDF file
            $returnPage  = array(
                'success' => $this->lurl . '/universign/success/cgv_emprunteurs/' . $oProjectCgv->id,
                'fail'    => $this->lurl . '/universign/fail/cgv_emprunteurs/' . $oProjectCgv->id,
                'cancel'  => $this->lurl . '/universign/cancel/cgv_emprunteurs/' . $oProjectCgv->id
            );

            // positionnement signature
            $page = 1;
            $x    = 430;
            $y    = 750;

            $c = new Client($uni_url);

            $docSignatureField = array(
                'page'        => new Value($page, 'int'),
                'x'           => new Value($x, 'int'),
                'y'           => new Value($y, 'int'),
                'signerIndex' => new Value(0, 'int'),
                'label'       => new Value('Unilend', 'string')
            );

            $signer = array(
                'firstname'    => new Value($firstname, 'string'),
                'lastname'     => new Value($lastname, 'string'),
                'organization' => new Value($organization, 'string'),
                'phoneNum'     => new Value($phoneNumber, 'string'),
                'emailAddress' => new Value($email, 'string')
            );

            $doc = array(
                'content'         => new Value($doc_content, 'base64'),
                'name'            => new Value($doc_name, 'string'),
                'signatureFields' => new Value(array(new Value($docSignatureField, 'struct')), 'array')
            );

            $language = 'fr';
            $signers  = array(new Value($signer, 'struct'));
            $request  = array(
                'documents'          => new Value(array(new Value($doc, 'struct')), 'array'),
                'signers'            => new Value($signers, 'array'),
                'successURL'         => new Value($returnPage['success'], 'string'),
                'failURL'            => new Value($returnPage['fail'], 'string'),
                'cancelURL'          => new Value($returnPage['cancel'], 'string'),
                'certificateTypes'   => new Value(array(new Value('timestamp', 'string')), 'array'),//the types of accepted certificate : timestamp for simple signature
                'language'           => new Value($language, 'string'),
                'identificationType' => new Value('sms', 'string'),
                'description'        => new Value('CGV Emprunteur ID : ' . $oProjectCgv->id, 'string'),
            );

            $f = new Request('requester.requestTransaction', array(new Value($request, 'struct')));
            $r = &$c->send($f);

            if (!$r->faultCode()) {
                //if the request succeeded
                $url = $r->value()->structMem('url')->scalarVal(); //you should redirect the signatory to this url
                $id  = $r->value()->structMem('id')->scalarVal(); //you should store this id

                $oProjectCgv->id_universign  = $id;
                $oProjectCgv->url_universign = $url;
                $oProjectCgv->update();

                header('Location: ' . $url);
                die;
            } else {
                mail(implode(',', $this->Config['DebugMailIt']), 'unilend erreur universign reception', 'id cgv project : ' . $oProjectCgv->id . ' | An error occurred: Code: ' . $r->faultCode() . ' Reason: ' . $r->faultString());
            }
        } else {
            header('Location: ' . $this->lurl);
            return;
        }
    }
}
