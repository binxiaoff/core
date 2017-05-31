<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;
use Xynnn\GoogleTagManagerBundle\Service\GoogleTagManager;

/**
 * UserIdCollector
 */
class DataLayerCollector
{
    const SESSION_KEY_LENDER_CLIENT_ID   = 'datalayer_lender_client_id';
    const SESSION_KEY_BORROWER_CLIENT_ID = 'datalayer_borrower_client_id';
    const SESSION_KEY_CLIENT_EMAIL       = 'datalayer_client_email';
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    private $requestStack;
    private $googleTagManager;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param RequestStack          $requestStack
     * @param GoogleTagManager      $googleTagManager
     */
    public function __construct(TokenStorageInterface $tokenStorage, RequestStack $requestStack, GoogleTagManager $googleTagManager)
    {
        $this->tokenStorage     = $tokenStorage;
        $this->requestStack     = $requestStack;
        $this->googleTagManager = $googleTagManager;
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $data = [];
            $user = $token->getUser();

            if ($user instanceof BaseUser) {
                $data = ['uid' => md5($user->getEmail()), 'unique_id' => md5($user->getEmail())];

                if ($user instanceof UserLender) {
                    $data['ID_Preteur'] = $user->getClientId();
                } elseif ($user instanceof UserPartner) {
                    $data['ID_Partenaire'] = $user->getPartner()->getId();
                    $data['ID_Client']     = $user->getClientId();
                    $data['Organisation']  = $user->getCompany()->getName();
                    $data['Role']          = in_array(UserPartner::ROLE_ADMIN, $user->getRoles()) ? 'Administrateur' : 'Collaborateur';
                } else {
                    $data['ID_Emprunteur'] = $user->getClientId();
                }
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

                $this->collectSources($session, $data);
            }

            foreach ($data as $item => $value) {
                $this->googleTagManager->addData($item, $value);
            }
        }
    }

    private function collectSources(SessionInterface $session, &$data)
    {
        if ($session->has(SourceManager::SOURCE1)) {
            $data['utm_source'] = $session->get(SourceManager::SOURCE1);
        }
        if ($session->has(SourceManager::SOURCE2)) {
            $data['utm_source2'] = $session->get(SourceManager::SOURCE2);
        }
        if ($session->has(SourceManager::SOURCE3)) {
            $data['utm_campaign'] = $session->get(SourceManager::SOURCE3);
        }
        if ($session->has(SourceManager::ENTRY_SLUG)) {
            $data['slug_origine'] = $session->get(SourceManager::ENTRY_SLUG);
        }
    }
}
