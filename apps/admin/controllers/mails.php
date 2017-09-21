<?php

use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class mailsController extends bootstrap
{
    /**
     * @var \mail_templates
     */
    public $mailTemplate;

    public function initialize()
    {
        parent::initialize();

        $this->settings->get('Facebook', 'type');
        $this->facebookUrl = $this->settings->value;

        $this->settings->get('Twitter', 'type');
        $this->twitterUrl = $this->settings->value;
    }

    public function _default()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->menu_admin = 'edition';

        /** @var \Unilend\Bundle\MessagingBundle\Service\MailTemplateManager $mailTemplateManager */
        $mailTemplateManager = $this->get('unilend.service.mail_template');

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            /** @var \mail_templates $mailTemplate */
            $mailTemplate = $this->loadData('mail_templates');

            $mailTemplate->get($this->params[1], 'type');
            $mailTemplateManager->archiveTemplate($mailTemplate);

            $_SESSION['freeow']['title']   = 'Archivage d\'un mail';
            $_SESSION['freeow']['message'] = 'Le mail a bien &eacute;t&eacute; archiv&eacute; !';

            header('Location: ' . $this->lurl . '/mails');
            die;
        }

        $externalEmails     = $mailTemplateManager->getActiveMailTemplates(MailTemplates::RECIPIENT_TYPE_EXTERNAL);
        $internalEmails     = $mailTemplateManager->getActiveMailTemplates(MailTemplates::RECIPIENT_TYPE_INTERNAL);
        $externalEmailUsage = $mailTemplateManager->getMailTemplateUsage($externalEmails);
        $internalEmailUsage = $mailTemplateManager->getMailTemplateUsage($internalEmails);

        $this->sections = [
            [
                'title'  => 'Emails externes',
                'emails' => $externalEmails,
                'stats'  => $externalEmailUsage
            ],
            [
                'title'  => 'Emails internes',
                'emails' => $internalEmails,
                'stats'  => $internalEmailUsage
            ]
        ];
    }

    public function _add()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->menu_admin = 'edition';

        if ($this->request->isMethod(Request::METHOD_POST)) {
            $aPost = $this->handlePost();
            /** @var \Unilend\Bundle\MessagingBundle\Service\MailTemplateManager $mailTemplateManager */
            $mailTemplateManager = $this->get('unilend.service.mail_template');
            /** @var \mail_templates $mailTemplate */
            $mailTemplate = $this->loadData('mail_templates');

            if (empty($aPost['type']) || empty($aPost['sender_name']) || empty($aPost['sender_email']) || empty($aPost['subject'])) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Ajout impossible : tous les champs n\'ont &eacute;t&eacute; remplis';
            } else if ($mailTemplate->exist($aPost['type'] . '" AND status = "' . MailTemplates::STATUS_ACTIVE, 'type')) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Ajout impossible : ce mail existe d&eacute;j&agrave;';
            } else {
                $mailTemplateManager->addTemplate($aPost['type'], $aPost['sender_name'], $aPost['sender_email'], $aPost['subject'], $aPost['content']);

                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Le mail a bien &eacute;t&eacute; ajout&eacute;';
            }

            header('Location:' . $this->lurl . '/mails');
            die;
        }
    }

    public function _edit()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->menu_admin = 'edition';

        /** @var \Unilend\Bundle\MessagingBundle\Service\MailTemplateManager $mailTemplateManager */
        $mailTemplateManager = $this->get('unilend.service.mail_template');

        if (false === empty($this->params[0])) {
            $this->mailTemplate = $this->loadData('mail_templates');
            $this->mailTemplate->get($this->params[0], 'status = ' . MailTemplates::STATUS_ACTIVE . ' AND type');

            if ($this->mailTemplate->id_mail_template && $this->request->isMethod(Request::METHOD_POST)) {
                $aPost = $this->handlePost();

                if (empty($aPost['sender_name']) || empty($aPost['sender_email']) || empty($aPost['subject'])) {
                    $_SESSION['freeow']['title']   = 'Modification d\'un mail';
                    $_SESSION['freeow']['message'] = 'Modification impossible : tous les champs n\'ont &eacute;t&eacute; remplis';
                } else {
                    $mailTemplateManager->modifyTemplate($this->mailTemplate, $aPost['sender_name'], $aPost['sender_email'], $aPost['subject'], $aPost['content']);

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
        $this->users->checkAccess(Zones::ZONE_LABEL_CONFIGURATION);
        $this->menu_admin = 'configuration';

        /** @var \Unilend\Bundle\MessagingBundle\Service\MailQueueManager $mailQueueManager */
        $mailQueueManager = $this->get('unilend.service.mail_queue');

        if (isset($_POST['form_send_search'])) {
            $clientId  = isset($_POST['id_client']) && false === empty($_POST['id_client']) ? $_POST['id_client'] : null;
            $from      = isset($_POST['from']) && false === empty($_POST['from']) ? $_POST['from'] : null;
            $recipient = isset($_POST['to']) && false === empty($_POST['to']) ? $_POST['to'] : null;
            $subject   = isset($_POST['subject']) && false === empty($_POST['subject']) ? $_POST['subject'] : null;
            $startDate = isset($_POST['date_from']) && false === empty($_POST['date_from']) ? \DateTime::createFromFormat('d/m/Y', $_POST['date_from']) : new \DateTime('2013-01-01');
            $endDate   = isset($_POST['date_to']) && false === empty($_POST['date_to']) ? \DateTime::createFromFormat('d/m/Y', $_POST['date_to']) : new \DateTime('NOW');

            $this->emails = $mailQueueManager->searchSentEmails($clientId, $from, $recipient, $subject, $startDate, $endDate);
        }
    }

    public function _recherche()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_CONFIGURATION);
        $this->menu_admin = 'configuration';

        $this->hideDecoration();
        $_SESSION['request_url'] = $this->lurl;
    }

    public function _emailpreview()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_CONFIGURATION);
        $this->menu_admin = 'configuration';

        $this->hideDecoration();
        $_SESSION['request_url'] = $this->lurl;

        if (false === empty($this->params[0])) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\MailQueue $mailQueue */
            $mailQueue = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:MailQueue')->find($this->params[0]);
            if ($mailQueue instanceof \Unilend\Bundle\CoreBusinessBundle\Entity\MailQueue) {
                /** @var \Unilend\Bundle\MessagingBundle\Service\MailQueueManager $oEmail */
                $oMailQueueManager = $this->get('unilend.service.mail_queue');
                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $oEmail */
                $oEmail = $oMailQueueManager->getMessage($mailQueue);

                $iDate = $oEmail->getDate();
                $aFrom = $oEmail->getFrom();
                $aTo   = $oEmail->getTo();

                $this->aEmail = [
                    'date'    => date('d/m/Y H:i', $iDate),
                    'from'    => array_shift($aFrom),
                    'to'      => array_shift($aTo),
                    'subject' => $oEmail->getSubject(),
                    'body'    => $oEmail->getBody()
                ];
            }
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
