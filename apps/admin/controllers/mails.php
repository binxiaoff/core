<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\{MailFooter, MailHeader, MailQueue, MailTemplate, Translations};
use Unilend\Service\Mailer\{MailQueueManager, MailTemplateManager};
use Unilend\Service\Translation\TranslationLoader;

class mailsController extends Controller
{
    /** @var MailTemplate */
    public $mailTemplate;

    public function initialize()
    {
        parent::initialize();

        $this->menu_admin = 'mails';
    }

    public function _default()
    {
        /** @var MailTemplateManager $mailTemplateManager */
        $mailTemplateManager = $this->get('unilend.service.mail_template');

        if (isset($this->params[0]) && 'delete' === $this->params[0]) {
            /** @var EntityManager $entityManager */
            $entityManager          = $this->get('doctrine.orm.entity_manager');
            $mailTemplateRepository = $entityManager->getRepository(MailTemplate::class);
            $mailTemplate           = $mailTemplateRepository->findOneBy([
                'name'   => $this->params[1],
                'locale' => $this->getParameter('locale'),
            ]);

            $mailTemplateManager->archive($mailTemplate);

            $_SESSION['freeow']['title']   = 'Archivage d\'un mail';
            $_SESSION['freeow']['message'] = 'Le mail a bien été archivé';

            header('Location: ' . $this->url . '/mails');
            die;
        }

        $this->sections = [
            'email' => [
                'title'  => 'email',
                'emails' => $this->get('doctrine')->getRepository(MailTemplate::class)->findAll(),
            ],
        ];

        $this->headers = $this->get('doctrine')->getRepository(MailHeader::class)->findAll();
        $this->footers = $this->get('doctrine')->getRepository(MailFooter::class)->findAll();
    }

