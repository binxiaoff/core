<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\ApiPlatform\MessageThread;

use ApiPlatform\Core\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Unilend\Core\Entity\MessageThread;
use Unilend\Core\Repository\MessageStatusRepository;

class MessageThreadViewedSubscriber implements EventSubscriberInterface
{
    /** @var Security */
    private Security $security;

    /** @var MessageStatusRepository */
    private MessageStatusRepository $messageStatusRepository;

    /**
     * MessageThreadViewedSubscriber constructor.
     *
     * @param Security                $security
     * @param MessageStatusRepository $messageStatusRepository
     */
    public function __construct(Security $security, MessageStatusRepository $messageStatusRepository)
    {
        $this->security                = $security;
        $this->messageStatusRepository = $messageStatusRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['markAsViewedMessageThreadMessageStatus', EventPriorities::POST_READ]];
    }

    /**
     * @param RequestEvent $event
     */
    public function markAsViewedMessageThreadMessageStatus(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (Request::METHOD_GET !== $request->getMethod()) {
            return;
        }

        $messageThread = $request->attributes->get('data');
        if (false === $messageThread instanceof MessageThread) {
            return;
        }

        $staff  = $this->security->getUser()->getCurrentStaff();

        $this->messageStatusRepository->setMessageStatusesToReadForRecipientAndMessageThread($staff, $messageThread);
    }
}
