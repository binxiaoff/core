<?php


namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Unilend\Bundle\CoreBusinessBundle\Service\NotificationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;

class LoginSubscriber implements EventSubscriberInterface
{
    /** @var EntityManager  */
    private $entityManager;
    /** @var NotificationManager  */
    private $notificationManager;

    public function __construct(EntityManager $entityManager, NotificationManager $notificationManager)
    {
        $this->entityManager       = $entityManager;
        $this->notificationManager = $notificationManager;
    }

    public static function getSubscribedEvents()
    {
        return array(
            SecurityEvents::INTERACTIVE_LOGIN            => 'onInteractiveLogin',
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure'
        );
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var BaseUser $user */
        $user = $event->getAuthenticationToken()->getUser();
        /** @var \clients $clients */
        $clients = $this->entityManager->getRepository('clients');
        $clients->saveLogin(new \DateTime('NOW'), $user->getUsername(), $user->getPassword());
        $clients->get($user->getClientId());

        /** @var \clients_gestion_notifications $clientNotificationSettings */
        $clientNotificationSettings = $this->entityManager->getRepository('clients_gestion_notifications');
        if (false === $clientNotificationSettings->select('id_client = ' . $user->getClientId())){
            $this->notificationManager->generateDefaultNotificationSettings($clients);
        }
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $credentials = $event->getAuthenticationToken()->getCredentials();
        /** @var \login_log $loginLog */
        $loginLog = $this->entityManager->getRepository('login_log');

        $loginLog->pseudo      = $credentials['username'];
        $loginLog->IP          = $_SERVER['REMOTE_ADDR']; //TODO register the IP in the user or find any other way to access the request
        $loginLog->date_action = date('Y-m-d H:i:s');
        $loginLog->statut      = 0;
        $loginLog->retour      = $event->getAuthenticationException()->getMessageKey();
        $loginLog->create();
    }
}