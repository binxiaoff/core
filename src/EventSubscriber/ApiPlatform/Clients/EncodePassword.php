<?php

declare(strict_types=1);

namespace Unilend\EventSubscriber\ApiPlatform\Clients;

use ApiPlatform\Core\EventListener\EventPriorities;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Unilend\Entity\Clients;

class EncodePassword implements EventSubscriberInterface
{
    /** @var UserPasswordEncoderInterface */
    private $encoder;

    /**
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['encodePassword', EventPriorities::PRE_WRITE],
        ];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     */
    public function encodePassword(ViewEvent $event)
    {
        $object = $event->getControllerResult();

        if ($object instanceof Clients && ($plainPassword = $object->getPlainPassword())) {
            $object->setPassword($this->encoder->encodePassword($object, $plainPassword));
            $object->eraseCredentials();
        }

        $event->setControllerResult($object);
    }
}
