<?php

declare(strict_types=1);

namespace Unilend\Service\GoogleTagManager;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\Clients;
use Xynnn\GoogleTagManagerBundle\Service\GoogleTagManagerInterface;

class DataLayerCollector
{
    private const SESSION_KEY_LENDER_CLIENT_ID   = 'datalayer_lender_client_id';
    private const SESSION_KEY_BORROWER_CLIENT_ID = 'datalayer_borrower_client_id';
    private const SESSION_KEY_CLIENT_EMAIL       = 'datalayer_client_email';

    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RequestStack */
    private $requestStack;
    /** @var GoogleTagManagerInterface */
    private $googleTagManager;

    /**
     * @param TokenStorageInterface     $tokenStorage
     * @param RequestStack              $requestStack
     * @param GoogleTagManagerInterface $googleTagManager
     */
    public function __construct(TokenStorageInterface $tokenStorage, RequestStack $requestStack, GoogleTagManagerInterface $googleTagManager)
    {
        $this->tokenStorage     = $tokenStorage;
        $this->requestStack     = $requestStack;
        $this->googleTagManager = $googleTagManager;
    }

    /**
     * Collect the data from session and set them to Google Tag Manager Data Layer.
     */
    public function collect()
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $data = [];
            /** @var Clients $client */
            $client = $token->getUser();

            if ($client instanceof Clients) {
                $data = ['uid' => md5($client->getEmail()), 'unique_id' => md5($client->getEmail())];
            } else {
                $session = $this->requestStack->getCurrentRequest()->getSession();

                if ($session->has(self::SESSION_KEY_LENDER_CLIENT_ID)) {
                    $data['ID_Preteur'] = $session->get(self::SESSION_KEY_LENDER_CLIENT_ID);
                }

                if ($session->has(self::SESSION_KEY_BORROWER_CLIENT_ID)) {
                    $data['ID_Emprunteur'] = $session->get(self::SESSION_KEY_BORROWER_CLIENT_ID);
                }

                if ($session->has(self::SESSION_KEY_CLIENT_EMAIL)) {
                    $data['unique_id'] = md5($session->get(self::SESSION_KEY_CLIENT_EMAIL));
                }
            }

            foreach ($data as $item => $value) {
                $this->googleTagManager->setData($item, $value);
            }
        }
    }
}
