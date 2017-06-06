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
     * @param Swift_Mime_Message $message
     * @param string[]           $failedRecipients
     *
     * @return int
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {

        $count = (
            count((array) $message->getTo())
            + count((array) $message->getCc())
            + count((array) $message->getBcc())
        );

        if (0 === $count) {
            $completeTrace = debug_backtrace();
            $backtrace     = [];

            foreach ($completeTrace as $key => $trace){
                $backtrace[$key]['file'] = isset($trace['file']) ? $trace['file'] : '';
                $backtrace[$key]['line'] = isset($trace['line']) ? $trace['line'] : '';
            }

            $this->logger->error('email address empty : ', ['template' => $message->getSubject(), 'backtrace'  => $backtrace]);

            return 0;
        }

        $senderEmail = array_keys($message->getFrom());
        $senderName  = array_values($message->getFrom());
        $recipients  = array_keys($message->getTo());
        $replyTo     = $message->getReplyTo();
        $body        = [
            'FromEmail'   => array_shift($senderEmail),
            'FromName'    => array_shift($senderName),
            'Subject'     => $message->getSubject(),
            'Html-part'   => $message->getBody(),
            'Recipients'  => array_map(function($recipient) { return ['Email' => $recipient]; }, $recipients)
        ];

        if (method_exists($message, 'getQueueId') && null !== $message->getQueueId()) {
            $body['Mj-CustomID'] = $message->getQueueId();
        }

        if (is_array($replyTo)) {
            $body['Headers']['Reply-To'] = TemplateMessage::emailAddressToString($replyTo);
        }

        if (false === empty($message->getChildren())) {
            $body['Attachments'] = [];
            foreach ($message->getChildren() as $child) {
                if (1 === preg_match('/^(?<content_type>.*); name=(?<file_name>.*)$/', $child->getHeaders()->get('Content-Type')->getFieldBody(), $matches)) {
                    $body['Attachments'][] = [
                        'Content-Type' => $matches['content_type'],
                        'Filename'     => $matches['file_name'],
                        'content'      => base64_encode($child->getBody())
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
}
