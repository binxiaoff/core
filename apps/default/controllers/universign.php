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

    function universignController($command, $config, $app)
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

    function _default()
    {
        // chargement des datas
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
                        // mail notifiaction admin
                        // Adresse notifications
                        $this->settings->get('Adresse notification pouvoir mandat signe', 'type');
                        $destinaire = $this->settings->value;

                        // on recup le projet
                        $projects->get($projects_pouvoir->id_project, 'id_project');
                        // on recup la companie
                        $companies->get($projects->id_company, 'id_company');
                        // on recup l'emprunteur
                        $clients->get($companies->id_client_owner, 'id_client');

                        $lien_pdf_pouvoir = $this->lurl . $projects_pouvoir->url_pdf;
                        $lien_pdf_mandat  = $this->lurl . $clients_mandats->url_pdf;

                        // Recuperation du modele de mail
                        $this->mails_text->get('notification-pouvoir-mandat-signe', 'lang = "' . $this->language . '" AND type');

                        // Variables du mailing
                        $surl         = $this->surl;
                        $url          = $this->lurl;
                        $id_projet    = $projects->id_project;
                        $nomProjet    = utf8_decode($projects->title_bo);
                        $nomCompany   = utf8_decode($companies->name);
                        $lien_pouvoir = $lien_pdf_pouvoir;
                        $lien_mandat  = $lien_pdf_mandat;

                        // Attribution des données aux variables
                        $sujetMail = htmlentities($this->mails_text->subject);
                        eval("\$sujetMail = \"$sujetMail\";");

                        $texteMail = $this->mails_text->content;
                        eval("\$texteMail = \"$texteMail\";");

                        $exp_name = $this->mails_text->exp_name;
                        eval("\$exp_name = \"$exp_name\";");

                        // Nettoyage de printemps
                        $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                        $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                        // Envoi du mail
                        $this->email = $this->loadLib('email', array());
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                        $this->email->addRecipient(trim($destinaire));
                        //$this->email->addBCCRecipient('');

                        $this->email->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
                        $this->email->setHTMLBody(utf8_decode($texteMail));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        $this->oLogger->addRecord(ULogger::INFO, 'Mandat and Pouvoir Ok', array($clients_mandats->id_project));
                        // fin mail
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

            header("location:" . $this->lurl . '/universign/confirmation/mandat/' . $clients_mandats->id_mandat);
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
                        $destinaire = $this->settings->value;

                        // on recup le projet
                        $projects->get($projects_pouvoir->id_project, 'id_project');
                        // on recup la companie
                        $companies->get($projects->id_company, 'id_company');
                        // on recup l'emprunteur
                        $clients->get($companies->id_client_owner, 'id_client');

                        $lien_pdf_pouvoir = $this->lurl . $projects_pouvoir->url_pdf;
                        $lien_pdf_mandat  = $this->lurl . $clients_mandats->url_pdf;

                        // Recuperation du modele de mail
                        $this->mails_text->get('notification-pouvoir-mandat-signe', 'lang = "' . $this->language . '" AND type');

                        // Variables du mailing
                        $surl         = $this->surl;
                        $url          = $this->lurl;
                        $id_projet    = $projects->id_project;
                        $nomProjet    = utf8_decode($projects->title_bo);
                        $nomCompany   = utf8_decode($companies->name);
                        $lien_pouvoir = $lien_pdf_pouvoir;
                        $lien_mandat  = $lien_pdf_mandat;

                        // Attribution des données aux variables
                        $sujetMail = htmlentities($this->mails_text->subject);
                        eval("\$sujetMail = \"$sujetMail\";");

                        $texteMail = $this->mails_text->content;
                        eval("\$texteMail = \"$texteMail\";");

                        $exp_name = $this->mails_text->exp_name;
                        eval("\$exp_name = \"$exp_name\";");

                        // Nettoyage de printemps
                        $sujetMail = strtr($sujetMail, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');
                        $exp_name  = strtr($exp_name, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyynn');

                        // Envoi du mail
                        $this->email = $this->loadLib('email', array());
                        $this->email->setFrom($this->mails_text->exp_email, $exp_name);
                        $this->email->addRecipient(trim($destinaire));
                        //$this->email->addRecipient(trim(implode(',', $this->Config['DebugMailIt'])));

                        $this->email->setSubject('=?UTF-8?B?' . base64_encode(html_entity_decode($sujetMail)) . '?=');
                        $this->email->setHTMLBody(utf8_decode($texteMail));
                        Mailer::send($this->email, $this->mails_filer, $this->mails_text->id_textemail);
                        $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir and Mandat Ok', array($projects_pouvoir->id_project));
                        // fin mail
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
            header("location:" . $this->lurl . '/universign/confirmation/pouvoir/' . $projects_pouvoir->id_pouvoir);
            die;
        } else {
            header("location:" . $this->lurl);
            die;
        }
    }

    function _mandat()
    {
        // chargement des datas
        $clients         = $this->loadData('clients');
        $clients_mandats = $this->loadData('clients_mandats');

        if ($clients_mandats->get($this->params[0], 'id_mandat') && $clients_mandats->status != 1) {
            if ($clients_mandats->url_universign != '' && $clients_mandats->status == 0) {
                $this->oLogger->addRecord(ULogger::INFO, 'Mandat not signed. Redirection to universign.', array($clients_mandats->id_project));
                header("location:" . $clients_mandats->url_universign);
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
                //used variables
                $uni_url     = $this->uni_url; // address of the universign server with basic authentication
                $firstname   = utf8_decode($clients->prenom); // the signatory first name
                $lastname    = utf8_decode($clients->nom); // the signatory last name
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
                    header("location:" . $url);
                    die;

                } else {
                    //displays the error code and the fault message
                    $this->oLogger->addRecord(ULogger::ERROR, 'Mandat response generation from universign : NOK. ERROR : ' . $r->faultCode() . ' ; REASON : ' . $r->faultString(), array($clients_mandats->id_project));
                    mail(implode(',', $this->Config['DebugMailIt']), 'unilend erreur universign reception', ' creatioon mandat id mandat : ' . $clients_mandats->id_mandat . ' | An error occurred: Code: ' . $r->faultCode() . ' Reason: "' . $r->faultString());
                }
            }
        }
    }

    function _pouvoir()
    {
        // chargement des datas
        $companies        = $this->loadData('companies');
        $projects_pouvoir = $this->loadData('projects_pouvoir');
        $clients          = $this->loadData('clients');
        $projects         = $this->loadData('projects');

        // on check les id et si le pdf n'est pas deja signé
        if ($projects_pouvoir->get($this->params[0], 'id_pouvoir') && $projects_pouvoir->status != 1) {
            // on check si deja existant en bdd avec l'url universign et si encore en cours
            if (isset($this->params[1]) && $this->params[1] == 'NoUpdateUniversign' && $projects_pouvoir->url_universign != '' && $projects_pouvoir->status == 0) {// si le meme jour alors on regenere pas le pdf universign
                $this->oLogger->addRecord(ULogger::INFO, 'Pouvoir not signed but flag bdd exist. Redirection to universign.', array($projects_pouvoir->id_project));
                header("location:" . $projects_pouvoir->url_universign);
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
                $firstname    = utf8_decode($clients->prenom); // the signatory first name
                $lastname     = utf8_decode($clients->nom); // the signatory last name
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
                    header("location:" . $url);
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

    function _confirmation()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = true;
        $this->autoFireHead   = true;
        $this->autoFireFooter = true;

        // chargement des datas
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
            } else {
                $this->oLogger->addRecord(ULogger::INFO, 'Confirmation not pouvoir and not mandat. Redirection home page.', array($projects_pouvoir->id_project));
                header("location:" . $this->lurl);
                die;
            }
        } else {
            $this->oLogger->addRecord(ULogger::INFO, 'Confirmation not pouvoir and not mandat. Redirection home page.', array($projects_pouvoir->id_project));
            header("location:" . $this->lurl);
            die;
        }
    }
}