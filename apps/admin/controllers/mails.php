<?php

use Symfony\Component\HttpFoundation\Request;
use Unilend\Entity\{MailQueue, MailTemplates, Translations, Zones};
use Unilend\Service\Mailer\{MailQueueManager, MailTemplateManager};

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
            $mailTemplateRepository = $entityManager->getRepository(MailTemplates::class);
            $mailTemplate           = $mailTemplateRepository->findOneBy([
                'type'   => $this->params[1],
                'locale' => $this->getParameter('locale'),
                'status' => MailTemplates::STATUS_ACTIVE,
                'part'   => MailTemplates::PART_TYPE_CONTENT
            ]);

            $mailTemplateManager->archiveTemplate($mailTemplate);

            $_SESSION['freeow']['title']   = 'Archivage d\'un mail';
            $_SESSION['freeow']['message'] = 'Le mail a bien été archivé';

            header('Location: ' . $this->url . '/mails');
            die;
        }

        $externalEmails = $mailTemplateManager->getActiveMailTemplates(MailTemplates::RECIPIENT_TYPE_EXTERNAL);
        $internalEmails = $mailTemplateManager->getActiveMailTemplates(MailTemplates::RECIPIENT_TYPE_INTERNAL);
        $emailUsage     = $mailTemplateManager->getMailTemplateUsage();

        $this->sections = [
            [
                'title'  => 'Emails externes',
                'emails' => $externalEmails,
                'stats'  => $emailUsage
            ],
            [
                'title'  => 'Emails internes',
                'emails' => $internalEmails,
                'stats'  => $emailUsage
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
            /** @var MailTemplateManager $mailTemplateManager */
            $mailTemplateManager = $this->get('unilend.service.mail_template');

            $type          = $this->bdd->generateSlug($this->request->request->get('type', null));
            $senderName    = $this->request->request->get('sender_name', null);
            $senderEmail   = $this->request->request->get('sender_email', null);
            $subject       = $this->request->request->get('subject', null);
            $title         = $this->request->request->get('title', null);
            $content       = $this->request->request->get('content', null);
            $header        = $this->request->request->get('header', null);
            $footer        = $this->request->request->get('footer', null);
            $recipientType = $this->request->request->get('recipient_type', null);

            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager          = $this->get('doctrine.orm.entity_manager');
            $mailTemplateRepository = $entityManager->getRepository(MailTemplates::class);
            $mailTemplate           = $mailTemplateRepository->findOneBy([
                'type'   => $type,
                'locale' => $this->getParameter('locale'),
                'status' => MailTemplates::STATUS_ACTIVE,
                'part'   => MailTemplates::PART_TYPE_CONTENT
            ]);

            if (empty($type) || empty($senderName) || empty($senderEmail) || empty($subject)) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Ajout impossible : tous les champs n\'ont été remplis';
            } elseif (null !== $mailTemplate) {
                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Ajout impossible : ce mail existe déjà';
            } else {
                $mailTemplateManager->addTemplate(
                    empty($type) ? null : $type,
                    empty($senderName) ? null : $senderName,
                    empty($senderEmail) ? null : $senderEmail,
                    empty($subject) ? null : $subject,
                    empty($title) ? null : $title,
                    empty($content) ? null : $content,
                    empty($header) ? null : $mailTemplateRepository->find($header),
                    empty($footer) ? null : $mailTemplateRepository->find($footer),
                    empty($recipientType) ? null : $recipientType
                );

                $_SESSION['freeow']['title']   = 'Ajout d\'un mail';
                $_SESSION['freeow']['message'] = 'Le mail a bien été ajouté';
            }

            header('Location: ' . $this->url . '/mails');
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
            $mailTemplateRepository = $entityManager->getRepository(MailTemplates::class);
            $this->mailTemplate     = $mailTemplateRepository->findOneBy([
                'type'   => $this->params[0],
                'locale' => $this->getParameter('locale'),
                'status' => MailTemplates::STATUS_ACTIVE,
                'part'   => $part
            ]);

            $senderName    = $this->request->request->get('sender_name', null);
            $senderEmail   = $this->request->request->get('sender_email', null);
            $subject       = $this->request->request->get('subject', null);
            $title         = $this->request->request->get('title', null);
            $content       = $this->request->request->get('content', null);
            $header        = $this->request->request->get('header', null);
            $footer        = $this->request->request->get('footer', null);
            $recipientType = $this->request->request->get('recipient_type', null);

            if (null !== $this->mailTemplate && $this->request->isMethod(Request::METHOD_POST)) {
                if (MailTemplates::PART_TYPE_CONTENT === $part && (empty($senderName) || empty($senderEmail) || empty($subject))) {
                    $_SESSION['freeow']['title']   = 'Modification d\'un mail';
                    $_SESSION['freeow']['message'] = 'Modification impossible : tous les champs n\'ont été remplis';
                } else {
                    $mailTemplateManager->modifyTemplate(
                        $this->mailTemplate,
                        empty($senderName) ? null : $senderName,
                        empty($senderEmail) ? null : $senderEmail,
                        empty($subject) ? null : $subject,
                        empty($title) ? null : $title,
                        empty($content) ? null : $content,
                        empty($header) ? null : $mailTemplateRepository->find($header),
                        empty($footer) ? null : $mailTemplateRepository->find($footer),
                        empty($recipientType) ? null : $recipientType
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
            $mailTemplateRepository = $entityManager->getRepository(MailTemplates::class);

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
        $_SESSION['request_url'] = $this->url;
    }

    public function _email_history_preview()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_CONFIGURATION);

        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;
        /** @var \Unilend\Service\Mailer\MailQueueManager $mailQueueManager */
        $mailQueueManager = $this->get('unilend.service.mail_queue');

        try {
            $mailQueueId = filter_var($this->params[0], FILTER_SANITIZE_NUMBER_INT);
            /** @var \Unilend\Entity\MailQueue $mailQueue */
            $mailQueue = $this->get('doctrine.orm.entity_manager')
                ->getRepository(MailQueue::class)->find($mailQueueId);
            if (null === $mailQueue) {
                $this->errorMessage = 'L\'email que vous avez demandé n\'existe pas.';
                return;
            }
            $email     = $mailQueueManager->getMessage($mailQueue);
            $sentAt    = $mailQueue->getSentAt();
            $from      = $email->getFrom();
            $to        = $email->getTo();

            $this->email = [
                'date'    => $sentAt->format('d/m/Y H:i'),
                'from'    => array_shift($from),
                'to'      => array_shift($to),
                'subject' => $email->getSubject(),
                'body'    => $email->getBody()
            ];
        } catch (\Exception $exception) {
            $this->errorMessage = 'Impossible d\'afficher le mail';
        }
    }
}
