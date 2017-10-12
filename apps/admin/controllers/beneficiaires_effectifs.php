<?php

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\BeneficialOwner;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Repository\BeneficialOwnerRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\BeneficialOwnerManager;

class beneficiaires_effectifsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        if (false === isset($this->params[0])) {
            header('Location: ' . $this->lurl . '/emprunteurs/gestion');
            die;
        }

        if (false == filter_var($this->params[0], FILTER_VALIDATE_INT)) {
            header('Location: ' . $this->lurl);
            die;
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->params[0]);

        if (null === $company) {
            header('Location: ' . $this->lurl);
            die;
        }

        $currentOwners = [];
        $countryList   = $this->get('unilend.service.location_manager')->getCountries();
        $ownerTypes    = $this->getBeneficialOwnerTypes();

        $companyBeneficialOwnerDeclarationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyBeneficialOwnerDeclaration');
        $currentDeclaration                          = $companyBeneficialOwnerDeclarationRepository->findCurrentBeneficialOwnerDeclaration($company);
        if (null !== $currentDeclaration) {
            $currentOwners = $this->formatOwnerList($currentDeclaration->getBeneficialOwner());
        }

        if (isset($_SESSION['email_status'])) {
            $emailStatusErrors  = isset($_SESSION['email_status']['errors']) ? $_SESSION['email_status']['errors'] : null;
            $emailStatusSuccess = isset($_SESSION['email_status']['success']) ? $_SESSION['email_status']['success'] : null;
            unset($_SESSION['email_status']);
        }

        $this->render(null, [
            'beneficial_owners'      => $currentOwners,
            'countries'              => $countryList,
            'types'                  => $ownerTypes,
            'declaration'            => $currentDeclaration,
            'company'                => $company,
            'universignDeclarations' => $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectBeneficialOwnerUniversign')->findAllDeclarationsForCompany($company),
            'emailStatusErrors'      => empty($emailStatusErrors) ? null : $emailStatusErrors,
            'emailStatusSuccess'     => empty($emailStatusSuccess) ? null : $emailStatusSuccess,
        ]);
    }

    /**
     * @param BeneficialOwner[] $owners
     *
     * @return array
     */
    private function formatOwnerList($owners)
    {
        $ownerList = [];
        foreach ($owners as $owner) {
            $ownerList[$owner->getId()] = $this->formatOwnerData($owner, true);
        }
        return $ownerList;
    }

    public function _edit()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        if ($this->request->isXmlHttpRequest()) {
            $action       = $this->request->request->filter('action', FILTER_SANITIZE_STRING);
            $state        = '';
            $responseData = [];

            switch ($action) {
                case 'create':
                    $return = $this->createBeneficialOwner($this->request);
                    break;
                case 'modify':
                    $return = $this->modifyBeneficialOwner($this->request);
                    break;
                case 'delete':
                    $return = $this->deactivateBeneficialOwner($this->request);
                    break;
                default:
                    throw new \Exception('Action not supported');
            }

            if ($return['owner'] instanceof BeneficialOwner) {
                $responseData = $this->formatOwnerData($return['owner']);
            }

            header('Content-Type: application/json');

            echo json_encode([
                'success' => empty($return['errors']),
                'error'   => $return['errors'],
                'id'      => $return['owner'] instanceof BeneficialOwner ? $return['owner']->getId() : null,
                'state'   => $state, // State must be separate from the data in the response
                'data'    => array_values($responseData) // Values must be in the same order as in the request
            ]);
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     * @throws Exception
     */
    private function createBeneficialOwner(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $translator    = $this->get('translator');
        $ownerType     = null;
        $errors        = [];

        $idClient           = $request->request->getInt('id');
        $lastName           = $request->request->filter('last_name', FILTER_SANITIZE_STRING);
        $firstName          = $request->request->filter('first_name', FILTER_SANITIZE_STRING);
        $birthDate          = $request->request->filter('birthdate', FILTER_SANITIZE_STRING);
        $birthPlace         = $request->request->filter('birthplace', FILTER_SANITIZE_STRING);
        $idBirthCountry     = $request->request->getInt('birth_country');
        $countryOfResidence = $request->request->getInt('country');
        $type               = 0 === $request->request->getInt('type') ? null : $request->request->getInt('type');
        $percentage         = $request->request->getDigits('percentage');
        $idDeclaration      = $request->request->getInt('id_declaration');
        $idCompany          = $request->request->getInt('id_company');

        if (empty($lastName)) {
            $errors[] = 'Le nom doit être rempli';
        }

        if (empty($firstName)) {
            $errors[] = 'Le prénom doit être rempli';
        }

        if (empty($birthDate)) {
            $errors[] = 'La date de naissance doit être rempli';
        }

        if (empty($birthPlace)) {
            $errors[] = 'Le lieu de naissance doit être rempli';
        }

        if (empty($idBirthCountry)) {
            $errors[] = 'Le pays de naissance doit être rempli';
        }

        if (empty($countryOfResidence)) {
            $errors[] = 'Le pays de résidence doit être rempli';
        }

        if (false === empty($errors)) {
            return [
                'owner'  => null,
                'errors' => $errors
            ];
        }

        $birthday = \DateTime::createFromFormat('d/m/Y', $birthDate);
        $birthday->setTime(0, 0, 0);
        if (false == $this->checkDate($birthDate) || false === $birthday || false === $this->isAtLeastEighteenYearsOld($birthday)) {
            $errors[] = 'La date de naissance n\'est pas valide';
        }

        $checkBirthplace = $this->validateLocations($idBirthCountry, $birthPlace);
        if (false === $checkBirthplace['success']) {
            $errors[] = $checkBirthplace['error'] . ' (naissance)';
        }

        $countryOfResidence = $request->request->getInt('country');
        if (empty($countryOfResidence)) {
            $checkCountry = $this->validateLocations($countryOfResidence);
            if (false === $checkCountry['success']) {
                $errors[] = $checkCountry['error'] . ' (résidence)';
            }
        }

        if (false === empty($type) && null === ($ownerType = $entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwnerType')->find($type))) {
            $errors[] = 'Le type de bénéficiaire effectif n\'est pas valide';
        }

        $minPercentage = 100 / BeneficialOwnerManager::MAX_NUMBER_BENEFICIAL_OWNERS_TYPE_SHAREHOLDER;
        if (false === empty($percentage) && ($percentage < $minPercentage || $percentage > 100)) {
            $errors[] = 'Le pourcentage des parts détenues n\'est pas correct. Il ne doit pas être inférieur à ' . $minPercentage . '&nbsp;% ou supérieur à 100&nbsp;%';
        }

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BeneficialOwnerManager $beneficialOwnerManager */
        $beneficialOwnerManager = $this->get('unilend.service.beneficial_owner_manager');
        /** @var CompanyBeneficialOwnerDeclaration $declaration */
        $declaration = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyBeneficialOwnerDeclaration')->find($idDeclaration);

        if (null !== $declaration && null !== $ownerType) {
            /** @var BeneficialOwnerRepository $beneficialOwnerRepository */
            $beneficialOwnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwner');
            $numberBeneficialOwners    = $beneficialOwnerRepository->getCountBeneficialOwnersForDeclarationByType($declaration, $ownerType->getLabel());
            $maxNumber                 = $beneficialOwnerManager->getMaxNumbersAccordingToType($ownerType->getLabel());
            if ($numberBeneficialOwners > $maxNumber) {
                $errors[] = 'Le nombre de bénéficiaires effectifs de type ' . $translator->trans('beneficial-owner_type-label-' . $ownerType->getLabel()) . ' ne peut pas être supérieur à ' . $maxNumber;
            }

            $sumPercentage = $beneficialOwnerRepository->getSumPercentage($declaration);
            if (bcadd($sumPercentage, $percentage, 4) > 100) {
                $errors[] = 'La somme des pourcentages ne peut pas être supérieure à 100&nbsp;%';
            }
        }

        if (false === empty($errors)) {
            return [
                'owner'  => null,
                'errors' => $errors
            ];
        }

        if (null === $declaration) {
            $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($idCompany);

            if (null === $company) {
                throw new \Exception('Company ' . $idCompany . ' does not exist');
            }

            $declaration = new CompanyBeneficialOwnerDeclaration();
            $declaration
                ->setIdCompany($company)
                ->setStatus(CompanyBeneficialOwnerDeclaration::STATUS_PENDING);
            $entityManager->persist($declaration);
        }

        if (false === empty($idClient)) {
            $owner = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClient);
            if (null === $owner) {
                $errors[] = 'Le client ID ' . $idClient . ' n\'existe pas';
            }

            if (false === empty($owner->getNaissance()) && $owner->getNaissance() != $birthday) {
                $errors[] = 'La date de naissance du client enregistrée en base et la date de naissance saisie ne correspondent pas';
            }

            if (false === empty($owner->getVilleNaissance()) && $owner->getVilleNaissance() !== $birthPlace) {
                $errors[] = 'Le lieu de naissance du client enregistré en base et le lieu de naissance saisi ne correspondent pas';
            }

            if (false === empty($owner->getIdPaysNaissance()) && $owner->getIdPaysNaissance() !== $idBirthCountry) {
                $errors[] = 'Le pays de naissance du client enregistré en base et le pays de naissance saisi ne correspondent pas';
            }

            if (false === empty($errors)) {
                return [
                    'owner'  => null,
                    'errors' => $errors
                ];
            }
        }

        try {
            $beneficialOwner = $beneficialOwnerManager->createBeneficialOwner(
                $declaration,
                $lastName,
                $firstName,
                $birthday,
                $birthPlace,
                $idBirthCountry,
                $countryOfResidence,
                $request->files->get('id_card_passport'),
                $ownerType,
                $percentage,
                $idClient
            );
        } catch (\Exception $exception) {
            $beneficialOwner = null;
            $errors[]        = 'Une erreur s\'est produite.';
            $this->get('logger')->warning($exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        return [
            'owner'  => $beneficialOwner,
            'errors' => $errors
        ];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function modifyBeneficialOwner(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $errors        = [];

        /** @var BeneficialOwner $owner */
        $owner = $entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwner')->find($request->request->getInt('id'));
        if (null === $owner) {
            return [
                'owner'  => $owner,
                'errors' => ['Le bénéficiaire effectif n\'existe pas']
            ];
        }

        $lastName     = $request->request->filter('last_name', FILTER_SANITIZE_STRING);
        $firstName    = $request->request->filter('first_name', FILTER_SANITIZE_STRING);
        $birthDate    = $request->request->filter('birthdate', FILTER_SANITIZE_STRING);
        $birthPlace   = $request->request->filter('birthplace', FILTER_SANITIZE_STRING);
        $birthCountry = $request->request->getInt('birth_country');

        if (CompanyBeneficialOwnerDeclaration::STATUS_PENDING !== $owner->getIdDeclaration()->getStatus()) {
            if ($owner->getIdClient()->getNom() !== $lastName) {
                $errors[] = 'Le nom du bénéficiaire effectif ne peut pas être modifié';
            }

            if ($owner->getIdClient()->getPrenom() !== $firstName) {
                $errors[] = 'Le prénom du bénéficiaire effectif ne peut pas être modifié';
            }

            if ($owner->getIdClient()->getNaissance()->format('d/m/Y') !== $birthDate) {
                $errors[] = 'La date de naissance du bénéficiaire effectif ne peut pas être modifié';
            }

            if ($owner->getIdClient()->getVilleNaissance() !== $birthPlace) {
                $errors[] = 'Le lieu de naissance du bénéficiaire effectif ne peut pas être modifié';
            }

            if ($owner->getIdClient()->getIdPaysNaissance() !== $birthCountry) {
                $errors[] = 'Le pays de naissance du bénéficiaire effectif ne peut pas être modifié';
            }

            if (false === empty($errors)) {
                return [
                    'owner'  => $owner,
                    'errors' => $errors
                ];
            }
        }

        $birthday = \DateTime::createFromFormat('d/m/Y', $birthDate);
        if (false == $this->checkDate($birthDate) || false === $birthday || false === $this->isAtLeastEighteenYearsOld($birthday)) {
            $errors[] = 'La date de naissance n\'est pas valide';
        }

        $checkBirthplace = $this->validateLocations($birthCountry, $birthPlace);
        if (false === $checkBirthplace['success']) {
            $errors[] = $checkBirthplace['error'];
        }

        $countryOfResidence = $request->request->getInt('country');
        if (empty($countryOfResidence)) {
            $checkCountry = $this->validateLocations($countryOfResidence);
            if (false === $checkCountry['success']) {
                $errors[] = $checkCountry['error'] . ' (pays de résidence)';
            }
        }

        $type = 0 === $request->request->getInt('type') ? null : $request->request->getInt('type');
        if (false === empty($type) && null === $ownerType = $entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwnerType')->find($type)) {
            $errors[] = 'Le type de bénéficiaire effectif n\'est pas valide.';
        }

        $percentage = $request->request->getDigits('percentage');
        if (false === empty($percentage) && $percentage < 25 || $percentage > 100) {
            $errors[] = 'Le pourcentage des parts détenues n\'est pas correct. Il ne doit pas être inférieur à 25% ou supérieur à 100%';
        }

        if (false === empty($errors)) {
            return [
                'owner'  => $owner,
                'errors' => $errors
            ];
        }

        if ('no_change' !== $request->request->get('id_card_passport') && null !== $request->files->all()) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
            $attachmentManager = $this->get('unilend.service.attachment_manager');
            $attachmentType    = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::CNI_PASSPORTE);
            if ($attachmentType) {
                $attachmentManager->upload($owner->getIdClient(), $attachmentType, $request->files->get('id_card_passport'));
            }
        }

        $owner->getIdClient()
            ->setNom($lastName)
            ->setPrenom($firstName)
            ->setNaissance($birthday)
            ->setIdPaysNaissance($birthCountry)
            ->setVilleNaissance($birthPlace);

        $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $owner->getIdClient()]);
        $clientAddress->setIdPaysFiscal($countryOfResidence);

        $entityManager->flush([$owner->getIdClient(), $clientAddress]);

        if ($percentage != $owner->getPercentageDetained() || null !== $owner->getIdType() && $type != $owner->getIdType()->getId()) {
            /** @var BeneficialOwnerManager $beneficialOwnerManager */
            $beneficialOwnerManager = $this->get('unilend.service.beneficial_owner_manager');
            $beneficialOwnerManager->modifyBeneficialOwner($owner, $type, $percentage);
        }

        return [
            'owner'  => $owner,
            'errors' => []
        ];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function deactivateBeneficialOwner(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $errors        = [];

        $idBeneficialOwner = $request->request->getInt('id');
        /** @var BeneficialOwner $owner */
        $owner = $entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwner')->find($idBeneficialOwner);

        if (null === $owner) {
            $errors[] = 'Une erreur s\'est produite';
            $this->get('logger')->warning('Beneficial owner does not exist (idBeneficialOwner  = ' . $idBeneficialOwner, ['class' => __CLASS__, 'function' => __FUNCTION__]);

            return [
                'owner'  => null,
                'errors' => []
            ];
        }

        if (CompanyBeneficialOwnerDeclaration::STATUS_PENDING === $owner->getIdDeclaration()->getStatus()) {
            $declaration = $owner->getIdDeclaration();

            $entityManager->remove($owner);
            $entityManager->flush($owner);

            $this->get('unilend.service.beneficial_owner_manager')->modifyPendingCompanyDeclaration($declaration);
        }

        if (CompanyBeneficialOwnerDeclaration::STATUS_VALIDATED === $owner->getIdDeclaration()->getStatus()) {
            $newDeclaration = clone $owner->getIdDeclaration();
            $newDeclaration->setStatus(CompanyBeneficialOwnerDeclaration::STATUS_PENDING);

            $entityManager->persist($newDeclaration);
            $entityManager->flush($newDeclaration);

            $ownerToArchive = $entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwner')->findOneBy(['idClient' => $owner->getIdClient(), 'idDeclaration' => $newDeclaration->getId()]);
            $entityManager->remove($ownerToArchive);
            $entityManager->flush($ownerToArchive);
        }

        return [
            'owner'  => null,
            'errors' => []
        ];
    }

    /**
     * @param BeneficialOwner $owner
     * @param bool            $formatForView
     *
     * @return array
     */
    private function formatOwnerData(BeneficialOwner $owner, $formatForView = false)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->get('translator');

        $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $owner->getIdClient()]);
        $passportType  = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::CNI_PASSPORTE);
        $passport      = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneClientAttachmentByType($owner->getIdClient(), $passportType);

        $responseData = [
            'last_name'        => $owner->getIdClient()->getNom(),
            'first_name'       => $owner->getIdClient()->getPrenom(),
            'type'             => null === $owner->getIdType() ? null : $owner->getIdType()->getId(),
            'percentage'       => $owner->getPercentageDetained(),
            'birthdate'        => $owner->getIdClient()->getNaissance()->format('d/m/Y'),
            'birthplace'       => $owner->getIdClient()->getVilleNaissance(),
            'birth_country'    => $owner->getIdClient()->getIdPaysNaissance(),
            'country'          => $clientAddress->getIdPaysFiscal(),
            'id_card_passport' => '<a href="' . $this->lurl . '/attachment/download/id/' . $passport->getId() . '/file/' . urlencode($passport->getPath()) . '" target="_blank">' . $passport->getOriginalName() . '</a>',
            'status'           => $translator->trans('beneficial-owner_declaration-status-' . $owner->getIdDeclaration()->getStatus()),
            'validation_type'  => CompanyBeneficialOwnerDeclaration::STATUS_VALIDATED === $owner->getIdDeclaration()->getStatus() ? BeneficialOwnerManager::VALIDATION_TYPE_UNIVERSIGN : ''
        ];

        if ($formatForView) {
            $countriesRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2');
            $birthCountry        = $countriesRepository->find($owner->getIdClient()->getIdPaysNaissance());
            $country             = $countriesRepository->find($clientAddress->getIdPaysFiscal());

            $responseData['id']            = $owner->getId();
            $responseData['birth_country'] = $birthCountry->getFr();
            $responseData['country']       = $country->getFr();
            $responseData['type']          = $owner->getIdType() ? $translator->trans('beneficial-owner_type-label-' . $owner->getIdType()->getLabel()) : '';
        }

        return $responseData;
    }

    /**
     * @param \DateTime $birthDay
     *
     * @return bool
     */
    private function isAtLeastEighteenYearsOld(\DateTime $birthDay)
    {
        $now      = new \DateTime('NOW');
        $dateDiff = $birthDay->diff($now);

        return $dateDiff->y >= 18;
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    private function checkDate($date)
    {
        $dayMonthYear = explode('/', $date);

        return checkdate($dayMonthYear[1], $dayMonthYear[0], $dayMonthYear[2]);
    }

    /**
     * @param int         $countryId
     * @param string|null $birthPlace
     *
     * @return array
     */
    private function validateLocations($countryId, $birthPlace = null)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $country       = $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($countryId);

        if (null === $country) {
            return [
                'success' => false,
                'error'   => 'Le pays n\'existe pas.'
            ];
        }

        if (PaysV2::COUNTRY_FRANCE === $country->getIdPays() && null !== $birthPlace) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\LocationManager $locationManager */
            $locationManager = $this->get('unilend.service.location_manager');
            $city            = $locationManager->getCities($birthPlace, true);
            if (empty($city)) {
                return [
                    'success' => false,
                    'error'   => 'La ville n\'existe pas en France.'
                ];
            }
        }

        return [
            'success' => true,
            'error'   => ''
        ];
    }

    private function getBeneficialOwnerTypes()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->get('translator');

        $types = [];
        foreach ($entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwnerType')->findAll() as $type) {
            $types[$type->getId()] = $translator->trans('beneficial-owner_type-label-' . $type->getLabel());
        }
        return $types;
    }

    public function _search_client()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        $clients = [];

        if ($search = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING)) {
            /** @var EntityManager $entityManager */
            $entityManager     = $this->get('doctrine.orm.entity_manager');
            $clientsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');

            if (filter_var($search, FILTER_VALIDATE_INT)) {
                $result = $clientsRepository->findBy(['idClient' => $search]);
            } else {
                $result = $clientsRepository->findClientByNameLike($search);
            }

            foreach ($result as $client) {
                $clients[] = [
                    'firstName' => $client->getPrenom(),
                    'lastName'  => $client->getNom(),
                    'id'        => $client->getIdClient()
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($clients);
    }

    public function _declaration()
    {
        if (false === isset($this->params[0])) {
            header('Location: ' . $this->lurl);
            die;
        }

        if (false == filter_var($this->params[0], FILTER_VALIDATE_INT)) {
            header('Location: ' . $this->lurl);
            die;
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->params[0]);

        if (null === $company) {
            header('Location: ' . $this->lurl);
            die;
        }

        $declaration = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyBeneficialOwnerDeclaration')->findCurrentBeneficialOwnerDeclaration($company);
        if (null === $declaration) {
            header('Location: ' . $this->lurl . '/beneficiaires_effectifs/' . $company->getIdCompany());
            die;
        }

        header('Content-type: application/pdf');
        echo $this->get('unilend.service.beneficial_owner_manager')->generateCompanyPdfFile($declaration);
    }

    public function _send_email()
    {
        if (false === isset($this->params[0])) {
            header('Location: ' . $this->lurl);
            die;
        }
        if (false == filter_var($this->params[0], FILTER_VALIDATE_INT)) {
            header('Location: ' . $this->lurl);
            die;
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->params[0]);

        if (null === $company) {
            header('Location: ' . $this->lurl);
            die;
        }
        if (false === isset($this->params[1])) {
            header('Location: ' . $this->lurl . '/beneficiaires_effectifs/' . $company->getIdCompany());
            die;
        }
        if (false == filter_var($this->params[1], FILTER_VALIDATE_INT)) {
            header('Location: ' . $this->lurl . '/beneficiaires_effectifs/' . $company->getIdCompany());
            die;
        }
        $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->params[1]);
        if (null === $project) {
            header('Location: ' . $this->lurl . '/beneficiaires_effectifs/' . $company->getIdCompany());
            die;
        }

        if ($this->get('unilend.service.email_manager')->sendFundedAndFinishedToBorrower($project)) {
            $_SESSION['email_status'] = [
                'success' => 'L\'email avec les documents à signer a été envoyé avec succès.',
                'errors'  => ''
            ];
        } else {
            $_SESSION['email_status'] = [
                'success' => '',
                'errors'  => 'Une erreur s\'est produite.'
            ];
        }

        header('Location: ' . $this->lurl . '/beneficiaires_effectifs/' . $company->getIdCompany());
        die;
    }
}
