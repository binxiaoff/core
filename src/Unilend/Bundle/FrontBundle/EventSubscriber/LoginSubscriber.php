<?php


namespace Unilend\Bundle\FrontBundle\EventSubscriber;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;

class LoginSubscriber implements EventSubscriberInterface
{
    /** @var EntityManager  */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var BaseUser $user */
        $user = $event->getAuthenticationToken()->getUser();
        /** @var \clients $clients */
        $clients = $this->entityManager->getRepository('clients');
        $clients->saveLogin(new \DateTime('NOW'), $user->getUsername(), $user->getPassword());
    }

    public static function getSubscribedEvents()
    {
        return array(SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin');
    }


}