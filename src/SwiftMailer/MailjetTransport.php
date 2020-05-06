<?php

declare(strict_types=1);

namespace Unilend\SwiftMailer;

use Mailjet\{Client, Resources, Response};
use Swift_Events_EventDispatcher;
use Swift_Events_EventListener;
use Swift_Mime_SimpleMessage;
use Swift_Transport;
use Unilend\Entity\MailQueue;

class MailjetTransport implements Swift_Transport
{
    /** The event dispatching layer */
    private $eventDispatcher;
    /** @var Client */
    private $mailJetClient;
    /** @var array */
    private $spool = [];

    /**
     * @param Client                       $mailJetClient
     * @param Swift_Events_EventDispatcher $dispatcher
     */
    public function __construct(Client $mailJetClient, Swift_Events_EventDispatcher $dispatcher)
    {
        $this->eventDispatcher = $dispatcher;
        $this->mailJetClient   = $mailJetClient;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function ping()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $response    = $this->mailJetClient->post(Resources::$Email, ['body' => ['Messages' => $this->spool]]);
        $this->spool = [];

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $body = [
            'From'     => $this->convertEmailsToArray($message->getFrom())[0],
            'To'       => $this->convertEmailsToArray($message->getTo()),
            'Subject'  => $message->getSubject(),
            'HTMLPart' => $message->getBody(),
        ];

        if (method_exists($message, 'getQueueId') && null !== $message->getQueueId()) {
            $body['CustomID'] = (string) $message->getQueueId();
        }

        if (false === empty($replyTo = $message->getReplyTo())) {
            $body['ReplyTo'] = $this->convertEmailsToArray($replyTo)[0];
        }

        if (false === empty($message->getChildren())) {
            $body['Attachments'] = [];
            foreach ($message->getChildren() as $child) {
                if (1 === preg_match('/^(?<content_type>.*); name=(?<file_name>.*)$/', $child->getHeaders()->get('Content-Type')->getFieldBody(), $matches)) {
                    $body['Attachments'][] = [
                        'ContentType'   => $matches['content_type'],
                        'Filename'      => $matches['file_name'],
                        'Base64Content' => base64_encode($child->getBody()),
                    ];
                }
            }
        }

        $this->spool[] = $body;

        // CALS-1471 Do not use spool for the moment
        $this->stop();

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->eventDispatcher->bindEventListener($plugin);
    }

    /**
     * @param MailQueue $mailQueue
     * @param Response  $response
     *
     * @return int|null
     */
    public function getMessageId(MailQueue $mailQueue, Response $response)
    {
        $body = $response->getBody();

        if (false === empty($body['Messages'])) {
            foreach ($body['Messages'] as $message) {
                if (isset($message['CustomID'], $message['To'][0]['MessageID']) && (int) $message['CustomID'] === $mailQueue->getId()) {
                    return $message['To'][0]['MessageID'];
                }
            }
        }

        return null;
    }

    /**
     * @param string|array $emails
     *
     * @return array
     */
    private function convertEmailsToArray($emails): array
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

            if (1 === preg_match('#^(?<name>.*)\s?<(?<email>.*)>$#', trim($email), $matches)) {
                $email = $matches['email'];
                $name  = $matches['name'];
            }

            $formattedEmail = [
                'Email' => $email,
            ];

            if (false === empty($name)) {
                $formattedEmail['Name'] = $name;
            }

            $formattedEmails[] = $formattedEmail;
        }

        return $formattedEmails;
    }
}
