<?php

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\BeneficialOwner;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Repository\BeneficialOwnerRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\BeneficialOwnerManager;

class beneficial_ownersController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess();

        $this->menu_admin = 'oneui';
    }

    public function _default()
    {
        if (false === isset($this->params[0])) {
            //TODO error message and/or redirect
        }

        if (false == filter_var($this->params[0], FILTER_VALIDATE_INT)) {
            //TODO error message and/or redirect
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->params[0]);

        if (null === $company) {
            //TODO error message and/or redirect
        }

        $companyBeneficialOwnerDeclarationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyBeneficialOwnerDeclaration');
        $currentDeclaration                          = $companyBeneficialOwnerDeclarationRepository->findCurrentBeneficialOwnerDeclaration($company);
        $currentOwners                               = $this->formatOwnerList($currentDeclaration->getBeneficialOwner());

        $countryList = $this->get('unilend.service.location_manager')->getCountries();
        $ownerTypes  = $this->getBeneficialOwnerTypes();

        $this->render(null, [
            'beneficial_owners' => $currentOwners,
            'countries'         => json_encode($countryList),
            'types'             => json_encode($ownerTypes)
        ]);
    }

    private function formatOwnerList($owners)
    {
        $ownerList = [];

        foreach ($owners as $owner) {
            $ownerList[$owner->getId()] = $this->formatOwnerData($owner);
        }

        return $ownerList;
    }


    public function _edit()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        if ($this->request->isXmlHttpRequest()) {
            $action       = $this->request->request->get('action');
            $state        = '';
            $responseData = [];

            switch($action) {
                case 'create' :
                    $return = $this->createBeneficialOwner($this->request);
                    break;
                case 'modify':
                    $return = $this->modifyBeneficialOwner($this->request);
                    break;
                case 'deactivate':
                    $return = $this->deactivateBeneficialOwner($this->request);
                    break;
                default:
                    throw new \Exception('Action not supported'); //TODO better that
            }

            if ($return['owner'] instanceof BeneficialOwner) {
                $responseData = $this->formatOwnerData($return['owner']);
            }

            echo json_encode([
                'success' => empty($return['errors']),
                'error'   => $return['errors'],
                'id'      => $return['id'],
                'state'   => $state, // State must be separate from the data in the response
                'data'    => $responseData // Values must be in the same order as in the request
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
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $errors        = [];

        $lastName           = $request->request->filter('last_name', FILTER_SANITIZE_STRING);
        $firstName          = $request->request->filter('first_name', FILTER_SANITIZE_STRING);
        $birthDate          = $request->request->filter('birthdate', FILTER_SANITIZE_STRING);
        $birthPlace         = $request->request->filter('birthplace', FILTER_SANITIZE_STRING);
        $idBirthCountry     = $request->request->getInt('birth_country');
        $countryOfResidence = $request->request->getInt('country');
        $ownerType          = $request->request->filter('type', FILTER_SANITIZE_STRING);
        $percentage         = $request->request->getDigits('percentage');
        $idDeclaration      = $request->request->getInt('id_declaration');
        $idCompany          = $request->request->getInt('id_company');

        if (empty($lastName)) {
            $errors[] = 'Le nom doit etre rempli.';
        }

        if (empty($firstName)) {
            $errors[] = 'le prénom doit etre rempli.';
        }

        if (empty($birthDate)) {
            $errors[] = 'La date de naissance doit être rempli.';
        }

        if (empty($birthPlace)) {
            $errors[] = 'Le lieu de naissance doit être rempli.';
        }

        if (empty($idBirthCountry)) {
            $errors[] = 'Le pays de naissance doit être rempli.';
        }

        if (empty($countryOfResidence)) {
            $errors[] = 'Le pays de résidence doit être rempli.';
        }

        if (false === empty($errors)) {
            return [
                'owner'  => null,
                'errors' => $errors,
                'id'     => null
            ];
        }

        $birthday = \DateTime::createFromFormat('d/m/Y', $birthDate);
        if (false == $this->checkDate($birthDate) || false === $birthday || false === $this->isAtLeastEighteenYearsOld($birthday)) {
            $errors[] = 'La date de naissance n\'est pas valide';
        }

        $checkBirthplace = $this->validateLocations($idBirthCountry, $birthPlace);
        if (false === $checkBirthplace['success']) {
            $errors[] = $checkBirthplace['error'] . '(Pays de naissance)';
        }

        $countryOfResidence = $request->request->getInt('country');
        if (empty($countryOfResidence)) {
            $checkCountry = $this->validateLocations($countryOfResidence);
            if (false === $checkCountry['success']) {
                $errors[] = $checkCountry['error'] . ' (Pays de résidence)';
            }
        }

        if (false === empty($ownerType) && false === in_array($ownerType, $this->getBeneficialOwnerTypes())) {
            $errors[] = 'Le type de bénéficiaire effectiv n\'est pas valide.';
        }

        $minPercentage = 100 / BeneficialOwnerManager::MAX_NUMBER_BENEFICIAL_OWNERS;
        if (false === empty($percentage) && $percentage < $minPercentage || $percentage > 100) {
            $errors[] = 'Le pourcentage des parts detenues n\'est pas correct. Il ne doit pas être inférieur à ' .  $minPercentage . '% ou supérieur à 100%';
        }

        /** @var CompanyBeneficialOwnerDeclaration $declaration */
        $declaration = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyBeneficialOwnerDeclaration')->find($idDeclaration);

        if (null !== $declaration) {
            /** @var BeneficialOwnerRepository $beneficialOwnerRepository */
            $beneficialOwnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwner');
            $numberOwners              = $beneficialOwnerRepository->getCountBeneficialOwnersForDeclaration($declaration);
            if ($numberOwners > BeneficialOwnerManager::MAX_NUMBER_BENEFICIAL_OWNERS) {
                $errors[] = 'Le nombre de bénéficiaires effectifs ne peut pas être supérieur à ' . BeneficialOwnerManager::MAX_NUMBER_BENEFICIAL_OWNERS;
            }

            $sumPercentage = $beneficialOwnerRepository->getSumPercentage($declaration);
            if (bcadd($sumPercentage, $percentage, 4) > 100) {
                $errors[] = 'La somme des pourcentages ne peut pas être supérieur à 100%.';
            }
        }

        if (false === empty($errors)) {
            return [
                'owner'  => null,
                'errors' => $errors,
                'id'     => null
            ];
        }

        if (null === $declaration) {
            $company   = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($idCompany);

            if (null === $company) {
                throw new \Exception('Entreprise ' . $idCompany . ' does not exist');
            }

            $declaration = new CompanyBeneficialOwnerDeclaration();
            $declaration->setIdCompany($company)
                ->setStatus(CompanyBeneficialOwnerDeclaration::STATUS_PENDING);
            $entityManager->persist($declaration);
        }

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BeneficialOwnerManager $beneficialOwnerManager */
        $beneficialOwnerManager = $this->get('unilend.service.beneficial_owner_manager');
        $beneficialOwner        = $beneficialOwnerManager->createBeneficialOwner($declaration, $lastName, $firstName, $birthday, $birthPlace, $idBirthCountry, $countryOfResidence, $request->files->get('id_card_passport'), $ownerType, $percentage);

        return [
            'owner'  => $beneficialOwner,
            'errors' => [],
            'id'     => $beneficialOwner->getId()
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
                'owner'    => $owner,
                'errors'   => ['Le bénéficiaire effectif n\'existe pas'],
                'id' => $request->request->getInt('id')
            ];
        }

        $lastName     = $request->request->filter('last_name', FILTER_SANITIZE_STRING);
        $firstName    = $request->request->filter('first_name', FILTER_SANITIZE_STRING);
        $birthDate    = $request->request->filter('birthdate', FILTER_SANITIZE_STRING);
        $birthPlace   = $request->request->filter('birthplace', FILTER_SANITIZE_STRING);
        $birthCountry = $request->request->getInt('birth_country');

        if (CompanyBeneficialOwnerDeclaration::STATUS_PENDING !== $owner->getIdDeclaration()->getStatus()) {
            if ($owner->getIdClient()->getNom() !== $lastName) {
                $errors[] = 'Le nom du bénéficiaire effectif ne peut pas être modifié.';
            }

            if ($owner->getIdClient()->getPrenom() !== $firstName) {
                $errors[] = 'Le prénom du bénéficiaire effectif ne peut pas être modifié.';
            }

            if ($owner->getIdClient()->getNaissance()->format('d/m/Y') !== $birthDate) {
                $errors[] = 'La date de naissance du bénéficiaire effectif ne peut pas être modifié.';
            }

            if ($owner->getIdClient()->getVilleNaissance() !== $birthPlace) {
                $errors[] = 'Le lieu de naissance du bénéficiaire effectif ne peut pas être modifié.';
            }

            if ($owner->getIdClient()->getIdPaysNaissance() !== $birthCountry) {
                $errors[] = 'Le pays de naissance du bénéficiaire effectif ne peut pas être modifié.';
            }

            if (false === empty($errors)) {
                return [
                    'owner'  => $owner,
                    'errors' => $errors,
                    'id'     => $owner->getId()
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
                $errors[] = $checkCountry['error'] . ' (Pays de résidence)';
            }
        }

        $ownerType = $request->request->filter('type', FILTER_SANITIZE_STRING);
        if (false === empty($ownerType) && false === in_array($ownerType, $this->getBeneficialOwnerTypes())) {
            $errors[] = 'Le type de bénéficiaire effectiv n\'est pas valide.';
        }

        $percentage = $request->request->getDigits('percentage');
        if (false === empty($percentage) && $percentage < 25 || $percentage > 100) {
            $errors[] = 'Le pourcentage des parts detenues n\'est pas correct. Il ne doit pas être inférieur à 25% ou supérieur à 100%';
        }

        if (false === empty($errors)) {
            return [
                'owner'  => $owner,
                'errors' => $errors,
                'id'     => $owner->getId()
            ];
        }

        if ('no_change' !== $request->request->get('id_card_passport') && null !== $request->files->all()) {
            $attachmentManager = $this->get('unilend.service.attachment_manager');
            $attachmentType    = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::CNI_PASSPORTE);
            if ($attachmentType) {
                $attachment = $attachmentManager->upload($owner->getIdClient(), $attachmentType, $request->files->get('id_card_passport'));
            }
        }

        $owner->getIdClient()->setNom($lastName)
            ->setPrenom($firstName)
            ->setNaissance($birthday)
            ->setIdPaysNaissance($birthCountry)
            ->setVilleNaissance($birthPlace);

        $ownerTypeEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwnerType')->find($ownerType);
        $percentage      = empty($percentage) ? null : $percentage;
        $owner->setIdType($ownerTypeEntity)
            ->setPercentageDetained($percentage);

        $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $owner->getIdClient()]);
        $clientAddress->setIdPaysFiscal($countryOfResidence);

        $entityManager->flush([$owner, $clientAddress]);

        return [
            'owner'  => $owner,
            'errors' => [],
            'id'     => $owner->getId()
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
            $errors[] = 'Une erreur c\'est produit';
            $this->get('logger')->warning('Beneficial owner does not exist (idBeneficialOwner  = ' . $idBeneficialOwner, ['class' => __CLASS__, 'function' => __FUNCTION__]);
            return [
                'owner'  => $owner,
                'errors' => [],
                'id'     => $owner->getId()
            ];
        }

        if (CompanyBeneficialOwnerDeclaration::STATUS_PENDING === $owner->getIdDeclaration()->getStatus()) {
            $entityManager->remove($owner);
            $entityManager->flush($owner);
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

        header('Location: ' . $this->lurl . '/beneficial_owners');
        die;
    }


    private function formatOwnerData(BeneficialOwner $owner)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->get('translator');

        $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $owner->getIdClient()]);
        $country       = $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($clientAddress->getIdPaysFiscal());
        $passportType  = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->findOneBy(['label' => AttachmentType::CNI_PASSPORTE]);
        $passport      = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneClientAttachmentByType($owner->getIdClient(), $passportType);

        $responseData = [
            'id'               => $owner->getId(),
            'last_name'        => $owner->getIdClient()->getNom(),
            'first_name'       => $owner->getIdClient()->getPrenom(),
            'percentage'       => null === $owner->getPercentageDetained() ? 0 : $owner->getPercentageDetained(),
            'birthdate'        => $owner->getIdClient()->getNaissance()->format('d/m/Y'),
            'birthplace'       => $owner->getIdClient()->getVilleNaissance(),
            'country'          => $country->getFr(),
            'id_card_passport' => null !== $passport ? '<a href="' . $this->url . 'attachment/download/id' . $passport->getId() . '/file/' .  urlencode($passport->getPath()) . '">' . $passport->getOriginalName() . '</a>' : 'No file', //TODO delete the exception once data ok in DB
            'type'             => $owner->getIdType()->getId(),
            'status'           => $translator->trans('beneficial-owner_declaration-status-' . $owner->getIdDeclaration()->getStatus()),
            'validation_type'  => ''
        ];

        return $responseData;
    }


    /**
     * @param \DateTime $birthDay
     * @return bool
     */
    private function isAtLeastEighteenYearsOld(\DateTime $birthDay)
    {
        $now = new \DateTime('NOW');
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
     * @param int         $country
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
            $city            = $locationManager->checkFrenchCity($birthPlace);
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
}
