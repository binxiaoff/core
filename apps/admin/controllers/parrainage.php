<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Service\OperationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\SponsorshipManager;

class parrainageController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_LENDERS);

        $this->catchAll   = true;
        $this->menu_admin = 'preteurs';
    }

    public function _default()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\SponsorshipManager $sponsorshipManager */
        $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\SponsorshipRepository $sponsorshipRepository */
        $sponsorshipRepository         = $entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship');
        $operationRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $sponsorshipCampaignRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipCampaign');

        $unilendPromotionWalletType = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendPromotionWallet     = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendPromotionWalletType]);

        $totalRewardsPaidSponsee = $operationRepository->sumDebitOperationsByTypeUntil($unilendPromotionWallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSEE]);
        $totalRewardsPaidSponsor = $operationRepository->sumDebitOperationsByTypeUntil($unilendPromotionWallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR]);

        $validCampaigns    = $this->getAdditionalCampaignData($sponsorshipCampaignRepository->findBy(['status' => SponsorshipCampaign::STATUS_VALID], ['start' => 'ASC']));
        $archivedCampaigns = $this->getAdditionalCampaignData($sponsorshipCampaignRepository->findBy(['status' => SponsorshipCampaign::STATUS_ARCHIVED], ['end' => 'DESC']));

        $this->render(null, [
            'unilendPromotionalBalance' => $unilendPromotionWallet->getAvailableBalance(),
            'totalSponsor'              => $sponsorshipRepository->getCountSponsorByCampaign(),
            'totalSponsee'              => $sponsorshipRepository->getCountSponseeByCampaign(),
            'totalRewardPaidOutSponsee' => $totalRewardsPaidSponsee,
            'totalRewardPaidOutSponsor' => $totalRewardsPaidSponsor,
            'validCampaigns'            => $validCampaigns,
            'archivedCampaigns'         => $archivedCampaigns,
            'blacklistedClients'        => $entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipBlacklist')->findAll(),
            'allSponsorshipRewards'     => $entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->getPaidOutSponsorshipDetails(),
            'currentCampaign'           => $sponsorshipManager->getCurrentSponsorshipCampaign(),
            'formErrors'                => $this->getErrorsFromSession(),
            'formSuccess'               => $this->getSuccessFromSession()
        ]);
    }

    private function getErrorsFromSession()
    {
        $newCampaignFormErrors = isset($_SESSION['create_sponsorship_campaign']['errors']) ? $_SESSION['create_sponsorship_campaign']['errors'] : [];
        unset($_SESSION['create_sponsorship_campaign']['errors']);

        $modifyCampaignErrors = isset($_SESSION['modify_sponsorship_campaign']['errors']) ? $_SESSION['modify_sponsorship_campaign']['errors'] : [];
        unset($_SESSION['modify_sponsorship_campaign']['errors']);

        $blacklistFormErrors = isset($_SESSION['sponsorship_blacklist']['errors']) ? $_SESSION['sponsorship_blacklist']['errors'] : [];
        unset($_SESSION['sponsorship_blacklist']['errors']);

        $payOutRewardErrors = isset($_SESSION['pay_out_sponsorship']['errors']) ? $_SESSION['pay_out_sponsorship']['errors'] : [];
        unset($_SESSION['pay_out_sponsorship']['errors']);

        $createSponsorshipErrors = isset($_SESSION['create_sponsorship']['errors']) ? $_SESSION['create_sponsorship']['errors'] : [];
        unset($_SESSION['create_sponsorship']['errors']);

        return [
            'modifyCampaign'    => $modifyCampaignErrors,
            'newCampaign'       => $newCampaignFormErrors,
            'blacklist'         => $blacklistFormErrors,
            'payOutReward'      => $payOutRewardErrors,
            'createSponsorship' => $createSponsorshipErrors
        ];
    }

    private function getSuccessFromSession()
    {
        $newCampaignFormSuccess = isset($_SESSION['create_sponsorship_campaign']['success']) ? $_SESSION['create_sponsorship_campaign']['success'] : [];
        unset($_SESSION['create_sponsorship_campaign']['success']);

        $modifyCampaignSuccess = isset($_SESSION['modify_sponsorship_campaign']['success']) ? $_SESSION['modify_sponsorship_campaign']['success'] : [];
        unset($_SESSION['modify_sponsorship_campaign']['success']);

        $blacklistFormSuccess = isset($_SESSION['sponsorship_blacklist']['success']) ? $_SESSION['sponsorship_blacklist']['success'] : [];
        unset($_SESSION['sponsorship_blacklist']['success']);

        $payOutRewardSuccess = isset($_SESSION['pay_out_sponsorship']['success']) ? $_SESSION['pay_out_sponsorship']['success'] : [];
        unset($_SESSION['pay_out_sponsorship']['success']);

        $createSponsorshipSuccess = isset($_SESSION['create_sponsorship']['success']) ? $_SESSION['create_sponsorship']['success'] : [];
        unset($_SESSION['create_sponsorship']['success']);

        return [
            'modifyCampaign'    => $modifyCampaignSuccess,
            'newCampaign'       => $newCampaignFormSuccess,
            'blacklist'         => $blacklistFormSuccess,
            'payOutReward'      => $payOutRewardSuccess,
            'createSponsorship' => $createSponsorshipSuccess
        ];
    }

    /**
     * @param array $campaigns
     *
     * @return array
     */
    private function getAdditionalCampaignData(array $campaigns)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\OperationRepository $operationRepository */
        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\SponsorshipRepository $sponsorshipRepository */
        $sponsorshipRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship');
        $campaignDetails = [];

        /** @var SponsorshipCampaign $campaign */
        foreach ($campaigns as $campaign) {
            $campaignDetails[$campaign->getId()]['campaign']             = $campaign;
            $campaignDetails[$campaign->getId()]['numberSponsee']        = $sponsorshipRepository->getCountSponseeByCampaign($campaign);
            $campaignDetails[$campaign->getId()]['numberSponsee']        = $sponsorshipRepository->getCountSponsorByCampaign($campaign);
            $campaignDetails[$campaign->getId()]['paidOutRewardSponsee'] = $operationRepository->getSumRewardAmountByCampaign(OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSEE, $campaign);
            $campaignDetails[$campaign->getId()]['paidOutRewardSponsor'] = $operationRepository->getSumRewardAmountByCampaign(OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR, $campaign);
        }

        return $campaignDetails;
    }

    public function _create_new_campaign()
    {
        if (null !== $this->request->request->get('create_new_campaign') || null !== $this->request->request->get('modify_campaign')) {
            $start = $this->request->request->get('start');
            if (false === empty($start) && preg_match("#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#", $start)) {
                $start = \DateTime::createFromFormat('d/m/Y', $start);
            } else {
                $_SESSION['create_sponsorship_campaign']['errors'][] = 'La date de début n\'est pas valide';
            }

            $end = $this->request->request->get('end');
            if (false === empty($end) && preg_match("#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#", $end)) {
                $end = \DateTime::createFromFormat('d/m/Y', $end);
            } else {
                $_SESSION['create_sponsorship_campaign']['errors'][] = 'La date de fin n\'est pas valide';
            }

            if ($end < $start) {
                $_SESSION['create_sponsorship_campaign']['errors'][] = 'La date de fin est antérieur à la date de début';
            }

            $amountSponsee = $this->request->request->getInt('amount_sponsee');
            if (empty($amountSponsee)) {
                $_SESSION['create_sponsorship_campaign']['errors'][] = 'Le montant pour le filleul n\'est pas valide';
            }

            $amountSponsor = $this->request->request->getInt('amount_sponsor');
            if (empty($amountSponsor)) {
                $_SESSION['create_sponsorship_campaign']['errors'][] = 'Le montant pour le parrain n\'est pas valide';
            }

            $maxNumberSponsee = $this->request->request->getInt('max_number_sponsee');
            if (empty($maxNumberSponsee)) {
                $_SESSION['create_sponsorship_campaign']['errors'][] = 'Le nombre maximal de filleuls n\'est pas valide';
            }

            $validityDays = $this->request->request->getInt('validity_days');
            if (empty($validityDays )) {
                $_SESSION['create_sponsorship_campaign']['errors'][] = 'Le nombre de jours de validité n\'est pas valide';
            }

            if (false == empty($_SESSION['create_sponsorship_campaign']['errors']) && null === $this->request->request->get('modify_campaign')) {
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            $idCampaign = $this->request->request->get('id_campaign', null);
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\SponsorshipManager $sponsorshipManager */
            $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');

            try {
                $newCampaignCreated = $sponsorshipManager->saveSponsorshipCampaign($start, $end, $amountSponsee, $amountSponsor, $maxNumberSponsee, $validityDays, $idCampaign);

                if ($newCampaignCreated && null !== $this->request->request->get('create_new_campaign')) {
                    $_SESSION['create_sponsorship_campaign']['success'] = 'La nouvelle campagne a été crée';
                } elseif ($newCampaignCreated && null !== $this->request->request->get('modify_campaign')) {
                    $_SESSION['modify_sponsorship_campaign']['success'] = 'La campagne a été modifié.';
                }
            } catch (\Exception $exception) {
                $_SESSION['create_sponsorship_campaign']['errors'][] = 'Une erreur est survenue lors de l\'enregistrement de la campagne.';

                if (SponsorshipManager::SPONSORSHIP_MANAGER_EXCEPTION_CODE == $exception->getCode()) {
                    $_SESSION['create_sponsorship_campaign']['errors']['technical'][] = $exception->getMessage();
                }
            }
        }

        if (null !== $this->request->request->get('modify_campaign') && false === empty($_SESSION['create_sponsorship_campaign']['errors'])) {
            $_SESSION['modify_sponsorship_campaign']['errors'] = $_SESSION['create_sponsorship_campaign']['errors'];
            unset($_SESSION['create_sponsorship_campaign']['errors']);
        }

        header('Location: ' . $this->lurl . '/parrainage');
        die;
    }

    public function _search_client()
    {
        $this->autoFireView = false;

        if (null !== $this->request->request->get('search_client') && $this->request->isXmlHttpRequest()) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $idClient = $this->request->request->getInt('id_client');
            if (empty($idClient)) {
                $result = ['success' => false, 'error' => ['Veuillez saisir un id Client']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClient);
            if (null === $client) {
                $result = ['success' => false, 'error' => ['Ce client n\'existe pas']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            if (false === $client->isLender()) {
                $result = ['success' => false, 'error' => ['Ce client n\'est pas un prêteur']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            $result = ['success' => true, 'client' => ['idClient' => $client->getIdClient(), 'lastName' => $client->getNom(), 'firstName' => $client->getPrenom()]];
            header('Content-Type: application/json');
            echo json_encode($result);
            die;
        }
    }

    public function _blacklist()
    {
        if (null !== $this->request->request->get('blacklist_client')) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $idClient = $this->request->request->getInt('id_client');
            if (empty($idClient)) {
                $_SESSION['sponsorship_blacklist']['errors'][] = 'Veuillez saisir un id Client';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClient);
            if (null === $client) {
                $_SESSION['sponsorship_blacklist']['errors'][] = 'Ce client n\'existe pas';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            if (false === $client->isLender()) {
                $_SESSION['sponsorship_blacklist']['errors'][] = 'Ce client n\'est pas un prêteur';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            $idCampaign = $this->request->request->getInt('campaign');
            $campaign   = null;
            if (0 < $idCampaign) {
                $campaign = $entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipCampaign')->find($idCampaign);
                if (null === $campaign) {
                    $_SESSION['sponsorship_blacklist']['errors'][] = 'La campagne choisi n\'existe pas';
                    header('Location: ' . $this->lurl . '/parrainage');
                    die;
                }
            }

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\SponsorshipManager $sponsorshipManager */
            $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');
            $user               = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($_SESSION['user']['id_user']);
            $sponsorshipManager->blacklistClientAsSponsor($client, $user, $campaign);

            $_SESSION['sponsorship_blacklist']['success'] = 'Le client a été blacklisté';
        }

        header('Location: ' . $this->lurl . '/parrainage');
        die;
    }

    public function _pay_out_reward()
    {
        if (null !== $this->request->request->get('pay_out_reward')) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\SponsorshipManager $sponsorshipManager */
            $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');
            $idSponsorship      = $this->request->request->getInt('id_sponsorship');
            $sponsorship        = $entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->find($idSponsorship);

            if (null === $sponsorship) {
                $_SESSION['pay_out_sponsorship']['errors'][] = 'Le parrainage choisi n\'existe pas';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            $typeReward = $this->request->request->get('type_reward');
            if (empty($typeReward)) {
                $_SESSION['pay_out_sponsorship']['errors'][] = 'Une erreur s\'est produite';
                $_SESSION['pay_out_sponsorship']['errors']['technical'][] = 'reward type ' . $this->request->request->get('type_reward') . ' does not exist';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            try {
                if (OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSEE == $typeReward) {
                    $sponsorshipManager->attributeSponseeReward($sponsorship->getIdClientSponsee());
                    $_SESSION['pay_out_sponsorship']['success'] = 'La prime du filleul a été versée';
                }
                if (OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR == $typeReward) {
                    if ($sponsorshipManager->attributeSponsorReward($sponsorship->getIdClientSponsee())) {
                        $_SESSION['pay_out_sponsorship']['success'] = 'La prime du parrain a été versée';
                    }
                }
            } catch (\Exception $exception) {
                $_SESSION['pay_out_sponsorship']['errors'][] = 'Une erreur s\'est produite';
                if (in_array($exception->getCode(), [OperationManager::OPERATION_MANAGER_EXCEPTION_CODE, SponsorshipManager::SPONSORSHIP_MANAGER_EXCEPTION_CODE])) {
                    $_SESSION['pay_out_sponsorship']['errors']['technical'][] = $exception->getMessage();
                } else {
                    $this->get('logger')->warning('Sponsorship reward could not be paid for sponsorship. Reason: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_sponsorship' => $sponsorship->getId()]);
                }
            }
        }

        header('Location: ' . $this->lurl . '/parrainage');
        die;
    }

    public function _search_sponsorship()
    {
        $this->autoFireView = false;

        if (null !== $this->request->request->get('search_sponsorship') && $this->request->isXmlHttpRequest()){
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $idClient = $this->request->request->getInt('id_client');
            $client   = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClient);
            if (null === $client) {
                $result = ['success' => false, 'error' => ['Le client n\'existe pas']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            if (false === $client->isLender()) {
                $result = ['success' => false, 'error' => ['Ce client n\'est pas un prêteur']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            $type = $this->request->request->get('type');
            if (empty($type)) {
                $result = ['success' => false, 'error' => ['Merci de spécifier s\'il s\'agit d\'un parrain ou d\'un filleul']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            $sponsorshipRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship');

            if ('sponsor' == $type) {
                $sponsorship = $sponsorshipRepository->getSponsorshipDetailBySponsor($client);
            } elseif ('sponsee' == $type) {
                $sponsorship = $sponsorshipRepository->getSponsorshipDetailBySponsee($client);
            }

            if (empty($sponsorship)) {
                $result = ['success' => false, 'error' => ['Il n\'y a pas de lien parrain-filleul pour ce client.']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            $result = ['success' => true, 'sponsorships' => $sponsorship];
            header('Content-Type: application/json');
            echo json_encode($result);
            die;
        }
    }

    public function _create_sponsorship()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager   = $this->get('doctrine.orm.entity_manager');

        if (null !== $this->request->request->get('create_sponsorship') && $this->request->isXmlHttpRequest()) {
            $idClientSponsor = $this->request->request->getInt('id_client_sponsor');
            if (null === $idClientSponsor) {
                $result = ['success' => false, 'error' => ['L\'id client du parrain n\'est pas valide']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            $sponsor   = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClientSponsor);
            if (null === $sponsor) {
                $result = ['success' => false, 'error' => ['Le client parrain n\'existe pas']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            if (false === $sponsor->isLender()) {
                $result = ['success' => false, 'error' => ['Le client parrain n\'est pas un prêteur']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            $idClientSponsee = $this->request->request->getInt('id_client_sponsee');
            if (null === $idClientSponsee) {
                $result = ['success' => false, 'error' => ['L\'id client du filleul n\'est pas valide']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            $sponsee  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClientSponsee);
            if (null === $sponsee) {
                $result = ['success' => false, 'error' => ['Le client filleul n\'existe pas']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            if (false === $sponsee->isLender()) {
                $result = ['success' => false, 'error' => ['Le client filleul n\'est pas un prêteur']];
                header('Content-Type: application/json');
                echo json_encode($result);
                die;
            }

            $sponseeValidationDate = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')->getFirstClientValidation($sponsee->getIdClient());
            $sponsorshipData       = [
                'idClientSponsor'                => $sponsor->getIdClient(),
                'lastNameSponsor'                => $sponsor->getNom(),
                'firstNameSponsor'               => $sponsor->getPrenom(),
                'idClientSponsee'                => $sponsee->getIdClient(),
                'lastNameSponsee'                => $sponsee->getNom(),
                'firstNameSponsee'               => $sponsee->getPrenom(),
                'subscriptionSponsee'            => $sponsee->getAdded()->format('d/m/Y'),
                'sponseeValidationDate'          => null !== $sponseeValidationDate ? $sponseeValidationDate->getAdded()->format('d/m/Y') : 'pas encore validé',
                'sponseeHasReceivedWelcomeOffer' => $this->get('unilend.service.welcome_offer_manager')->clientHasReceivedWelcomeOffer($sponsee)
            ];

            $result = ['success' => true, 'sponsorshipData' => $sponsorshipData];
            header('Content-Type: application/json');
            echo json_encode($result);
            die;
        }

        header('Location: ' . $this->lurl . '/parrainage');
        die;
    }


    public function _create_sponsorship_confirm()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager   = $this->get('doctrine.orm.entity_manager');

        if (null !== $this->request->request->get('create_sponsorship_confirm')) {
            $idClientSponsor = $this->request->request->getInt('id_client_sponsor');
            if (null === $idClientSponsor) {
                $_SESSION['create_sponsorship']['errors'][] = 'L\'id client du parrain n\'est pas valide';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            $sponsor   = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClientSponsor);
            if (null === $sponsor) {
                $_SESSION['create_sponsorship']['errors'][] = 'Le client parrain n\'existe pas';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            if (false === $sponsor->isLender()) {
                $_SESSION['create_sponsorship']['errors'][] = 'Le client parrain n\'est pas un prêteur';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            $idClientSponsee = $this->request->request->getInt('id_client_sponsee');
            if (null === $idClientSponsee) {
                $_SESSION['create_sponsorship']['errors'][] = 'L\'id client du filleul n\'est pas valide';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            $sponsee  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClientSponsee);
            if (null === $sponsee) {
                $_SESSION['create_sponsorship']['errors'][] = 'Le client filleul n\'existe pas';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            if (false === $sponsee->isLender()) {
                $_SESSION['create_sponsorship']['errors'][] = 'Le client filleul n\'est pas un prêteur';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }


            $campaign = $entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipCampaign')->findCampaignValidAtDate($sponsee->getAdded());
            if (null === $campaign) {
                $_SESSION['create_sponsorship']['errors'][] = 'Il n\'y a pas de campagne active au moment de l\'inscription du filleul';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            if ($sponsor->getIdClient() === $sponsee->getIdClient()) {
                $_SESSION['create_sponsorship']['errors'][] = 'Parrain et filleul ne peuvent pas être le même client';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            $sponsorship = $entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsor' => $sponsor, 'idClientSponsee' => $sponsee]);
            if (null !== $sponsorship) {
                $_SESSION['create_sponsorship']['errors'][] = 'L\'assosciation parrain filleul pour ces clients existe déjà';
                header('Location: ' . $this->lurl . '/parrainage');
                die;
            }

            $status   = $this->get('unilend.service.welcome_offer_manager')->clientHasReceivedWelcomeOffer($sponsee) ? Sponsorship::STATUS_SPONSEE_PAID : Sponsorship::STATUS_ONGOING;
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\SponsorshipManager $sponsorshipManager */
            $sponsorshipManager = $this->get('unilend.service.sponsorship_manager');
            $sponsorshipManager->createSponsorship($sponsee, $sponsor->getSponsorCode(), $campaign);

            $sponsorship = $entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsee' => $sponsee]);
            $sponsorship->setIdSponsorshipCampaign($campaign)
                ->setStatus($status);
            $entityManager->flush($sponsorship);
            $_SESSION['create_sponsorship']['success'] = 'Le lien entre parrain (' . $sponsor->getIdClient() . ') et filleul (' . $sponsee->getIdClient() . ') a été crée';
        }

        header('Location: ' . $this->lurl . '/parrainage');
        die;

    }

    public function _export()
    {
        $headers = [
            'ID client du filleul',
            'Nom Prenom du filleul',
            'Email du filleul',
            'Montant de la prime du filleul',
            'Date de versement de la prime filleul',
            'Code parrain utilisé',
            'ID client du parrain',
            'Nom Prenom du parrain',
            'Email du parrain',
            'Montant de la prime du parrain',
            'Date de versement de la prime parrain'
        ];

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $details       = $entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->getPaidOutSponsorshipDetails();

        $document = new \PHPExcel();
        $document->getDefaultStyle()->getFont()->setName('Arial');
        $document->getDefaultStyle()->getFont()->setSize(11);
        $activeSheet = $document->setActiveSheetIndex(0);

        foreach ($headers as $column => $title) {
            $activeSheet->setCellValueByColumnAndRow($column, 1, $title);
        }

        $row = 2;
        foreach ($details as $detail) {
            $activeSheet->setCellValue('A' . $row, $detail['id_client_sponsee']);
            $activeSheet->setCellValue('B' . $row, $detail['sponsee_last_name'] . ' ' . $detail['sponsee_first_name']);
            $activeSheet->setCellValue('C' . $row, $detail['sponsee_email']);
            if (false === empty($detail['sponsee_amount'])) {
                $activeSheet->setCellValueExplicit('D' . $row, $detail['sponsee_amount'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicit('E' . $row, \PHPExcel_Shared_Date::PHPToExcel(\DateTime::createFromFormat('Y-m-d H:i:s', $detail['sponsee_added'])), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
            }
            $activeSheet->setCellValue('F' . $row, $detail['sponsor_code']);
            $activeSheet->setCellValue('G' . $row, $detail['id_client_sponsor']);
            $activeSheet->setCellValue('H' . $row, $detail['sponsor_last_name'] . ' ' . $detail['sponsor_first_name']);
            $activeSheet->setCellValue('I' . $row, $detail['sponsor_email']);
            if (false === empty($detail['sponsor_amount'])) {
                $activeSheet->setCellValueExplicit('J' . $row, $detail['sponsor_amount'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->setCellValueExplicit('K' . $row, \PHPExcel_Shared_Date::PHPToExcel(\DateTime::createFromFormat('Y-m-d H:i:s', $detail['sponsor_added'])), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
            }
            $row +=1;
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=parrainage.xlsx');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        $writer = PHPExcel_IOFactory::createWriter($document, 'Excel2007');
        $writer->save('php://output');

        die;
    }
}
