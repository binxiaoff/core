<?php

use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailQueue;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;
use Unilend\Bundle\CoreBusinessBundle\Entity\Translations;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\MessagingBundle\Service\MailQueueManager;
use Unilend\Bundle\MessagingBundle\Service\MailTemplateManager;

class mailsController extends bootstrap
{
    /** @var MailTemplates */
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

        /** @var MailTemplateManager $mailTemplateManager */
        $mailTemplateManager = $this->get('unilend.service.mail_template');

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager          = $this->get('doctrine.orm.entity_manager');
            $mailTemplateRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates');
            $mailTemplate           = $mailTemplateRepository->findOneBy([
                'type'   => $this->params[1],
                'locale' => $this->getParameter('locale'),
                'status' => MailTemplates::STATUS_ACTIVE,
                'part'   => MailTemplates::PART_TYPE_CONTENT
            ]);

            $mailTemplateManager->archiveTemplate($mailTemplate);

            $_SESSION['freeow']['title']   = 'Archivage d\'un mail';
            $_SESSION['freeow']['message'] = 'Le mail a bien été archivé';

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

        $this->headers = $mailTemplateManager->getActiveMailTemplates(null, MailTemplates::PART_TYPE_HEADER);
        $this->footers = $mailTemplateManager->getActiveMailTemplates(null, MailTemplates::PART_TYPE_FOOTER);
    }

    public function _add()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->menu_admin = 'edition';

        if ($this->request->isMethod(Request::METHOD_POST)) {
            $form = $this->handlePost();
            /** @var MailTemplateManager $mailTemplateManager */
            $mailTemplateManager = $this->get('unilend.service.mail_template');

            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager          = $this->get('doctrine.orm.entity_manager');
            $mailTemplateRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates');
            $mailTemplate           = $mailTemplateRepository->findOneBy([
                'type'   => $form['type'],
                'locale' => $this->getParameter('locale'),
                'status' => MailTemplates::STATUS_ACTIVE,
                'part'   => MailTemplates::PART_TYPE_CONTENT
            ]);

            if (empty($form['type']) || empty($form['sender_name']) || empty($form['sender_email']) || empty($form['subject'])) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Ajout impossible : tous les champs n\'ont été remplis';
            } elseif (null !== $mailTemplate) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Ajout impossible : ce mail existe déjà';
            } else {
                $mailTemplateManager->addTemplate(
                    empty($form['type']) ? null : $form['type'],
                    empty($form['sender_name']) ? null : $form['sender_name'],
                    empty($form['sender_email']) ? null : $form['sender_email'],
                    empty($form['subject']) ? null : $form['subject'],
                    empty($form['title']) ? null : $form['title'],
                    empty($form['content']) ? null : $form['content'],
                    empty($form['header']) ? null : $mailTemplateRepository->find($form['header']),
                    empty($form['footer']) ? null : $mailTemplateRepository->find($form['footer']),
                    empty($form['recipient_type']) ? null : $form['recipient_type']
                );

                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Le mail a bien été ajouté';
            }

            header('Location: ' . $this->lurl . '/mails');
            die;
        }

        /** @var MailTemplateManager $mailTemplateManager */
        $mailTemplateManager = $this->get('unilend.service.mail_template');
        $this->headers       = $mailTemplateManager->getActiveMailTemplates(null, MailTemplates::PART_TYPE_HEADER);
        $this->footers       = $mailTemplateManager->getActiveMailTemplates(null, MailTemplates::PART_TYPE_FOOTER);
    }

    public function _edit()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->menu_admin = 'edition';

        /** @var MailTemplateManager $mailTemplateManager */
        $mailTemplateManager = $this->get('unilend.service.mail_template');

        if (false === empty($this->params[0])) {
            $part = isset($this->params[1]) ? $this->params[1] : MailTemplates::PART_TYPE_CONTENT;

            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager          = $this->get('doctrine.orm.entity_manager');
            $mailTemplateRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates');
            $this->mailTemplate     = $mailTemplateRepository->findOneBy([
                'type'   => $this->params[0],
                'locale' => $this->getParameter('locale'),
                'status' => MailTemplates::STATUS_ACTIVE,
                'part'   => $part
            ]);

            if (null !== $this->mailTemplate && $this->request->isMethod(Request::METHOD_POST)) {
                $form = $this->handlePost();

                if (MailTemplates::PART_TYPE_CONTENT === $part && (empty($form['sender_name']) || empty($form['sender_email']) || empty($form['subject']))) {
                    $_SESSION['freeow']['title']   = 'Modification d\'un mail';
                    $_SESSION['freeow']['message'] = 'Modification impossible : tous les champs n\'ont été remplis';
                } else {
                    $mailTemplateManager->modifyTemplate(
                        $this->mailTemplate,
                        empty($form['sender_name']) ? null : $form['sender_name'],
                        empty($form['sender_email']) ? null : $form['sender_email'],
                        empty($form['subject']) ? null : $form['subject'],
                        empty($form['title']) ? null : $form['title'],
                        empty($form['content']) ? null : $form['content'],
                        empty($form['header']) ? null : $mailTemplateRepository->find($form['header']),
                        empty($form['footer']) ? null : $mailTemplateRepository->find($form['footer']),
                        empty($form['recipient_type']) ? null : $form['recipient_type']
                    );

                    $_SESSION['freeow']['title']   = 'Modification d\'un mail';
                    $_SESSION['freeow']['message'] = 'Le mail a bien été modifié';
                }

                header('Location: ' . $this->url . '/mails');
                die;
            }

            /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
            $translator      = $this->get('translator');
            $titleLabel      = Translations::SECTION_MAIL_TITLE . '_' . $this->mailTemplate->getType();
            $this->mailTitle = $translator->trans($titleLabel);
            $this->mailTitle = $this->mailTitle === $titleLabel ? '' : $this->mailTitle;

            /** @var MailTemplateManager $mailTemplateManager */
            $mailTemplateManager = $this->get('unilend.service.mail_template');
            $this->headers       = $mailTemplateManager->getActiveMailTemplates(null, MailTemplates::PART_TYPE_HEADER);
            $this->footers       = $mailTemplateManager->getActiveMailTemplates(null, MailTemplates::PART_TYPE_FOOTER);
        }
    }

    public function _preview()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);
        $this->hideDecoration();
        $this->autoFireView = false;

        $title    = $this->request->request->get('title', '');
        $content  = $this->request->request->get('content', '');
        $header   = $this->request->request->filter('header', null, FILTER_VALIDATE_INT);
        $footer   = $this->request->request->filter('footer', null, FILTER_VALIDATE_INT);
        $keywords = $this->request->request->filter('keywords', [], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        if ($header || $footer) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager          = $this->get('doctrine.orm.entity_manager');
            $mailTemplateRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates');

            if ($header && $header = $mailTemplateRepository->find($header)) {
                $content = $header->getContent() . $content;
            }

            if ($footer && $footer = $mailTemplateRepository->find($footer)) {
                $content .= $footer->getContent();
            }
        }

        $keywords['title'] = $title;
        foreach ($keywords as $keyword => $value) {
            $content = str_replace('[EMV DYN]' . $keyword . '[EMV /DYN]', $value, $content);
        }

        $this->sendAjaxResponse(true, ['content' => $content]);
    }

    public function _emailhistory()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_CONFIGURATION);
        $this->menu_admin = 'configuration';

        /** @var MailQueueManager $mailQueueManager */
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
            $mailQueue = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:MailQueue')->find($this->params[0]);
            if ($mailQueue instanceof MailQueue) {
                /** @var MailQueueManager $oMailQueueManager */
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
        $fields = [];

        foreach ($_POST as $field => $value) {
            $fields[$field] = $value;
        }

        $fields['type']    = isset($fields['type']) ? $this->bdd->generateSlug(trim($_POST['type'])) : '';
        $fields['subject'] = isset($fields['subject']) ? str_replace('"', '\'', $fields['subject']) : null;
        $fields['content'] = isset($fields['content']) ? str_replace('"', '\'', $fields['content']) : null;

        return $fields;
    }
}
