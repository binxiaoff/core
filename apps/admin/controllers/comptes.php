<?php

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Entity\Clients;
use Unilend\Entity\WalletType;
use Unilend\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager;

class comptesController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->hideDecoration();
        $this->users->checkAccess();
        $this->menu_admin = Zones::ZONE_LABEL_LENDERS;

        $zones = array_intersect([Zones::ZONE_LABEL_BORROWERS, Zones::ZONE_LABEL_LENDERS], $this->lZonesHeader);

        if (empty($zones)) {
            // In order to use classic redirection when user has no access to page
            $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        }

        /** @var BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if ($userManager->isUserGroupRisk($this->userEntity) || $userManager->isUserGroupSales($this->userEntity)) {
            $this->menu_admin = Zones::ZONE_LABEL_BORROWERS;
        }
    }

    public function _default()
    {
        header('Location: ' . $this->lurl . '/comptes/doublons');
        exit;
    }

    public function _doublons()
    {
        if ($this->request->isMethod(Request::METHOD_POST)) {
            $email = $this->request->request->filter('email', '', FILTER_SANITIZE_EMAIL);
            if (false === empty($email)) {
                header('Location: ' . $this->lurl . '/comptes/doublons/' . urlencode($email));
                exit;
            }

            header('Location: ' . $this->lurl . '/comptes/doublons');
            exit;
        }

        $template = [
            'email'    => '',
            'accounts' => []
        ];

        if (false === empty($this->params[0])) {
            $email             = filter_var(urldecode($this->params[0]), FILTER_SANITIZE_EMAIL);
            $template['email'] = $email;

            /** @var EntityManager $entityManager */
            $entityManager     = $this->get('doctrine.orm.entity_manager');
            $clientsRepository = $entityManager->getRepository(Clients::class);
            $accounts          = $clientsRepository->findDuplicatesByEmail($email);

            foreach ($accounts as $account) {
                if (
                    WalletType::BORROWER === $account['walletType']
                    || WalletType::LENDER === $account['walletType'] && in_array($account['clientType'], [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])
                ) {
                    $account['name'] = $account['companyName'];
                } else {
                    $account['name'] = $account['prenom'] . ' ' . $account['nom'];
                }

                $template['accounts'][] = $account;
            }
        }

        $this->render(null, $template);
    }
}
