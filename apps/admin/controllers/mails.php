<?php

class mailsController extends bootstrap
{

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('configuration');
        $this->menu_admin = 'configuration';
    }

    public function _default()
    {
        /** @var \Unilend\Bundle\MessagingBundle\Service\MailTemplateManager $oMailTemplateManager */
        $oMailTemplateManager = $this->get('unilend.service.mail_template');

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            /** @var \mail_templates $oMailTemplate */
            $oMailTemplate = $this->loadData('mail_templates');

            $oMailTemplate->get($this->params[1], 'type');
            $oMailTemplateManager->archiveTemplate($oMailTemplate);

            $_SESSION['freeow']['title']   = 'Archivage d\'un mail';
            $_SESSION['freeow']['message'] = 'Le mail a bien &eacute;t&eacute; archiv&eacute; !';

            header('Location:' . $this->lurl . '/mails');
            die;
        }

        $this->lMails = $oMailTemplateManager->getActiveMailTemplates();
    }

    public function _add()
    {
        if (isset($_POST['form_add_mail'])) {
            $aPost = $this->handlePost();
            /** @var \Unilend\Bundle\MessagingBundle\Service\MailTemplateManager $oMailTemplateManager */
            $oMailTemplateManager = $this->get('unilend.service.mail_template');
            /** @var \mail_templates $mailTemplate */
            $mailTemplate = $this->loadData('mail_templates');

            if (empty($aPost['type']) || empty($aPost['sender_name']) || empty($aPost['sender_email']) || empty($aPost['subject'])) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Ajout impossible : tous les champs n\'ont &eacute;t&eacute; remplis';
            } else if ($mailTemplate->exist($aPost['type'] . '" AND status = "' . \mail_templates::STATUS_ACTIVE, 'type')) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Ajout impossible : ce mail existe d&eacute;j&agrave;';
            } else {
                $oMailTemplateManager->addTemplate($aPost['type'], $aPost['sender_name'], $aPost['sender_email'], $aPost['subject'], $aPost['content']);

                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Le mail a bien &eacute;t&eacute; ajout&eacute;';
            }

            header('Location:' . $this->lurl . '/mails');
            die;
        }
    }

    public function _edit()
    {
        /** @var \Unilend\Bundle\MessagingBundle\Service\MailTemplateManager $oMailTemplateManager */
        $oMailTemplateManager = $this->get('unilend.service.mail_template');

        if (false === empty($this->params[0])) {
            /** @var \mail_templates oMailTemplate */
            $this->oMailTemplate = $this->loadData('mail_templates');
            $this->oMailTemplate->get($this->params[0], 'status = ' . \mail_templates::STATUS_ACTIVE . ' AND type');

            if (isset($_POST['form_mod_mail']) && false === empty($this->oMailTemplate->id_mail_template)) {
                $aPost = $this->handlePost();

                if (empty($aPost['sender_name']) || empty($aPost['sender_email']) || empty($aPost['subject'])) {
                    $_SESSION['freeow']['title']   = 'Modification d\'un mail';
                    $_SESSION['freeow']['message'] = 'Modification impossible : tous les champs n\'ont &eacute;t&eacute; remplis';
                } else {
                    $oMailTemplateManager->modifyTemplate($this->oMailTemplate, $aPost['sender_name'], $aPost['sender_email'], $aPost['subject'], $aPost['content']);

                    $_SESSION['freeow']['title']   = 'Modification d\'un mail';
                    $_SESSION['freeow']['message'] = 'Le mail a bien &eacute;t&eacute; modifi&eacute;';
                }

                header('Location:' . $this->url . '/mails');
                die;
            }
        }
    }

    public function _emailhistory()
    {
        /** @var \Unilend\Bundle\MessagingBundle\Service\MailQueueManager $oMailQueueManager */
        $oMailQueueManager = $this->get('unilend.service.mail_queue');

        if (isset($_POST['form_send_search'])) {
            $sFrom      = (isset($_POST['from']) && false === empty($_POST['from'])) ? $_POST['from'] : null;
            $sTo        = (isset($_POST['to']) && false === empty($_POST['to'])) ? $_POST['to'] : null;
            $sSubject   = (isset($_POST['subject']) && false === empty($_POST['subject'])) ? $_POST['subject'] : null;
            $oDateStart = (isset($_POST['date_from']) && false === empty($_POST['date_from'])) ? \DateTime::createFromFormat('d/m/Y H:i:s', $_POST['date_from']) : new \DateTime('2013-01-01');
            $oDateEnd   = (isset($_POST['date_to']) && false === empty($_POST['date_to'])) ? \DateTime::createFromFormat('d/m/Y H:i:s', $_POST['date_to']) : new \DateTime('NOW');

            $this->aEmails = $oMailQueueManager->searchSentEmails(null, $sFrom, $sTo, $sSubject, $oDateStart, $oDateEnd);
        } else {
            $this->aEmails = $oMailQueueManager->searchSentEmails(null, null, null, null, null, null, 100);
        }
    }

    public function _recherche()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->lurl;
    }

    public function _emailpreview()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->lurl;

        /** @var \mail_queue $oMailQueue */
        $oMailQueue = $this->loadData('mail_queue');

        if (isset($this->params[0]) && $oMailQueue->get($this->params[0])) {
            /** @var \Unilend\Bundle\MessagingBundle\Service\MailQueueManager $oEmail */
            $oMailQueueManager = $this->get('unilend.service.mail_queue');
            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $oEmail */
            $oEmail = $oMailQueueManager->getMessage($oMailQueue);

            $iDate = $oEmail->getDate();
            $aFrom = $oEmail->getFrom();
            $aTo   = $oEmail->getTo();

            $this->aEmail = array(
                'date'    => date('d/m/Y H:i', $iDate),
                'from'    => array_shift($aFrom),
                'to'      => array_shift($aTo),
                'subject' => $oEmail->getSubject(),
                'body'    => $oEmail->getBody()
            );
        }
    }

    private function handlePost()
    {
        foreach ($_POST as $field => $value) {
            $aPost[$field] = $value;
        }

        $aPost['type']    = isset($aPost['type']) ? $this->bdd->generateSlug(trim($_POST['type'])) : '';
        $aPost['subject'] = str_replace('"', '\'', $_POST['subject']);
        $aPost['content'] = str_replace('"', '\'', $_POST['content']);

        return $aPost;
    }
}
