<?php

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyClient;
use Unilend\Bundle\CoreBusinessBundle\Entity\PartnerProduct;
use Unilend\Bundle\CoreBusinessBundle\Entity\PartnerProjectAttachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\PartnerThirdParty;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class partenairesController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->menu_admin = Zones::ZONE_LABEL_BORROWERS;
    }

    public function _default()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $partners      = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->getPartnersSortedByName();

        $this->render(null, ['partners' => $partners]);
    }

    public function _edit()
    {
        /** @var Doctrine\ORM\EntityManager $entityManager = */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');

        if (
            empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || null === ($partner = $partnerRepository->find($this->params[0]))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $success = isset($_SESSION['forms']['partner']['success']) ? $_SESSION['forms']['partner']['success'] : [];
        $errors  = isset($_SESSION['forms']['partner']['errors']) ? $_SESSION['forms']['partner']['errors'] : [];

        unset($_SESSION['forms']['partner']['success']);
        unset($_SESSION['forms']['partner']['errors']);

        $agencies   = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['idParentCompany' => $partner->getIdCompany()->getIdCompany()]);
        $agencies[] = $partner->getIdCompany();
        usort($agencies, function($first, $second) {
            return strcmp($first->getName(), $second->getName());
        });

        $users = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient')->findBy(['idCompany' => $agencies]);

        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator        = $this->get('translator');
        $productRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Product');
        $productTypes      = $productRepository->findBy(['status' => [Product::STATUS_OFFLINE, Product::STATUS_ONLINE]]);
        foreach ($productTypes as $productType) {
            $productType->setLabel($translator->trans('product_label_' . $productType->getLabel()));
        }
        usort($productTypes, function($first, $second) {
            return strcmp($first->getLabel(), $second->getLabel());
        });

        $products = $partner->getProductAssociations([Product::STATUS_OFFLINE, Product::STATUS_ONLINE]);
        usort($products, function ($first, $second) {
            return strcmp($first->getIdProduct()->getLabel(), $second->getIdProduct()->getLabel());
        });

        $descriptionTranslationLabel = 'partner-project-details_description-instructions-' . $partner->getLabel();
        $documentsTranslationLabel   = 'partner-project-details_documents-instructions-' . $partner->getLabel();
        $descriptionTranslation      = $descriptionTranslationLabel === $translator->trans($descriptionTranslationLabel) ? '' : $translator->trans($descriptionTranslationLabel);
        $documentsTranslation        = $documentsTranslationLabel === $translator->trans($documentsTranslationLabel) ? '' : $translator->trans($documentsTranslationLabel);

        $this->render(null, [
            'formSuccess'   => $success,
            'formErrors'    => $errors,
            'partner'       => $partner,
            'agencies'      => $agencies,
            'users'         => $users,
            'documentTypes' => $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->findBy([], ['label' => 'ASC']),
            'documents'     => $partner->getAttachmentTypes(),
            'productTypes'  => $productTypes,
            'products'      => $products,
            'instructions'  => [
                'description' => $descriptionTranslation,
                'documents'   => $documentsTranslation
            ]
        ]);
    }

    public function _agence()
    {
        if (
            false === $this->request->isXmlHttpRequest()
            || empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $partner       = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);

        if (null === $partner) {
            $this->sendAjaxResponse(false, null, ['Partenaire inconnu']);
        }

        /** @var Companies $agency */
        $agency = null;
        $errors = [];

        switch ($this->request->request->get('action')) {
            case 'create':
                $agency       = new Companies();
                $agencyErrors = $this->setAgencyData($this->request, $agency);
                $errors       = array_merge($errors, $agencyErrors);

                if (empty($agencyErrors)) {
                    $agency->setIdParentCompany($partner->getIdCompany());
                    $agency->setIdClientOwner(0); // @todo remove once id_client_owner is nullable

                    $entityManager->persist($agency);
                    $entityManager->flush($agency);
                }
                break;
            case 'modify':
                $id = $this->request->request->getInt('id');

                if (empty($id)) {
                    $errors[] = 'Agence inconnue';
                    break;
                }

                $companiesRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
                $agency              = $companiesRepository->find($id);

                if (null === $agency) {
                    $errors[] = 'Agence inconnue';
                    break;
                }

                $agencyErrors = $this->setAgencyData($this->request, $agency);
                $errors       = array_merge($errors, $agencyErrors);

                if (empty($agencyErrors)) {
                    $entityManager->flush($agency);
                }
                break;
            default:
                $errors[] = 'Action inconnue';
                break;
        }

        $this->sendAjaxResponse(
            empty($errors),
            $agency instanceof Companies ? [
                $agency->getName(),
                $agency->getSiren(),
                $agency->getPhone(),
                $agency->getAdresse1(),
                $agency->getZip(),
                $agency->getCity(),
            ] : [],
            $errors,
            $agency instanceof Companies ? $agency->getIdCompany() : null
        );
    }

    /**
     * @param Request   $request
     * @param Companies $agency
     *
     * @return array
     */
    private function setAgencyData(Request $request, Companies $agency)
    {
        $errors   = [];
        $name     = $request->request->filter('name', FILTER_SANITIZE_STRING);
        $siren    = $request->request->filter('siren', FILTER_SANITIZE_STRING);
        $phone    = $request->request->filter('phone', FILTER_SANITIZE_STRING);
        $address  = $request->request->filter('address', FILTER_SANITIZE_STRING);
        $postcode = $request->request->filter('postcode', FILTER_SANITIZE_STRING);
        $city     = $request->request->filter('city', FILTER_SANITIZE_STRING);

        if (empty($name)) {
            $errors[] = 'Vous devez renseigner le nom de l\'agence';
        }
        if (false === empty($siren) && 1 !== preg_match('/^[0-9]{9}$/', $siren)) {
            $errors[] = 'Numéro de SIREN invalide';
        }

        if (empty($errors)) {
            $agency->setName($name);
            $agency->setSiren($siren);
            $agency->setPhone($phone);
            $agency->setAdresse1($address);
            $agency->setZip($postcode);
            $agency->setCity($city);
        }

        return $errors;
    }

    public function _utilisateur()
    {
        if (
            false === $this->request->isXmlHttpRequest()
            || empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $partner       = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);

        if (null === $partner) {
            $this->sendAjaxResponse(false, null, ['Partenaire inconnu']);
        }

        /** @var CompanyClient $companyClient */
        $companyClient = null;
        $errors        = [];
        $action        = $this->request->request->get('action');

        switch ($action) {
            case 'create':
                $errors = $this->createUser($this->request, $companyClient, $errors);
                break;
            case 'modify':
                $errors = $this->updateUser($this->request, $companyClient, $errors);
                break;
            case 'activate':
            case 'deactivate':
                $errors = $this->toggleUserStatus($this->request, $companyClient, $errors);
                break;
            case 'password':
                $errors = $this->sendUserPassword($this->request, $companyClient, $errors);
                break;
            default:
                $errors[] = 'Action inconnue';
                break;
        }

        $data = [];
        if ($companyClient instanceof CompanyClient && $companyClient->getId()) {
            if (in_array($action, ['activate', 'deactivate'])) {
                $data = $companyClient->getIdClient()->getStatus() ? 'active' : 'inactive';
            } else {
                $data = [
                    $companyClient->getIdClient()->getNom(),
                    $companyClient->getIdClient()->getPrenom(),
                    $companyClient->getIdClient()->getEmail(),
                    $companyClient->getIdCompany()->getIdCompany(),
                    $companyClient->getIdClient()->getTelephone(),
                    $companyClient->getRole() === 'ROLE_PARTNER_ADMIN' ? 'admin' : 'agent',
                    $companyClient->getIdClient()->getLastlogin() ? $companyClient->getIdClient()->getLastlogin()->format('d/m/Y') : ''
                ];
            }
        }

        $this->sendAjaxResponse(
            empty($errors),
            $data,
            $errors,
            $companyClient instanceof CompanyClient && $companyClient->getId() ? $companyClient->getId() : null
        );
    }

    /**
     * @param Request            $request
     * @param CompanyClient|null $companyClient
     * @param array              $errors
     *
     * @return array
     */
    private function createUser(Request $request, &$companyClient, array $errors)
    {
        $companyClient       = new CompanyClient();
        $companyClientErrors = $this->setCompanyClientData($request, $companyClient);
        $errors              = array_merge($errors, $companyClientErrors);

        if (empty($companyClientErrors)) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            try {
                $entityManager->persist($companyClient->getIdClient());
                $entityManager->persist($companyClient);
                $entityManager->flush([$companyClient->getIdClient(), $companyClient]);

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\WalletCreationManager $walletCreationManager */
                $walletCreationManager = $this->get('unilend.service.wallet_creation_manager');
                $walletCreationManager->createWallet($companyClient->getIdClient(), WalletType::PARTNER);

                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
                $mailerManager = $this->get('unilend.service.email_manager');
                $mailerManager->sendPartnerAccountActivation($companyClient->getIdClient());
            } catch (\Exception $exception) {
                $this->get('logger')->error('An error occurred while creating client: ' . $exception->getMessage(), [['class' => __CLASS__, 'function' => __FUNCTION__]]);
            }
        }

        return $errors;
    }

    /**
     * @param Request            $request
     * @param CompanyClient|null $companyClient
     * @param array              $errors
     *
     * @return array
     */
    private function updateUser(Request $request, &$companyClient, array $errors)
    {
        $id = $request->request->getInt('id');

        if (empty($id)) {
            $errors[] = 'Utilisateur inconnu';
            return $errors;
        }

        /** @var EntityManager $entityManager */
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $companyClientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient');
        $companyClient           = $companyClientRepository->find($id);

        if (null === $companyClient) {
            $errors[] = 'Utilisateur inconnu';
            return $errors;
        }

        $agencyErrors = $this->setCompanyClientData($request, $companyClient);
        $errors       = array_merge($errors, $agencyErrors);

        if (empty($agencyErrors)) {
            $entityManager->flush([$companyClient->getIdClient(), $companyClient]);
        }

        return $errors;
    }

    /**
     * @param Request            $request
     * @param CompanyClient|null $companyClient
     * @param array              $errors
     *
     * @return array
     */
    private function toggleUserStatus(Request $request, &$companyClient, array $errors)
    {
        $id = $request->request->getInt('id');

        if (empty($id)) {
            $errors[] = 'Utilisateur inconnu';
            return $errors;
        }

        /** @var EntityManager $entityManager */
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $companyClientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient');
        $companyClient           = $companyClientRepository->find($id);

        if (null === $companyClient) {
            $errors[] = 'Utilisateur inconnu';
            return $errors;
        }

        $status = 'activate' === $request->request->get('action') ? Clients::STATUS_ONLINE : Clients::STATUS_OFFLINE;
        $companyClient->getIdClient()->setStatus($status);
        $entityManager->flush($companyClient->getIdClient());

        return $errors;
    }

    /**
     * @param Request            $request
     * @param CompanyClient|null $companyClient
     * @param array              $errors
     *
     * @return array
     */
    private function sendUserPassword(Request $request, &$companyClient, array $errors)
    {
        $id = $request->request->getInt('id');

        if (empty($id)) {
            $errors[] = 'Utilisateur inconnu';
            return $errors;
        }

        /** @var EntityManager $entityManager */
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $companyClientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient');
        $companyClient           = $companyClientRepository->find($id);

        if (null === $companyClient) {
            $errors[] = 'Utilisateur inconnu';
            return $errors;
        }

        /** @var \temporary_links_login $temporaryLink */
        $temporaryLink      = $this->get('unilend.service.entity_manager')->getRepository('temporary_links_login');
        $settingsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings');
        $token              = $temporaryLink->generateTemporaryLink($companyClient->getIdClient()->getIdClient(), \temporary_links_login::PASSWORD_TOKEN_LIFETIME_MEDIUM);
        $keywords           = [
            'surl'          => $this->surl,
            'url'           => $this->furl,
            'prenom'        => $companyClient->getIdClient()->getPrenom(),
            'login'         => $companyClient->getIdClient()->getEmail(),
            'link_password' => $this->furl . '/partenaire/securite/' . $token,
            'lien_fb'       => $settingsRepository->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw'       => $settingsRepository->findOneBy(['type' => 'Twitter'])->getValue()
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('mot-de-passe-oublie', $keywords);

        try {
            $message->setTo($companyClient->getIdClient()->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email "mot-de-passe-oublie" - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $companyClient->getIdClient()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }

        return $errors;
    }

    /**
     * @param Request       $request
     * @param CompanyClient $companyClient
     *
     * @return array
     */
    private function setCompanyClientData(Request $request, CompanyClient $companyClient)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $errors        = [];
        $lastName      = $request->request->filter('lastname', FILTER_SANITIZE_STRING);
        $firstName     = $request->request->filter('firstname', FILTER_SANITIZE_STRING);
        $email         = $request->request->filter('email', FILTER_VALIDATE_EMAIL);
        $agency        = $request->request->getInt('agency');
        $phone         = $request->request->filter('phone', FILTER_SANITIZE_STRING);
        $role          = $request->request->filter('role', FILTER_SANITIZE_STRING);

        if (empty($lastName)) {
            $errors[] = 'Vous devez renseigner un nom';
        }
        if (empty($firstName)) {
            $errors[] = 'Vous devez renseigner un prénom';
        }
        if (empty($email)) {
            $errors[] = 'Vous devez renseigner une adresse email';
        }
        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Vous devez renseigner une adresse email valide';
        }
        $clientsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        if (
            (null === $companyClient->getIdClient() || $companyClient->getIdClient()->getEmail() !== $email)
            && $clientsRepository->existEmail($email, Clients::STATUS_ONLINE)
        ) {
            $errors[] = 'Il existe déjà un compte avec cette adresse email';
        }
        if (empty($agency)) {
            $errors[] = 'Vous devez renseigner une agence de rattachement';
        }
        $companiesRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        $agency              = $companiesRepository->find($agency);
        if (null === $agency) {
            $errors[] = 'Agence de rattachement inconnue';
        }
        if (empty($role)) {
            $errors[] = 'Vous devez renseigner un rôle';
        }
        if (false === in_array($role, ['admin', 'agent'])) {
            $errors[] = 'Rôle inconnu';
        }

        if (empty($errors)) {
            if (null === $companyClient->getIdClient()) {
                $client = new Clients();
                $client
                    ->setIdLangue('fr')
                    ->setSlug($this->ficelle->generateSlug($firstName . '-' . $lastName))
                    ->setStatus(Clients::STATUS_ONLINE);

                $companyClient->setIdClient($client);
            }
            $companyClient->getIdClient()->setNom($lastName);
            $companyClient->getIdClient()->setPrenom($firstName);
            $companyClient->getIdClient()->setEmail($email);
            $companyClient->getIdClient()->setTelephone($phone);
            $companyClient->setIdCompany($agency);
            $companyClient->setRole('admin' === $role ? 'ROLE_PARTNER_ADMIN' : 'ROLE_PARTNER_USER');
        }

        return $errors;
    }

    public function _instructions()
    {
        /** @var Doctrine\ORM\EntityManager $entityManager = */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');

        if (
            false === $this->request->isMethod(Request::METHOD_POST)
            || empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || null === ($partner = $partnerRepository->find($this->params[0]))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $instructions = $this->request->request->get('instructions');

        if (false === is_array($instructions)) {
            $_SESSION['forms']['partner']['errors']['instructions'] = ['Veuillez saisir les instructions de dépôt de dossier.'];

            header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#instructions');
            return;
        }

        /** @var \Unilend\Bundle\TranslationBundle\Service\TranslationManager $translationManager */
        $translationManager          = $this->get('unilend.service.translation_manager');
        $descriptionTranslationLabel = 'description-instructions-' . $partner->getLabel();
        $documentsTranslationLabel   = 'documents-instructions-' . $partner->getLabel();
        $description                 = isset($instructions['description']) ? filter_var($instructions['description'], FILTER_SANITIZE_STRING) : '';
        $documents                   = isset($instructions['documents']) ? filter_var($instructions['documents'], FILTER_SANITIZE_STRING) : '';

        $translationManager->deleteTranslation('partner-project-details', $descriptionTranslationLabel);
        $translationManager->deleteTranslation('partner-project-details', $documentsTranslationLabel);

        $translationManager->addTranslation('partner-project-details', $descriptionTranslationLabel, $description);
        $translationManager->addTranslation('partner-project-details', $documentsTranslationLabel, $documents);

        $translationManager->flush();

        $_SESSION['forms']['partner']['success']['instructions'] = ['Instructions modifiées avec succès.'];

        header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#instructions');
        return;
    }

    public function _documents()
    {
        /** @var Doctrine\ORM\EntityManager $entityManager = */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');

        if (
            false === $this->request->isMethod(Request::METHOD_POST)
            || empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || null === ($partner = $partnerRepository->find($this->params[0]))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $newDocuments = json_decode($this->request->request->get('new_order'), true);

        if (null === $newDocuments) {
            $_SESSION['forms']['partner']['errors']['documents'] = ['Impossible de récupérer la liste des pièces à fournir.'];

            header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#documents');
            return;
        }

        $newOrder                           = array_column($newDocuments, 'id');
        $attachmentTypeRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType');
        $partnerProjectAttachmentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProjectAttachment');
        $currentDocuments                   = $partnerProjectAttachmentRepository->findBy(['idPartner' => $partner], ['rank' => 'ASC']);

        foreach ($currentDocuments as $currentDocument) {
            $index = array_search($currentDocument->getAttachmentType()->getId(), $newOrder);

            if (false === $index) {
                $entityManager->remove($currentDocument);
                continue;
            }

            $newRank   = $index + 1;
            $mandatory = (bool) $newDocuments[$index]['mandatory'];

            if ($newRank !== $currentDocument->getRank()) {
                $currentDocument->setRank($newRank);
            }

            if ($mandatory !== $currentDocument->getMandatory()) {
                $currentDocument->setMandatory($mandatory);
            }

            unset($newDocuments[$index]);
        }

        // Documents left in $newDocuments correspond to documents not yet existing in DB
        foreach ($newDocuments as $index => $newDocument) {
            $document = new PartnerProjectAttachment();
            $document->setPartner($partner);
            $document->setAttachmentType($attachmentTypeRepository->find($newDocument['id']));
            $document->setMandatory((bool) $newDocument['mandatory']);
            $document->setRank($index + 1);

            $entityManager->persist($document);
        }

        $entityManager->flush();

        $_SESSION['forms']['partner']['success']['documents'] = ['Liste des pièces à fournir modifiée avec succès.'];

        header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#documents');
        return;
    }

    public function _produits()
    {
        /** @var Doctrine\ORM\EntityManager $entityManager = */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');

        if (
            false === $this->request->isMethod(Request::METHOD_POST)
            || empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || null === ($partner = $partnerRepository->find($this->params[0]))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $productId         = $this->request->request->getInt('product');
        $productRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Product');

        if (empty($productId) || null === ($product = $productRepository->find($productId))) {
            $_SESSION['forms']['partner']['errors']['products'] = ['Produit inconnu.'];

            header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#products');
            return;
        }

        $partnerProductRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProduct');
        $partnerProducts          = $partnerProductRepository->findBy(['idPartner' => $partner]);

        if (($action = $this->request->request->get('action')) && 'delete' === $action) {
            foreach ($partnerProducts as $partnerProduct) {
                if ($product === $partnerProduct->getIdProduct()) {
                    $entityManager->remove($partnerProduct);
                    $entityManager->flush($partnerProduct);

                    $_SESSION['forms']['partner']['success']['products'] = ['Le produit a été supprimé avec succès.'];

                    header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#products');
                    return;
                }
            }

            $_SESSION['forms']['partner']['errors']['products'] = ['Impossible de retirer le produit, il ne fait pas partie des produits associés au partenaire.'];

            header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#products');
            return;
        } else {
            foreach ($partnerProducts as $partnerProduct) {
                if ($product === $partnerProduct->getIdProduct()) {
                    $_SESSION['forms']['partner']['errors']['products'] = ['Le produit est déjà associé au partenaire. Vous devez d\'abord le supprimer avant de le rajouter pour modifier les taux de commmission.'];

                    header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#products');
                    return;
                }
            }

            $fundsCommissionRate     = (float) str_replace(',', '.', $this->request->request->get('funds_commission_rate', -1));
            $repaymentCommissionRate = (float) str_replace(',', '.', $this->request->request->get('repayment_commission_rate', -1));

            if (
                $fundsCommissionRate < 0
                || $fundsCommissionRate > 100
                || $repaymentCommissionRate < 0
                || $repaymentCommissionRate > 100
            ) {
                $_SESSION['forms']['partner']['errors']['products'] = ['Les taux de commission doivent se situer entre 0 et 100&nbsp;%.'];

                header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#products');
                return;
            }

            $partnerProduct = new PartnerProduct();
            $partnerProduct->setIdPartner($partner);
            $partnerProduct->setIdProduct($product);
            $partnerProduct->setCommissionRateFunds($fundsCommissionRate);
            $partnerProduct->setCommissionRateRepayment($repaymentCommissionRate);

            $entityManager->persist($partnerProduct);
            $entityManager->flush($partnerProduct);

            $_SESSION['forms']['partner']['success']['products'] = ['Le produit a été associé au partenaire avec succès.'];
        }

        header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#products');
        return;
    }

    public function _parametres()
    {
        /** @var Doctrine\ORM\EntityManager $entityManager = */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');

        if (
            false === $this->request->isMethod(Request::METHOD_POST)
            || empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || null === ($partner = $partnerRepository->find($this->params[0]))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $partner->setProspect('on' === $this->request->request->get('prospect'));
        $entityManager->flush($partner);

        $_SESSION['forms']['partner']['success']['settings'] = ['Paramètres modifiés avec succès.'];

        header('Location: ' . $this->lurl . '/partenaires/edit/' . $partner->getId() . '#settings');
        return;
    }

    public function _tiers()
    {
        /** @var Doctrine\ORM\EntityManager $entityManager = */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');

        if (
            empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || null === ($this->partner = $partnerRepository->find($this->params[0]))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $this->translator = $this->get('translator');
        $this->partner    = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);
    }

    public function _ajout_tiers()
    {
        if (
            $this->request->isMethod(Request::METHOD_POST)
            && false === empty($this->params[0])
            && $this->request->request->get('id_company')
            && $this->request->request->get('third_party_type')
        ) {
            /** @var Doctrine\ORM\EntityManager $entityManager = */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->request->request->get('id_company'));
            $partner       = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);
            $type          = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerThirdPartyType')->find($this->request->request->get('third_party_type'));

            if ($company && $partner && $type) {
                try {
                    $thirdParty = new PartnerThirdParty();
                    $thirdParty->setIdCompany($company);
                    $thirdParty->setIdPartner($partner);
                    $thirdParty->setIdType($type);

                    $entityManager->persist($thirdParty);
                    $entityManager->flush($thirdParty);

                    $_SESSION['freeow']['title']   = 'Tiers ajouté';
                    $_SESSION['freeow']['message'] = 'le tiers est ajouté avec succès';
                } catch (Exception $exception) {
                    $_SESSION['freeow']['title']   = 'Une erreur survenu';
                    $_SESSION['freeow']['message'] = 'le tiers n\'est pas ajouté';
                }

                header('Location: ' . $this->lurl . '/partenaires/tiers/' . $this->params[0]);
                return;
            }
        }

        $this->hideDecoration();

        if (
            isset($this->params[0], $this->params[1])
            && false !== filter_var($this->params[0], FILTER_VALIDATE_INT)
            && 1 === preg_match('/^[0-9]{9}$/', $this->params[1])
        ) {
            /** @var Doctrine\ORM\EntityManager $entityManager = */
            $entityManager         = $this->get('doctrine.orm.entity_manager');
            $this->translator      = $this->get('translator');
            $this->siren           = $this->params[1];
            $this->partner         = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);
            $this->companies       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['siren' => $this->siren]);
            $this->thirdPartyTypes = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerThirdPartyType')->findAll();
        }
    }

    public function _agences()
    {
        if (
            false === $this->request->isXmlHttpRequest()
            || empty($this->request->request->getInt('partner'))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $partner       = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->request->request->getInt('partner'));

        if (null === $partner) {
            $this->sendAjaxResponse(false, null, ['Partenaire inconnu']);
        }

        $agencies = [
            ['id' => $partner->getIdCompany()->getIdCompany(), 'name' => 'Siège']
        ];
        foreach ($entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['idParentCompany' => $partner->getIdCompany()]) as $agency) {
            $agencies[] = [
                'id'   => $agency->getIdCompany(),
                'name' => $agency->getName()
            ];
        }
        usort($agencies, function($first, $second) {
            return strcmp($first['name'], $second['name']);
        });

        $this->sendAjaxResponse(true, $agencies);
    }

    public function _utilisateurs()
    {
        if (
            false === $this->request->isXmlHttpRequest()
            || empty($this->request->request->getInt('agency'))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $agency        = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->request->request->getInt('agency'));

        if (null === $agency) {
            $this->sendAjaxResponse(false, null, ['Agence inconnue']);
        }

        $users = [];
        foreach ($entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient')->findBy(['idCompany' => $agency->getIdCompany()]) as $user) {
            $users[] = [
                'id'   => $user->getIdClient()->getIdClient(),
                'name' => $user->getIdClient()->getPrenom() . ' ' . $user->getIdClient()->getNom()
            ];
        }
        usort($users, function($first, $second) {
            return strcmp($first['name'], $second['name']);
        });

        $this->sendAjaxResponse(true, $users);
    }
}
