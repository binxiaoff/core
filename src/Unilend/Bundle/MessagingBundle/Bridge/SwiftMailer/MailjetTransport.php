<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Mailjet\Client;
use Mailjet\Resources;
use Mailjet\Response;
use Psr\Log\LoggerInterface;
use Swift_Events_EventListener;
use Swift_Mime_Message;

class MailjetTransport implements \Swift_Transport
{
    /** The event dispatching layer */
    private $eventDispatcher;
    /** @var Client */
    private $mailJetClient;
    /** @var array */
    private $spool = [];
    /** @var  LoggerInterface */
    private $logger;

    public function __construct(\Swift_Events_EventDispatcher $dispatcher, Client $mailJetClient, LoggerInterface $logger)
    {
        $this->eventDispatcher = $dispatcher;
        $this->mailJetClient   = $mailJetClient;
        $this->logger          = $logger;
    }

    /**
     * Tests if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return true;
    }

    public function ping()
    {
    }

    /**
     * Starts this Transport mechanism.
     */
    public function start()
    {
    }

    /**
     * Stops this Transport mechanism.
     *
     * @return Response
     */
    public function stop()
    {
        $response    = $this->mailJetClient->post(Resources::$Email, ['body' => ['Messages' => $this->spool]]);
        $this->spool = [];

        return $response;
    }

    /**
     * @param \Swift_Mime_SimpleMessage $message
     * @param string[]           $failedRecipients
     *
     * @return int
     *
     * @throws \Exception
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $replyTo = $message->getReplyTo();
        $body    = [
            'From'     => $this->convertEmailsToArray($message->getFrom())[0],
            'To'       => $this->convertEmailsToArray($message->getTo()),
            'Subject'  => $message->getSubject(),
            'HTMLPart' => $message->getBody()
        ];

        if (method_exists($message, 'getQueueId') && null !== $message->getQueueId()) {
            $body['CustomID'] = (string) $message->getQueueId();
        }

        if (false === empty($replyTo)) {
            $body['ReplyTo'] = $this->convertEmailsToArray($replyTo)[0];
        }

        if (false === empty($message->getChildren())) {
            $body['Attachments'] = [];
            foreach ($message->getChildren() as $child) {
                if (1 === preg_match('/^(?<content_type>.*); name=(?<file_name>.*)$/', $child->getHeaders()->get('Content-Type')->getFieldBody(), $matches)) {
                    $body['Attachments'][] = [
                        'ContentType'   => $matches['content_type'],
                        'Filename'      => $matches['file_name'],
                        'Base64Content' => base64_encode($child->getBody())
                    ];
                }
            }
        }

        $this->spool[] = $body;

        return 1;
    }

    /**
     * Register a plugin.
     *
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->eventDispatcher->bindEventListener($plugin);
    }

    /**
     * @param string|array $emails
     *
     * @return array
     */
    private function convertEmailsToArray($emails) : array
    {
        $formattedEmails = [];

        if (is_string($emails)) {
            $emails = str_replace(',', ';', $emails);
            $emails = explode(';', $emails);
        }

        foreach ($emails as $email => $name) {
            if (is_int($email)) {
                $email = $name;
                $name  = null;
            }

            if (1 === preg_match('#^(?<name>.*)\s?\<(?<email>.*)\>$#', trim($email), $matches)) {
                $email = $matches['email'];
                $name  = $matches['name'];
            }

            $formattedEmail = [
                'Email' => $email
            ];

            if (false === empty($name)) {
                $formattedEmail['Name'] = $name;
            }

            $formattedEmails[] = $formattedEmail;
        }

        return $formattedEmails;
    }
}
