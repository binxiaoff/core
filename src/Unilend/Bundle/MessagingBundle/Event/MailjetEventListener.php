<?php

namespace Unilend\Bundle\MessagingBundle\Event;

use Knp\Bundle\MailjetBundle\Event\Adapter\BlockedEvent;
use Knp\Bundle\MailjetBundle\Event\Adapter\BounceEvent;
use Knp\Bundle\MailjetBundle\Event\Adapter\ClickEvent;
use Knp\Bundle\MailjetBundle\Event\Adapter\OpenEvent;
use Knp\Bundle\MailjetBundle\Event\Adapter\SpamEvent;
use Knp\Bundle\MailjetBundle\Event\Adapter\TypofixEvent;
use Knp\Bundle\MailjetBundle\Event\Adapter\UnsubEvent;
use Knp\Bundle\MailjetBundle\Event\Listener\EventListenerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

/**
 * @package Unilend\Bundle\MessagingBundle\Event
 * https://dev.mailjet.com/guides/#event-api-real-time-notifications
 */
class MailjetEventListener implements EventListenerInterface
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onOpenEvent(OpenEvent $event)
    {
        $data = $event->getData();

        /** @var \mailjet_event_open $openEvent */
        $openEvent                  = $this->entityManager->getRepository('mailjet_event_open');
        $openEvent->time            = $data['time'];
        $openEvent->email           = $data['email'];
        $openEvent->campaign_id     = $data['mj_campaign_id'];
        $openEvent->contact_id      = $data['mj_contact_id'];
        $openEvent->custom_campaign = $data['customcampaign'];
        $openEvent->message_id      = $data['MessageID'];
        $openEvent->custom_id       = $data['CustomID'];
        $openEvent->payload         = $data['Payload'];
        $openEvent->ip              = $data['ip'];
        $openEvent->geo             = $data['geo'];
        $openEvent->agent           = $data['agent'];
        $openEvent->create();
    }

    public function onSpamEvent(SpamEvent $event)
    {
        $data = $event->getData();

        /** @var \mailjet_event_spam $spamEvent */
        $spamEvent = $this->entityManager->getRepository('mailjet_event_spam');
        $spamEvent->time            = $data['time'];
        $spamEvent->email           = $data['email'];
        $spamEvent->campaign_id     = $data['mj_campaign_id'];
        $spamEvent->contact_id      = $data['mj_contact_id'];
        $spamEvent->custom_campaign = $data['customcampaign'];
        $spamEvent->message_id      = $data['MessageID'];
        $spamEvent->custom_id       = $data['CustomID'];
        $spamEvent->payload         = $data['Payload'];
        $spamEvent->source          = $data['source'];
        $spamEvent->create();
    }

    public function onClickEvent(ClickEvent $event)
    {
        $data = $event->getData();

        /** @var \mailjet_event_click $clickEvent */
        $clickEvent                  = $this->entityManager->getRepository('mailjet_event_click');
        $clickEvent->time            = $data['time'];
        $clickEvent->email           = $data['email'];
        $clickEvent->campaign_id     = $data['mj_campaign_id'];
        $clickEvent->contact_id      = $data['mj_contact_id'];
        $clickEvent->custom_campaign = $data['customcampaign'];
        $clickEvent->message_id      = $data['MessageID'];
        $clickEvent->custom_id       = $data['CustomID'];
        $clickEvent->payload         = $data['Payload'];
        $clickEvent->url             = $data['url'];
        $clickEvent->create();
    }

    public function onUnsubEvent(UnsubEvent $event)
    {
        $data = $event->getData();

        /** @var \mailjet_event_unsub $unsubEvent */
        $unsubEvent                  = $this->entityManager->getRepository('mailjet_event_unsub');
        $unsubEvent->time            = $data['time'];
        $unsubEvent->email           = $data['email'];
        $unsubEvent->campaign_id     = $data['mj_campaign_id'];
        $unsubEvent->contact_id      = $data['mj_contact_id'];
        $unsubEvent->custom_campaign = $data['customcampaign'];
        $unsubEvent->message_id      = $data['MessageID'];
        $unsubEvent->custom_id       = $data['CustomID'];
        $unsubEvent->payload         = $data['Payload'];
        $unsubEvent->list_id         = $data['mj_list_id'];
        $unsubEvent->ip              = $data['ip'];
        $unsubEvent->geo             = $data['geo'];
        $unsubEvent->agent           = $data['agent'];
        $unsubEvent->create();
    }

    public function onBounceEvent(BounceEvent $event)
    {
        $data = $event->getData();

        /** @var \mailjet_event_bounce $bounceEvent */
        $bounceEvent                   = $this->entityManager->getRepository('mailjet_event_bounce');
        $bounceEvent->time             = $data['time'];
        $bounceEvent->email            = $data['email'];
        $bounceEvent->campaign_id      = $data['mj_campaign_id'];
        $bounceEvent->contact_id       = $data['mj_contact_id'];
        $bounceEvent->custom_campaign  = $data['customcampaign'];
        $bounceEvent->message_id       = $data['MessageID'];
        $bounceEvent->custom_id        = $data['CustomID'];
        $bounceEvent->payload          = $data['Payload'];
        $bounceEvent->blocked          = $data['blocked'];
        $bounceEvent->hard_bounce      = $data['hard_bounce'];
        $bounceEvent->error_related_to = $data['error_related_to'];
        $bounceEvent->error            = $data['error'];
        $bounceEvent->create();
    }

    public function onBlockedEvent(BlockedEvent $event)
    {
        $data = $event->getData();

        /** @var \mailjet_event_blocked $blockedEvent */
        $blockedEvent                   = $this->entityManager->getRepository('mailjet_event_blocked');
        $blockedEvent->time             = $data['time'];
        $blockedEvent->email            = $data['email'];
        $blockedEvent->campaign_id      = $data['mj_campaign_id'];
        $blockedEvent->contact_id       = $data['mj_contact_id'];
        $blockedEvent->custom_campaign  = $data['customcampaign'];
        $blockedEvent->message_id       = $data['MessageID'];
        $blockedEvent->custom_id        = $data['CustomID'];
        $blockedEvent->payload          = $data['Payload'];
        $blockedEvent->error_related_to = $data['error_related_to'];
        $blockedEvent->error            = $data['error'];
        $blockedEvent->create();
    }

    public function onTypofixEvent(TypofixEvent $event)
    {
        $data = $event->getData();

        /** @var \mailjet_event_typofix $typofixEvent */
        $typofixEvent                   = $this->entityManager->getRepository('mailjet_event_typofix');
        $typofixEvent->time             = $data['time'];
        $typofixEvent->email            = $data['email'];
        $typofixEvent->campaign_id      = $data['mj_campaign_id'];
        $typofixEvent->contact_id       = $data['mj_contact_id'];
        $typofixEvent->custom_campaign  = $data['customcampaign'];
        $typofixEvent->message_id       = $data['MessageID'];
        $typofixEvent->custom_id        = $data['CustomID'];
        $typofixEvent->payload          = $data['Payload'];
        $typofixEvent->original_address = $data['original_address'];
        $typofixEvent->new_address      = $data['new_address'];
        $typofixEvent->create();
    }
}
