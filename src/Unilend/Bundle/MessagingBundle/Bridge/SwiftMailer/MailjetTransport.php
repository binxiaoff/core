<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Mailjet\Client;
use Mailjet\Resources;
use Swift_Events_EventListener;
use Swift_Mime_Message;

class MailjetTransport implements \Swift_Transport
{
    /** The event dispatching layer */
    private $oEventDispatcher;
    /** @var Client */
    private $oMailJetClient;
    /** @var array */
    private $spool = [];

    public function __construct(\Swift_Events_EventDispatcher $oDispatcher, Client $oMailJetClient)
    {
        $this->oEventDispatcher = $oDispatcher;
        $this->oMailJetClient   = $oMailJetClient;
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
     */
    public function stop()
    {
    }

    /**
     * @param Swift_Mime_Message $message
     * @param string[]           $aFailedRecipients
     *
     * @return \Mailjet\Response
     */
    public function send(Swift_Mime_Message $message, &$aFailedRecipients = null)
    {
        $body = $this->getBody($message);
        return $this->oMailJetClient->post(Resources::$Email, ['body' => $body]);
    }

    /**
     * @param Swift_Mime_Message $message
     */
    public function add(Swift_Mime_Message $message)
    {
        $this->spool[] = $this->getBody($message);
    }

    /**
     * @return \Mailjet\Response
     */
    public function bulkSend()
    {
        return $this->oMailJetClient->post(Resources::$Email, ['body' => ['Messages' => $this->spool]]);
    }

    /**
     * @param TemplateMessage $message
     * @return array
     */
    private function getBody(TemplateMessage $message)
    {
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

        if (null !== $message->getMessageId()) {
            $body['Mj-CustomID'] = $message->getMessageId();
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

        return $body;
    }

    /**
     * Register a plugin.
     *
     * @param Swift_Events_EventListener $oPlugin
     */
    public function registerPlugin(Swift_Events_EventListener $oPlugin)
    {
        $this->oEventDispatcher->bindEventListener($oPlugin);
    }
}
