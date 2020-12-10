<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\ApiPlatform\MessageThread;

use ApiPlatform\Core\EventListener\EventPriorities;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Entity\MessageStatus;
use Unilend\Entity\MessageThread;
use Unilend\Repository\MessageStatusRepository;

class MessageThreadViewedSubscriber implements EventSubscriberInterface
{
    /** @var Security */
    private Security $security;

    /** @var MessageStatusRepository */
    private MessageStatusRepository $messageStatusRepository;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /**
     * MessageThreadViewedSubscriber constructor.
     *
     * @param Security                $security
     * @param MessageStatusRepository $messageStatusRepository
     * @param EntityManagerInterface  $entityManager
     */
    public function __construct(Security $security, MessageStatusRepository $messageStatusRepository, EntityManagerInterface $entityManager)
    {
        $this->security                = $security;
        $this->messageStatusRepository = $messageStatusRepository;
        $this->entityManager           = $entityManager;
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
        if (!$messageThread instanceof MessageThread) {
            return;
        }

        $staff  = $this->security->getUser()->getCurrentStaff();

        $messageStatuses = $this->messageStatusRepository->findUnreadStatusByRecipientAndMessageThread($staff, $messageThread);
        foreach ($messageStatuses as $messageStatus) {
            $messageStatus->setStatus(MessageStatus::STATUS_READ);
            $this->entityManager->persist($messageStatus);
        }
        $this->entityManager->flush();
    }
}
