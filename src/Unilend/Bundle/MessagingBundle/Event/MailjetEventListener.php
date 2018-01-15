<?php

namespace Unilend\Bundle\MessagingBundle\Event;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Knp\Bundle\MailjetBundle\Event\Adapter\{
    BlockedEvent,
    BounceEvent,
    ClickEvent,
    OpenEvent,
    SentEvent,
    SpamEvent,
    UnsubEvent
};
use Knp\Bundle\MailjetBundle\Event\Listener\EventListenerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    MailjetEventBlocked,
    MailjetEventBounce,
    MailjetEventClick,
    MailjetEventOpen,
    MailjetEventSpam,
    MailjetEventUnsub
};

/**
 * @package Unilend\Bundle\MessagingBundle\Event
 * @link    https://dev.mailjet.com/guides/#event-api-real-time-notifications
 */
class MailjetEventListener implements EventListenerInterface
{
    /** @var EntityManager */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager   $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @param SentEvent $event
     */
    public function onSentEvent(SentEvent $event)
    {
        // "sent" event is not interesting for us as we can use `mail_queue`
    }

    /**
     * @param OpenEvent $event
     */
    public function onOpenEvent(OpenEvent $event)
    {
        $event = $event->getEvent();

        $openEvent = new MailjetEventOpen();
        $openEvent
            ->setTime($event->getTime())
            ->setEmail($event->getEmail())
            ->setCampaignId($event->getCampaignId())
            ->setContactId($event->getContactId())
            ->setCustomCampaign($event->getCustomCampaign())
            ->setMessageId($event->getMessageId())
            ->setCustomId($event->getCustomId())
            ->setPayload($event->getPayload())
            ->setIp($event->getIp())
            ->setGeo($event->getGeo())
            ->setAgent($event->getUserAgent());

        try {
            $this->entityManager->persist($openEvent);
            $this->entityManager->flush($openEvent);
        } catch (ORMException $exception) {
            $this->logger->error(
                'Could not save Mailjet "open" event',
                ['file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }

    /**
     * @param SpamEvent $event
     */
    public function onSpamEvent(SpamEvent $event)
    {
        $event = $event->getEvent();

        $spamEvent = new MailjetEventSpam();
        $spamEvent
            ->setTime($event->getTime())
            ->setEmail($event->getEmail())
            ->setCampaignId($event->getCampaignId())
            ->setContactId($event->getContactId())
            ->setCustomCampaign($event->getCustomCampaign())
            ->setMessageId($event->getMessageId())
            ->setCustomId($event->getCustomId())
            ->setPayload($event->getPayload())
            ->setSource($event->getSource());

        try {
            $this->entityManager->persist($spamEvent);
            $this->entityManager->flush($spamEvent);
        } catch (ORMException $exception) {
            $this->logger->error(
                'Could not save Mailjet "open" event',
                ['file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }

    /**
     * @param ClickEvent $event
     */
    public function onClickEvent(ClickEvent $event)
    {
        $event = $event->getEvent();

        $clickEvent = new MailjetEventClick();
        $clickEvent
            ->setTime($event->getTime())
            ->setEmail($event->getEmail())
            ->setCampaignId($event->getCampaignId())
            ->setContactId($event->getContactId())
            ->setCustomCampaign($event->getCustomCampaign())
            ->setMessageId($event->getMessageId())
            ->setCustomId($event->getCustomId())
            ->setPayload($event->getPayload())
            ->seturl($event->geturl());

        try {
            $this->entityManager->persist($clickEvent);
            $this->entityManager->flush($clickEvent);
        } catch (ORMException $exception) {
            $this->logger->error(
                'Could not save Mailjet "open" event',
                ['file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }

    /**
     * @param UnsubEvent $event
     */
    public function onUnsubEvent(UnsubEvent $event)
    {
        $event = $event->getEvent();

        $unsubEvent = new MailjetEventUnsub();
        $unsubEvent
            ->setTime($event->getTime())
            ->setEmail($event->getEmail())
            ->setCampaignId($event->getCampaignId())
            ->setContactId($event->getContactId())
            ->setCustomCampaign($event->getCustomCampaign())
            ->setMessageId($event->getMessageId())
            ->setCustomId($event->getCustomId())
            ->setPayload($event->getPayload())
            ->setListId($event->getListId())
            ->setIp($event->getIp())
            ->setGeo($event->getGeo())
            ->setAgent($event->getUserAgent());

        try {
            $this->entityManager->persist($unsubEvent);
            $this->entityManager->flush($unsubEvent);
        } catch (ORMException $exception) {
            $this->logger->error(
                'Could not save Mailjet "open" event',
                ['file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }

    /**
     * @param BounceEvent $event
     */
    public function onBounceEvent(BounceEvent $event)
    {
        $event = $event->getEvent();

        $bounceEvent = new MailjetEventBounce();
        $bounceEvent
            ->setTime($event->getTime())
            ->setEmail($event->getEmail())
            ->setCampaignId($event->getCampaignId())
            ->setContactId($event->getContactId())
            ->setCustomCampaign($event->getCustomCampaign())
            ->setMessageId($event->getMessageId())
            ->setCustomId($event->getCustomId())
            ->setPayload($event->getPayload())
            ->setBlocked($event->isBlocked())
            ->setHardBounce($event->isHardBounce())
            ->setErrorRelatedTo($event->getErrorExplanation())
            ->setError($event->getError());

        try {
            $this->entityManager->persist($bounceEvent);
            $this->entityManager->flush($bounceEvent);
        } catch (ORMException $exception) {
            $this->logger->error(
                'Could not save Mailjet "open" event',
                ['file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }

    /**
     * @param BlockedEvent $event
     */
    public function onBlockedEvent(BlockedEvent $event)
    {
        $event = $event->getEvent();

        $blockedEvent = new MailjetEventBlocked();
        $blockedEvent->setTime($event->getTime())
            ->setEmail($event->getEmail())
            ->setCampaignId($event->getCampaignId())
            ->setContactId($event->getContactId())
            ->setCustomCampaign($event->getCustomCampaign())
            ->setMessageId($event->getMessageId())
            ->setCustomId($event->getCustomId())
            ->setPayload($event->getPayload())
            ->setErrorRelatedTo($event->getErrorExplanation())
            ->setError($event->getError());

        try {
            $this->entityManager->persist($blockedEvent);
            $this->entityManager->flush($blockedEvent);
        } catch (ORMException $exception) {
            $this->logger->error(
                'Could not save Mailjet "open" event',
                ['file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }
}
