<?php

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Entity\{Attachment, AttachmentType, BeneficialOwner, BeneficialOwnerType, ClientAddress, CompanyBeneficialOwnerDeclaration, Pays,
    ProjectBeneficialOwnerUniversign, ProjectsStatus, Zones};
use Unilend\Repository\BeneficialOwnerRepository;
use Unilend\Service\BeneficialOwnerManager;

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
        $company       = $entityManager->getRepository(Companies::class)->find($this->params[0]);

        if (null === $company) {
            header('Location: ' . $this->lurl);
            die;
        }

        $currentOwners = [];
        $countryList   = $this->get('unilend.service.location_manager')->getCountries();
        $ownerTypes    = $this->getBeneficialOwnerTypes();

        $companyBeneficialOwnerDeclarationRepository = $entityManager->getRepository(CompanyBeneficialOwnerDeclaration::class);
        $currentDeclaration                          = $companyBeneficialOwnerDeclarationRepository->findCurrentDeclarationByCompany($company);
        $existingDeclarations                        = [];

        if (null !== $currentDeclaration) {
            $currentOwners        = $this->formatOwnerList($currentDeclaration->getBeneficialOwners());
            $existingDeclarations = $entityManager->getRepository(ProjectBeneficialOwnerUniversign::class)->findAllDeclarationsForCompany($company);

            if (empty($existingDeclarations)) {
                foreach ($entityManager->getRepository(Projects::class)->findBy(['status' => ProjectsStatus::STATUS_FUNDED, 'idCompany' => $company->getIdCompany()]) as $project) {
                    $existingDeclarations[] = $this->get('unilend.service.beneficial_owner_manager')->addProjectBeneficialOwnerDeclaration($currentDeclaration, $project);
                }
            }
        } else {
            $currentDeclaration = new CompanyBeneficialOwnerDeclaration();
            $currentDeclaration->setIdCompany($company)
                ->setStatus(CompanyBeneficialOwnerDeclaration::STATUS_PENDING);

            $entityManager->persist($currentDeclaration);
            $entityManager->flush($currentDeclaration);
            $entityManager->refresh($currentDeclaration);
        }

        if (isset($_SESSION['email_status'])) {
            $emailStatusErrors  = isset($_SESSION['email_status']['errors']) ? $_SESSION['email_status']['errors'] : null;
            $emailStatusSuccess = isset($_SESSION['email_status']['success']) ? $_SESSION['email_status']['success'] : null;
            unset($_SESSION['email_status']);
        }

        $passportType = $entityManager->getRepository(AttachmentType::class)->find(AttachmentType::CNI_PASSPORTE);
        $passport     = $entityManager->getRepository(Attachment::class)->findOneClientAttachmentByType($company->getIdClientOwner(), $passportType);

        /** @var ClientAddress $companyOwnerAddress */
        $companyOwnerAddress = $company->getIdClientOwner()->getIdAddress();

        $this->render(null, [
            'companyOwner'         => $company->getIdClientOwner(),
            'companyOwnerAddress'  => $companyOwnerAddress,
            'companyOwnerPassport' => null === $passport ? '' : '<a href="' . $this->lurl . '/attachment/download/id/' . $passport->getId() . '/file/' . urlencode($passport->getPath()) . '" target="_blank">' . $passport->getOriginalName() . '</a>',
            'beneficial_owners'    => $currentOwners,
            'countries'            => $countryList,
            'types'                => $ownerTypes,
            'currentDeclaration'   => $currentDeclaration,
            'company'              => $company,
            'projectDeclarations'  => $existingDeclarations,
            'emailStatusErrors'    => empty($emailStatusErrors) ? null : $emailStatusErrors,
            'emailStatusSuccess'   => empty($emailStatusSuccess) ? null : $emailStatusSuccess,
            'fiscalCountryId'      => $companyOwnerAddress instanceof ClientAddress ? $companyOwnerAddress->getIdCountry()->getIdPays() : 0,
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

            $this->sendAjaxResponse(empty($return['errors']), array_values($responseData), $return['errors'], $return['owner'] instanceof BeneficialOwner ? $return['owner']->getId() : null);
            return;
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

        $clientId           = $request->request->getInt('id');
        $lastName           = $request->request->filter('last_name', FILTER_SANITIZE_STRING);
        $firstName          = $request->request->filter('first_name', FILTER_SANITIZE_STRING);
        $birthDate          = $request->request->filter('birth_date', FILTER_SANITIZE_STRING);
        $birthPlace         = $request->request->filter('birth_place', FILTER_SANITIZE_STRING);
        $birthCountryId     = $request->request->getInt('birth_country');
        $countryOfResidence = $request->request->getInt('country');
        $type               = 0 === $request->request->getInt('type') ? null : $request->request->getInt('type');
        $percentage         = $request->request->getDigits('percentage');
        $declarationId      = $request->request->getInt('id_declaration');
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

        if (empty($birthCountryId)) {
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
        if (false === $this->checkDate($birthDate) || false === $birthday || false === $this->isAtLeastEighteenYearsOld($birthday)) {
            $errors[] = 'La date de naissance n\'est pas valide';
        }

        $checkBirthplace = $this->validateLocations($birthCountryId, $birthPlace);
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

        if (false === empty($type) && null === ($ownerType = $entityManager->getRepository(BeneficialOwnerType::class)->find($type))) {
            $errors[] = 'Le type de bénéficiaire effectif n\'est pas valide';
        }

        $minPercentage = bcdiv(100, BeneficialOwnerManager::MAX_NUMBER_BENEFICIAL_OWNERS_TYPE_SHAREHOLDER, 2);
        if (false === empty($percentage) && ($percentage < $minPercentage || $percentage > 100)) {
            $errors[] = 'Le pourcentage des parts détenues n\'est pas correct. Il ne doit pas être inférieur à ' . $minPercentage . '&nbsp;% ou supérieur à 100&nbsp;%';
        }

        /** @var \Unilend\Service\BeneficialOwnerManager $beneficialOwnerManager */
        $beneficialOwnerManager = $this->get('unilend.service.beneficial_owner_manager');
        $declaration            = null;
        if (false === empty($declarationId)) {
            /** @var CompanyBeneficialOwnerDeclaration $declaration */
            $declaration = $entityManager->getRepository(CompanyBeneficialOwnerDeclaration::class)->find($declarationId);
            if (null === $declaration) {
                $errors[] = 'Impossible de trouver la déclaration correspondante';
            }
        }

        if (null !== $declaration && null !== $ownerType) {
            /** @var BeneficialOwnerRepository $beneficialOwnerRepository */
            $beneficialOwnerRepository = $entityManager->getRepository(BeneficialOwner::class);
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

        $clientAttachment = null;
        if (false === empty($clientId)) {
            $owner = $entityManager->getRepository(Clients::class)->find($clientId);
            if (null === $owner) {
                $errors[] = 'Le client ID ' . $clientId . ' n\'existe pas';
            }

            if (false === empty($owner->getDateOfBirth()) && $owner->getDateOfBirth() != $birthday) {
                $errors[] = 'La date de naissance du client enregistrée en base et la date de naissance saisie ne correspondent pas';
            }

            if (false === empty($owner->getVilleNaissance()) && $owner->getVilleNaissance() !== $birthPlace) {
                $errors[] = 'Le lieu de naissance du client enregistré en base et le lieu de naissance saisi ne correspondent pas';
            }

            if (false === empty($owner->getIdPaysNaissance()) && $owner->getIdPaysNaissance() !== $birthCountryId) {
                $errors[] = 'Le pays de naissance du client enregistré en base et le pays de naissance saisi ne correspondent pas';
            }

            if (null === $request->files->all()) {
                $attachmentName = $request->request->filter('id_card_passport', FILTER_SANITIZE_STRING);
                if (null !== $attachmentName) {
                    $clientAttachment = $entityManager->getRepository(Attachment::class)->findOneClientAttachmentByType($owner, AttachmentType::CNI_PASSPORTE);
                    if ($clientAttachment->getOriginalName() !== $clientAttachment) {
                        $errors[] = 'Le nom de la pièce d\'identité transmis et le nom du document enregistré en base pour ce client ne correspondent pas.';
                    }
                } else {
                    $errors[] = 'Un pièce d\'identité doit être téléchargé.';
                }
            }

            if (false === empty($errors)) {
                return [
                    'owner'  => null,
                    'errors' => $errors
                ];
            }
        }

        if (null === $clientAttachment) {
            $clientAttachment = $request->files->get('id_card_passport');
        }

        try {
            $beneficialOwner = $beneficialOwnerManager->createBeneficialOwner(
                $declaration,
                $lastName,
                $firstName,
                $birthday,
                $birthPlace,
                $birthCountryId,
                $countryOfResidence,
                $clientAttachment,
                $ownerType,
                $percentage,
                $clientId
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
        $owner = $entityManager->getRepository(BeneficialOwner::class)->find($request->request->getInt('id'));
        if (null === $owner) {
            return [
                'owner'  => $owner,
                'errors' => ['Le bénéficiaire effectif n\'existe pas']
            ];
        }

        $lastName     = $request->request->filter('last_name', FILTER_SANITIZE_STRING);
        $firstName    = $request->request->filter('first_name', FILTER_SANITIZE_STRING);
        $birthDate    = $request->request->filter('birth_date', FILTER_SANITIZE_STRING);
        $birthPlace   = $request->request->filter('birth_place', FILTER_SANITIZE_STRING);
        $birthCountry = $request->request->getInt('birth_country');

        if (CompanyBeneficialOwnerDeclaration::STATUS_PENDING !== $owner->getIdDeclaration()->getStatus()) {
            if ($owner->getIdClient()->getLastName() !== $lastName) {
                $errors[] = 'Le nom du bénéficiaire effectif ne peut pas être modifié';
            }

            if ($owner->getIdClient()->getFirstName() !== $firstName) {
                $errors[] = 'Le prénom du bénéficiaire effectif ne peut pas être modifié';
            }

            if ($owner->getIdClient()->getDateOfBirth()->format('d/m/Y') !== $birthDate) {
                $errors[] = 'La date de naissance du bénéficiaire effectif ne peut pas être modifié';
            }

            if ($owner->getIdClient()->getBirthCity() !== $birthPlace) {
                $errors[] = 'Le lieu de naissance du bénéficiaire effectif ne peut pas être modifié';
            }

            if ($owner->getIdClient()->getIdBirthCountry() !== $birthCountry) {
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

        $countryOfResidenceId = $request->request->getInt('country');
        if (empty($countryOfResidenceId)) {
            $checkCountry = $this->validateLocations($countryOfResidenceId);
            if (false === $checkCountry['success']) {
                $errors[] = $checkCountry['error'] . ' (pays de résidence)';
            }
        }

        $type = 0 === $request->request->getInt('type') ? null : $request->request->getInt('type');
        if (false === empty($type) && null === $ownerType = $entityManager->getRepository(BeneficialOwnerType::class)->find($type)) {
            $errors[] = 'Le type de bénéficiaire effectif n\'est pas valide.';
        }

        $percentage    = $request->request->getDigits('percentage');
        $minPercentage = bcdiv(100, BeneficialOwnerManager::MAX_NUMBER_BENEFICIAL_OWNERS_TYPE_SHAREHOLDER, 2);
        if (false === empty($percentage) && ($percentage < $minPercentage || $percentage > 100)) {
            $errors[] = 'Le pourcentage des parts détenues n\'est pas correct. Il ne doit pas être inférieur à ' . $minPercentage . '&nbsp;% ou supérieur à 100&nbsp;%';
        }

        if (false === empty($errors)) {
            return [
                'owner'  => $owner,
                'errors' => $errors
            ];
        }

        if ('no_change' !== $request->request->get('id_card_passport') && null !== $request->files->all()) {
            /** @var \Unilend\Service\AttachmentManager $attachmentManager */
            $attachmentManager = $this->get('unilend.service.attachment_manager');
            $attachmentType    = $entityManager->getRepository(AttachmentType::class)->find(AttachmentType::CNI_PASSPORTE);
            if ($attachmentType) {
                $attachmentManager->upload($owner->getIdClient(), $attachmentType, $request->files->get('id_card_passport'));
            }
        }

        $owner->getIdClient()
            ->setLastName($lastName)
            ->setFirstName($firstName)
            ->setDateOfBirth($birthday)
            ->setIdBirthCountry($birthCountry)
            ->setBirthCity($birthPlace);

        $clientAddress = $owner->getIdClient()->getIdAddress();
        $countryOfResidence = $entityManager->getRepository(Pays::class)->find($countryOfResidenceId);
        $clientAddress->setIdCountry($countryOfResidence);

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
        $owner = $entityManager->getRepository(BeneficialOwner::class)->find($idBeneficialOwner);

        if (null === $owner) {
            $errors[] = 'Une erreur s\'est produite';
            $this->get('logger')->warning('Beneficial owner does not exist (idBeneficialOwner  = ' . $idBeneficialOwner, ['class' => __CLASS__, 'function' => __FUNCTION__]);

            return [
                'owner'  => null,
                'errors' => []
            ];
        }

        $currentDeclaration = $owner->getIdDeclaration();

        switch ($currentDeclaration->getStatus()) {
            case CompanyBeneficialOwnerDeclaration::STATUS_PENDING:
                $declaration = $owner->getIdDeclaration();

                $entityManager->remove($owner);
                $entityManager->flush($owner);

                $this->get('unilend.service.beneficial_owner_manager')->modifyPendingCompanyDeclaration($declaration);
                break;
            case CompanyBeneficialOwnerDeclaration::STATUS_VALIDATED:
                $currentDeclaration->setStatus(CompanyBeneficialOwnerDeclaration::STATUS_ARCHIVED);
                $entityManager->flush($currentDeclaration);

                $newDeclaration       = clone $owner->getIdDeclaration();
                $newDeclarationOwners = $newDeclaration->getBeneficialOwners();
                $newDeclarationOwners->removeElement($owner);

                $newDeclaration->setStatus(CompanyBeneficialOwnerDeclaration::STATUS_PENDING);

                $entityManager->persist($newDeclaration);
                $entityManager->flush($newDeclaration);

                foreach ($newDeclarationOwners as $newDeclarationOwner) {
                    $entityManager->detach($newDeclarationOwner);
                    $newDeclarationOwner->setIdDeclaration($newDeclaration);
                    $entityManager->persist($newDeclarationOwner);
                    $entityManager->flush($newDeclarationOwner);
                }
                break;
            default:
                break;
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

        $clientAddress = $owner->getIdClient()->getIdAddress();
        $passportType  = $entityManager->getRepository(AttachmentType::class)->find(AttachmentType::CNI_PASSPORTE);
        $passport      = $entityManager->getRepository(Attachment::class)->findOneClientAttachmentByType($owner->getIdClient(), $passportType);

        $responseData = [
            'last_name'        => $owner->getIdClient()->getLastName(),
            'first_name'       => $owner->getIdClient()->getFirstName(),
            'type'             => null === $owner->getIdType() ? null : $owner->getIdType()->getId(),
            'percentage'       => $owner->getPercentageDetained() == 0 ? '' : $owner->getPercentageDetained(),
            'birth_date'       => $owner->getIdClient()->getDateOfBirth()->format('d/m/Y'),
            'birth_place'      => $owner->getIdClient()->getBirthCity(),
            'birth_country'    => $owner->getIdClient()->getIdBirthCountry(),
            'country'          => $clientAddress->getIdCountry()->getIdPays(),
            'id_card_passport' => '<a href="' . $this->lurl . '/viewer/client/' . $owner->getIdClient()->getIdClient() . '/' . $passport->getId() . '" target="_blank">' . $passport->getOriginalName() . '</a>'
        ];

        if ($formatForView) {
            $countriesRepository = $entityManager->getRepository(Pays::class);
            $birthCountry        = $countriesRepository->find($owner->getIdClient()->getIdBirthCountry());
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
        if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $date)) {
            $dayMonthYear = explode('/', $date);

            return checkdate($dayMonthYear[1], $dayMonthYear[0], $dayMonthYear[2]);
        }

        return false;
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
        $country       = $entityManager->getRepository(Pays::class)->find($countryId);

        if (null === $country) {
            return [
                'success' => false,
                'error'   => 'Le pays n\'existe pas.'
            ];
        }

        if (Pays::COUNTRY_FRANCE === $country->getIdPays() && null !== $birthPlace) {
            /** @var \Unilend\Service\LocationManager $locationManager */
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
        foreach ($entityManager->getRepository(BeneficialOwnerType::class)->findAll() as $type) {
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
            $clientsRepository = $entityManager->getRepository(Clients::class);
            $result            = $clientsRepository->findBeneficialOwnerByName($search);

            foreach ($result as $client) {
                $ownerPassportUrl = empty($client['attachmentId']) ? '' : '<a href="' . $this->lurl . '/attachment/download/id/' . $client['attachmentId'] . '/file/' . urlencode($client['attachmentPath']) . '" target="_blank">' . $client['attachmentOriginalName'] . '</a>';

                $clients[] = [
                    'firstName'          => $client['prenom'],
                    'lastName'           => $client['nom'],
                    'id'                 => $client['idClient'],
                    'birthDate'          => $client['naissance']->format('d/m/Y'),
                    'birthPlace'         => $client['villeNaissance'],
                    'birthCountry'       => $client['idPaysNaissance'],
                    'country'            => $client['idPaysFiscal'],
                    'idCardPassportUrl'  => $ownerPassportUrl,
                    'idCardPassportName' => $client['attachmentOriginalName']
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($clients);
    }

    public function _declaration()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

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
        $company       = $entityManager->getRepository(Companies::class)->find($this->params[0]);

        if (null === $company) {
            header('Location: ' . $this->lurl);
            die;
        }

        $declaration = $entityManager->getRepository(CompanyBeneficialOwnerDeclaration::class)->findCurrentDeclarationByCompany($company);
        if (null === $declaration) {
            header('Location: ' . $this->lurl . '/beneficiaires_effectifs/' . $company->getIdCompany());
            die;
        }

        header('Content-type: application/pdf');
        echo $this->get('unilend.service.beneficial_owner_manager')->generateCompanyPdfFile($declaration);
    }

    public function _send_email()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

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
        $company       = $entityManager->getRepository(Companies::class)->find($this->params[0]);

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
        $project = $entityManager->getRepository(Projects::class)->find($this->params[1]);
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
