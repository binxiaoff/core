<?php
/**
 * Created by PhpStorm.
 * User: Bin
 * Date: 2016/4/23
 * Time: 22:10
 */

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Mailjet\Client;
use Mailjet\Resources;
use Swift_Events_EventListener;
use Swift_Mime_Message;

class MailjetTransport implements \Swift_Transport
{
    /** The event dispatching layer */
    private $oEventDispatcher;
    /** @var  Client */
    private $oMailJetClient;

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
     * @param Swift_Mime_Message $oMessage
     * @param string[]           $aFailedRecipients
     *
     * @return \Mailjet\Response
     */
    public function send(Swift_Mime_Message $oMessage, &$aFailedRecipients = null)
    {
        $aSenderEmail = array_keys($oMessage->getFrom());
        $aSenderName  = array_values($oMessage->getFrom());
        $aRecipients  = array_keys($oMessage->getTo());
        $body = [
            'FromEmail'  => array_shift($aSenderEmail),
            'FromName'   => array_shift($aSenderName),
            'Subject'    => $oMessage->getSubject(),
            'Html-part'  => $oMessage->getBody(),
            'Recipients' => [['Email' => array_shift($aRecipients)]]
        ];

        return $this->oMailJetClient->post(Resources::$Email, ['body' => $body]);
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
