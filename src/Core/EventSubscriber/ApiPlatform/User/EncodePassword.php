<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber\ApiPlatform\User;

use ApiPlatform\Core\EventListener\EventPriorities;
use Exception;
use KLS\Core\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class EncodePassword implements EventSubscriberInterface
{
    /** @var UserPasswordEncoderInterface */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['encodePassword', EventPriorities::PRE_WRITE],
        ];
    }

    /**
     * @throws Exception
     */
    public function encodePassword(ViewEvent $event)
    {
        $object = $event->getControllerResult();

        if ($object instanceof User && ($plainPassword = $object->getPlainPassword())) {
            $object->setPassword($this->encoder->encodePassword($object, $plainPassword));
            $object->eraseCredentials();
        }

        $event->setControllerResult($object);
    }
}
