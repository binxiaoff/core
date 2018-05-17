<?php

namespace Unilend\Bundle\CoreBusinessBundle\Event;

use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, Users
};
use Unilend\Bundle\CoreBusinessBundle\Service\ClientAuditer;
use Welp\MailchimpBundle\Event\WebhookEvent;
use Welp\MailchimpBundle\Provider\ProviderInterface;


class MailChimpEventSubscriber implements EventSubscriberInterface, ProviderInterface
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ClientAuditer */
    private $clientAuditer;

    /**
     * @param EntityManager $entityManager
     * @param ClientAuditer $clientAuditer
     */
    public function __construct(EntityManager $entityManager, ClientAuditer $clientAuditer)
    {
        $this->entityManager = $entityManager;
        $this->clientAuditer = $clientAuditer;
    }

    /**
     * @inheritdoc
     */
    public function getSubscribers(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WebhookEvent::EVENT_SUBSCRIBE   => 'subscribe',
            WebhookEvent::EVENT_UNSUBSCRIBE => 'unsubscribe',
            WebhookEvent::EVENT_UPEMAIL     => 'upemail'
        ];
    }

    /**
     * @param WebhookEvent $event
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function subscribe(WebhookEvent $event): void
    {
        $this->updateEnrolment($event, Clients::NEWSLETTER_OPT_IN_ENROLLED);
    }

    /**
     * @param WebhookEvent $event
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function unsubscribe(WebhookEvent $event): void
    {
        $this->updateEnrolment($event, Clients::NEWSLETTER_OPT_IN_NOT_ENROLLED);
    }

    /**
     * @param WebhookEvent $event
     * @param int          $enrolmentStatus
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateEnrolment(WebhookEvent $event, int $enrolmentStatus): void
    {
        if (
            is_array($event->getData())
            && isset($event->getData()['email'])
            && false !== filter_var($event->getData()['email'], FILTER_VALIDATE_EMAIL)
        ) {
            $frontUser        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT);
            $clientRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
            $clients          = $clientRepository->findBy(['email' => $event->getData()['email']]);

            foreach ($clients as $client) {
                $client->setOptin1($enrolmentStatus);
                $this->clientAuditer->logChanges($client, $frontUser);
            }

            $this->entityManager->flush();
        }
    }
}
