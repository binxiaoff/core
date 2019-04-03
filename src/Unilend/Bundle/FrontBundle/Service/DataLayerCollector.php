<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\PartnerManager;
use Xynnn\GoogleTagManagerBundle\Service\GoogleTagManagerInterface;

/**
 * UserIdCollector
 */
class DataLayerCollector
{
    const SESSION_KEY_LENDER_CLIENT_ID   = 'datalayer_lender_client_id';
    const SESSION_KEY_BORROWER_CLIENT_ID = 'datalayer_borrower_client_id';
    const SESSION_KEY_CLIENT_EMAIL       = 'datalayer_client_email';

    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RequestStack */
    private $requestStack;
    /** @var GoogleTagManagerInterface */
    private $googleTagManager;
    /** @var PartnerManager */
    private $partnerManager;

    /**
     * @param TokenStorageInterface     $tokenStorage
     * @param RequestStack              $requestStack
     * @param GoogleTagManagerInterface $googleTagManager
     * @param PartnerManager            $partnerManager
     */
    public function __construct(TokenStorageInterface $tokenStorage, RequestStack $requestStack, GoogleTagManagerInterface $googleTagManager, PartnerManager $partnerManager)
    {
        $this->tokenStorage     = $tokenStorage;
        $this->requestStack     = $requestStack;
        $this->googleTagManager = $googleTagManager;
        $this->partnerManager   = $partnerManager;
    }

    /**
     * {@inheritdoc}
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

                if ($client->isLender()) {
                    $data['ID_Preteur'] = $client->getIdClient();
                } elseif ($partner = $this->partnerManager->getPartner($client)) {
                    $data['ID_Partenaire'] = $partner->getId();
                    $data['ID_Client']     = $client->getIdClient();
                    $data['Organisation']  = $client->getCompany()->getName();
                    $data['Role']          = in_array(Clients::ROLE_PARTNER_ADMIN, $client->getRoles()) ? 'Administrateur' : 'Collaborateur';
                } else {
                    $data['ID_Emprunteur'] = $client->getIdClient();
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