    public function _add()
    {
        if ($this->request->isMethod(Request::METHOD_POST)) {
            /** @var MailTemplateManager $mailTemplateManager */
            $mailTemplateManager = $this->get('unilend.service.mail_template');

            $type          = $this->generateSlug($this->request->request->get('type', null));
            $senderName    = $this->request->request->get('sender_name', null);
            $senderEmail   = $this->request->request->get('sender_email', null);
            $subject       = $this->request->request->get('subject', null);
            $title         = $this->request->request->get('title', null);
            $content       = $this->request->request->get('content', null);
            $header        = $this->request->request->get('header', null);
            $footer        = $this->request->request->get('footer', null);
            $recipientType = $this->request->request->get('recipient_type', null);

            /** @var EntityManager $entityManager */
            $entityManager          = $this->get('doctrine.orm.entity_manager');
            $mailTemplateRepository = $entityManager->getRepository(MailTemplate::class);
            $mailTemplate           = $mailTemplateRepository->findOneBy([
                'name'   => $type,
                'locale' => $this->getParameter('locale'),
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

        $this->headers = $this->get('doctrine')->getRepository(MailHeader::class)->findAll();
        $this->footers = $this->get('doctrine')->getRepository(MailFooter::class)->findAll();
    }

    public function _edit()
    {
        /** @var MailTemplateManager $mailTemplateManager */
        $mailTemplateManager = $this->get('unilend.service.mail_template');

        if (false === empty($this->params[0])) {
            /** @var EntityManager $entityManager */
            $entityManager          = $this->get('doctrine.orm.entity_manager');
            $mailTemplateRepository = $entityManager->getRepository(MailTemplate::class);
            $this->mailTemplate     = $mailTemplateRepository->findOneBy([
                'name'   => $this->params[0],
                'locale' => $this->getParameter('locale'),
            ]);

            $senderName  = $this->request->request->get('sender_name', null);
            $senderEmail = $this->request->request->get('sender_email', null);
            $subject     = $this->request->request->get('subject', null);
            $title       = $this->request->request->get('title', null);
            $content     = $this->request->request->get('content', null);
            $header      = $this->request->request->get('header', null);
            $footer      = $this->request->request->get('footer', null);

            if (null !== $this->mailTemplate && $this->request->isMethod(Request::METHOD_POST)) {
                if ((empty($senderName) || empty($senderEmail) || empty($subject))) {
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
                        empty($header) ? null : $entityManager->find(MailHeader::class, $header),
                        empty($footer) ? null : $entityManager->find(MailFooter::class, $footer)
                    );

                    $_SESSION['freeow']['title']   = 'Modification d\'un mail';
                    $_SESSION['freeow']['message'] = 'Le mail a bien été modifié';
                }

                header('Location: ' . $this->url . '/mails');
                die;
            }

            /** @var TranslatorInterface $translator */
            $translator      = $this->get('translator');
            $titleLabel      = Translations::SECTION_MAIL_TITLE . TranslationLoader::SECTION_SEPARATOR . $this->mailTemplate->getName();
            $this->mailTitle = $translator->trans($titleLabel);
            $this->mailTitle = $this->mailTitle === $titleLabel ? '' : $this->mailTitle;

            $this->headers = $this->get('doctrine')->getRepository(MailHeader::class)->findAll();
            $this->footers = $this->get('doctrine')->getRepository(MailFooter::class)->findAll();
        }
    }

    public function _preview()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $content = $this->request->request->get('content', '');

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $this->sendAjaxResponse(true, ['content' => $content]);
    }

    public function _emailhistory()
    {
        $this->menu_admin = 'mailshistory';

        /** @var MailQueueManager $mailQueueManager */
        $mailQueueManager = $this->get('unilend.service.mail_queue');

        if (isset($_POST['form_send_search'])) {
            $clientId  = isset($_POST['id_client']) && false === empty($_POST['id_client']) ? $_POST['id_client'] : null;
            $from      = isset($_POST['from'])      && false === empty($_POST['from']) ? $_POST['from'] : null;
            $recipient = isset($_POST['to'])        && false === empty($_POST['to']) ? $_POST['to'] : null;
            $subject   = isset($_POST['subject'])   && false === empty($_POST['subject']) ? $_POST['subject'] : null;
            $startDate = isset($_POST['date_from']) && false === empty($_POST['date_from']) ? DateTime::createFromFormat('d/m/Y', $_POST['date_from']) : new DateTime('2013-01-01');
            $endDate   = isset($_POST['date_to'])   && false === empty($_POST['date_to']) ? DateTime::createFromFormat('d/m/Y', $_POST['date_to']) : new DateTime('NOW');

            $this->emails = $mailQueueManager->searchSentEmails($clientId, $from, $recipient, $subject, $startDate, $endDate);
        }
    }

    public function _recherche()
    {
        $this->menu_admin = 'mailshistory';

        $this->hideDecoration();
    }

    public function _header(): void
    {
        $this->handleMailPart(MailHeader::class);
    }

    public function _footer(): void
    {
        $this->handleMailPart(MailFooter::class);
    }

    public function _email_history_preview()
    {
        $this->hideDecoration();

        /** @var MailQueueManager $mailQueueManager */
        $mailQueueManager = $this->get('unilend.service.mail_queue');

        try {
            $mailQueueId = filter_var($this->params[0], FILTER_SANITIZE_NUMBER_INT);
            /** @var MailQueue $mailQueue */
            $mailQueue = $this->get('doctrine.orm.entity_manager')
                ->getRepository(MailQueue::class)->find($mailQueueId);
            if (null === $mailQueue) {
                $this->errorMessage = 'L\'email que vous avez demandé n\'existe pas.';

                return;
            }
            $email  = $mailQueueManager->getMessage($mailQueue);
            $sentAt = $mailQueue->getSentAt();
            $from   = $email->getFrom();
            $to     = $email->getTo();

            $this->email = [
                'date'    => $sentAt->format('d/m/Y H:i'),
                'from'    => array_shift($from),
                'to'      => array_shift($to),
                'subject' => $email->getSubject(),
                'body'    => $email->getBody(),
            ];
        } catch (Exception $exception) {
            $this->errorMessage = 'Impossible d\'afficher le mail';
        }
    }

    /**
     * @param string $class
     */
    private function handleMailPart($class)
    {
        $id = $this->params[0];

        $manager = $this->get('doctrine')->getManager();

        $repository = $manager->getRepository($class);

        $part = $repository->find($id);

        $this->mailPart = $part;

        if ($this->request->isMethod(Request::METHOD_POST)) {
            $part->setContent($this->request->get('content'));

            $manager->persist($part);
            $manager->flush($part);
        }
    }
}
