<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AddressType, Attachment, ClientAddress, ClientAddressAttachment, Clients, Companies, CompanyAddress, Pays};

class AddressManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var LocationManager */
    private $locationManager;

    /**
     * @param EntityManager   $entityManager
     * @param LocationManager $locationManager
     */
    public function __construct(EntityManager $entityManager, LocationManager $locationManager)
    {
        $this->entityManager   = $entityManager;
        $this->locationManager = $locationManager;
    }

    /**
     * @param string    $address
     * @param string    $zip
     * @param string    $city
     * @param int       $idCountry
     * @param Companies $company
     * @param string    $type
     *
     * @throws \Exception
     */
    public function saveCompanyAddress(string $address, string $zip, string $city, int $idCountry, Companies $company, string $type): void
    {
        $addressType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AddressType')->findOneBy(['label' => $type]);
        if (null === $addressType) {
            throw new \InvalidArgumentException('The address ' . $type . ' does not exist');
        }

        $country = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Pays')->find($idCountry);
        if (null === $country) {
            throw new \InvalidArgumentException('The country id ' . $idCountry . ' does not exist');
        }

        $this->entityManager->beginTransaction();

        try {

            if (null !== $company->getIdClientOwner() && $company->getIdClientOwner()->isLender()) {
                $this->saveLenderCompanyAddress($company, $address, $zip, $city, $country, $addressType);
            } else {
                $companyAddress = $this->saveNonLenderCompanyAddress($company, $address, $zip, $city, $country, $addressType);

                if (null !== $companyAddress) {
                    $this->validateCompanyAddress($companyAddress);
                    $this->useCompanyAddress($companyAddress);
                    $this->archivePreviousCompanyAddress($company, $addressType);
                }
            }

            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    /**
     * @param Companies   $company
     * @param string      $address
     * @param string      $zip
     * @param string      $city
     * @param Pays        $country
     * @param AddressType $type
     *
     * @return CompanyAddress|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveNonLenderCompanyAddress(Companies $company, string $address, string $zip, string $city, Pays $country, AddressType $type): ?CompanyAddress
    {
        $lastModifiedAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findLastModifiedNotArchivedAddressByType($company, $type);
        $companyAddress      = AddressType::TYPE_MAIN_ADDRESS === $type->getLabel() ? $company->getIdAddress() : $company->getIdPostalAddress();

        if (
            null === $companyAddress && null === $lastModifiedAddress
            || (null === $companyAddress && null !== $lastModifiedAddress && $this->isAddressDataDifferent($lastModifiedAddress, $address, $zip, $city, $country))
            || (null !== $companyAddress && $this->isAddressDataDifferent($companyAddress, $address, $zip, $city, $country))
        ){
            return $this->createCompanyAddress($company, $address, $zip, $city, $country, $type);
        }

        return null;
    }

    /**
     * @param Companies   $company
     * @param string      $address
     * @param string      $zip
     * @param string      $city
     * @param Pays        $country
     * @param AddressType $type
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function saveLenderCompanyAddress(Companies $company, string $address, string $zip, string $city, Pays $country, AddressType $type)
    {
        $companyAddress      = AddressType::TYPE_MAIN_ADDRESS === $type->getLabel() ? $company->getIdAddress() : $company->getIdPostalAddress();
        $lastModifiedAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findLastModifiedNotArchivedAddressByType($company, $type);

        if (
            null === $companyAddress && null === $lastModifiedAddress
            || (null === $companyAddress && null !== $lastModifiedAddress && $this->isAddressDataDifferent($lastModifiedAddress, $address, $zip, $city, $country))
            || (null !== $companyAddress && $this->isAddressDataDifferent($companyAddress, $address, $zip, $city, $country) && $this->isAddressDataDifferent($lastModifiedAddress, $address, $zip, $city, $country))
        ) {
            $newAddress = $this->createCompanyAddress($company, $address, $zip, $city, $country, $type);

            if (AddressType::TYPE_MAIN_ADDRESS === $type->getLabel()) {
                $this->addCogToLenderAddress($newAddress);
            }
        }

        if (isset($newAddress) && AddressType::TYPE_POSTAL_ADDRESS === $type->getLabel()) {
            $this->entityManager->beginTransaction();

            try {
                $newAddress->setDateValidated(new \DateTime('NOW'));
                $this->entityManager->flush($newAddress);

                $this->validateCompanyAddress($newAddress);
                $this->useCompanyAddress($newAddress);
                $this->archivePreviousCompanyAddress($company, $type);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();
                throw $exception;
            }

        }
    }

    /**
     * @param CompanyAddress|ClientAddress $addressObject
     * @param string                       $address
     * @param string                       $zip
     * @param string                       $city
     * @param Pays                         $country
     *
     * @return bool
     */
    private function isAddressDataDifferent($addressObject, string $address, string $zip, string $city, Pays $country): bool
    {
        return
            $address !== $addressObject->getAddress()
            || $zip !== $addressObject->getZip()
            || $city !== $addressObject->getCity()
            || $country !== $addressObject->getIdCountry();
    }

    /**
     * @param Companies   $company
     * @param string      $address
     * @param string      $zip
     * @param string      $city
     * @param Pays        $country
     * @param AddressType $type
     *
     * @return CompanyAddress
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createCompanyAddress(Companies $company, string $address, string $zip, string $city, Pays $country, AddressType $type): CompanyAddress
    {
        $companyAddress = new CompanyAddress();
        $companyAddress
            ->setIdCompany($company)
            ->setAddress($address)
            ->setZip($zip)
            ->setCity($city)
            ->setIdCountry($country)
            ->setIdType($type);

        $this->entityManager->persist($companyAddress);
        $this->entityManager->flush($companyAddress);

        return $companyAddress;
    }

    /**
     * @param CompanyAddress|ClientAddress $address
     *
     * @throws \Exception
     */
    public function validateLenderAddress($address)
    {
        $this->entityManager->beginTransaction();

        try {
            if ($address instanceof ClientAddress) {
                $this->validateClientAddress($address);
                $this->useClientAddress($address);
                $this->archivePreviousClientAddress($address->getIdClient(), $address->getIdType());
            }

            if ($address instanceof CompanyAddress) {
                $this->validateCompanyAddress($address);
                $this->useCompanyAddress($address);
                $this->archivePreviousCompanyAddress($address->getIdCompany(), $address->getIdType());
            }

            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    /**
     * @param CompanyAddress $address
     *
     * @throws \Exception
     */
    private function validateCompanyAddress(CompanyAddress $address): void
    {
        $this->entityManager->beginTransaction();
        try {
            $this->addLatitudeAndLongitude($address);

            $address->setDateValidated(new \DateTime('NOW'));

            $this->entityManager->flush($address);

            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    /**
     * @param Companies   $company
     * @param AddressType $type
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function archivePreviousCompanyAddress(Companies $company, AddressType $type): void
    {
        $previousAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findBy(['idCompany' => $company, 'idType' => $type, 'dateArchived' => null]);
        foreach ($previousAddress as $addressToArchive) {
            if ($addressToArchive === $company->getIdAddress() || $addressToArchive === $company->getIdPostalAddress()) {
                continue;
            }

            $addressToArchive->setDateArchived(new \DateTime());
            $this->entityManager->flush($addressToArchive);
        }
    }

    /**
     * @param CompanyAddress $address
     */
    public function addLatitudeAndLongitude(CompanyAddress $address)
    {
        $coordinates = $this->locationManager->getCompanyCoordinates($address);

        if ($coordinates) {
            $address->setLatitude($coordinates['latitude']);
            $address->setLongitude($coordinates['longitude']);
        }
    }

    /**
     * @param CompanyAddress $address
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function useCompanyAddress(CompanyAddress $address): void
    {
        $company = $address->getIdCompany();

        if (AddressType::TYPE_MAIN_ADDRESS === $address->getIdType()->getLabel()) {
            $company->setIdAddress($address);
        }

        if (AddressType::TYPE_POSTAL_ADDRESS === $address->getIdType()->getLabel()) {
            $company->setIdPostalAddress($address);
        }

        $this->entityManager->flush($company);
    }

    /**
     * @param Companies $company
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteCompanyAddresses(Companies $company)
    {
        foreach ($this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findBy(['idCompany' => $company]) as $address) {
            $this->entityManager->remove($address);
            $this->entityManager->flush($address);
        }
    }

    /**
     * @param Companies $company
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function companyPostalAddressSameAsMainAddress(Companies $company): void
    {
        $postalAddress = $company->getIdPostalAddress();

        if (null === $postalAddress) {
            $postalAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')
                ->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_POSTAL_ADDRESS);
        }

        if (null !== $postalAddress && null === $postalAddress->getDateArchived()) {
            $this->entityManager->beginTransaction();

            try {
                $type = $company->getIdPostalAddress()->getIdType();

                $company->setIdPostalAddress(null);
                $this->entityManager->flush($company);

                $this->archivePreviousCompanyAddress($company, $type);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();
                throw $exception;
            }
        }
    }

    /**
     * @param string  $address
     * @param string  $zip
     * @param string  $city
     * @param int     $idCountry
     * @param Clients $client
     * @param string  $type
     *
     * @throws \Exception
     */
    public function saveClientAddress(string $address, string $zip, string $city, int $idCountry, Clients $client, string $type): void
    {
        $addressType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AddressType')->findOneBy(['label' => $type]);
        if (null === $type) {
            throw new \InvalidArgumentException('The address ' . $type . ' does not exist');
        }

        $country = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Pays')->find($idCountry);
        if (null === $country) {
            throw new \InvalidArgumentException('The country id ' . $idCountry . ' does not exist');
        }

        $this->entityManager->beginTransaction();

        try {
            if ($client->isLender()) {
                $this->saveLenderClientAddress($client, $address, $zip, $city, $country, $addressType);
            } else {
               //TODO when doing advisor
            }

            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    /**
     * @param Clients     $client
     * @param string      $address
     * @param string      $zip
     * @param string      $city
     * @param Pays        $country
     * @param AddressType $type
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function saveLenderClientAddress(Clients $client, string $address, string $zip, string $city, Pays $country, AddressType $type)
    {
        $clientAddress       = AddressType::TYPE_MAIN_ADDRESS === $type->getLabel() ? $client->getIdAddress() : $client->getIdPostalAddress();
        $lastModifiedAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->findLastModifiedNotArchivedAddressByType($client, $type);

        if (
            null === $clientAddress && null === $lastModifiedAddress
            || (null === $clientAddress && null !== $lastModifiedAddress && $this->isAddressDataDifferent($lastModifiedAddress, $address, $zip, $city, $country))
            || (null !== $clientAddress && $this->isAddressDataDifferent($clientAddress, $address, $zip, $city, $country) && null !== $lastModifiedAddress && $this->isAddressDataDifferent($lastModifiedAddress, $address, $zip, $city, $country))
        ) {
            $newAddress = $this->createClientAddress($client, $address, $zip, $city, $country, $type);

            if (AddressType::TYPE_MAIN_ADDRESS === $type->getLabel()) {
                $this->addCogToLenderAddress($newAddress);
            }
        }

        if (isset($newAddress) && AddressType::TYPE_POSTAL_ADDRESS === $type->getLabel()) {
            $this->entityManager->beginTransaction();

            try {
                $this->validateClientAddress($newAddress);
                $this->useClientAddress($newAddress);
                $this->archivePreviousClientAddress($client, $type);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();
                throw $exception;
            }
        }
    }

    /**
     * @param Clients     $client
     * @param string      $address
     * @param string      $zip
     * @param string      $city
     * @param Pays        $country
     * @param AddressType $type
     *
     * @return ClientAddress
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createClientAddress(Clients $client, string $address, string $zip, string $city, Pays $country, AddressType $type): ClientAddress
    {
        $clientAddress = new ClientAddress();
        $clientAddress
            ->setIdClient($client)
            ->setAddress($address)
            ->setZip($zip)
            ->setCity($city)
            ->setIdCountry($country)
            ->setIdType($type);

        $this->entityManager->persist($clientAddress);
        $this->entityManager->flush($clientAddress);

        return $clientAddress;
    }

    /**
     * @param ClientAddress $address
     *
     * @throws \Exception
     */
    private function validateClientAddress(ClientAddress $address): void
    {
        $address->setDateValidated(new \DateTime('NOW'));
        $this->entityManager->flush($address);
    }

    /**
     * @param Clients     $client
     * @param AddressType $type
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function archivePreviousClientAddress(Clients $client, AddressType $type): void
    {
        $previousAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->findBy(['idClient' => $client, 'idType' => $type, 'dateArchived' => null]);
        foreach ($previousAddress as $addressToArchive) {
            if ($addressToArchive === $client->getIdAddress() || $addressToArchive === $client->getIdPostalAddress()) {
                continue;
            }

            $addressToArchive->setDateArchived(new \DateTime());
            $this->entityManager->flush($addressToArchive);
        }
    }

    /**
     * @param ClientAddress $address
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function useClientAddress(ClientAddress $address): void
    {
        $client = $address->getIdClient();

        if (AddressType::TYPE_MAIN_ADDRESS === $address->getIdType()->getLabel()) {
            $client->setIdAddress($address);
        }

        if (AddressType::TYPE_POSTAL_ADDRESS === $address->getIdType()->getLabel()) {
            $client->setIdPostalAddress($address);
        }

        $this->entityManager->flush($client);
    }


    /**
     * @param Clients $client
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function clientPostalAddressSameAsMainAddress(Clients $client): void
    {
        $postalAddress = $client->getIdPostalAddress();

        if (null === $postalAddress) {
            $postalAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')
                ->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_POSTAL_ADDRESS);
        }

        if (null !== $postalAddress && null === $postalAddress->getDateArchived()) {
            $this->entityManager->beginTransaction();

            try {
                $type = $client->getIdPostalAddress()->getIdType();

                $client->setIdPostalAddress(null);
                $this->entityManager->flush($client);

                $this->archivePreviousClientAddress($client, $type);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();
                throw $exception;
            }
        }
    }

    /**
     * @param ClientAddress $address
     * @param Attachment    $attachment
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function linkAttachmentToAddress(ClientAddress $address, Attachment $attachment): void
    {
        $clientAddressAttachment = new ClientAddressAttachment();
        $clientAddressAttachment
            ->setIdClientAddress($address)
            ->setIdAttachment($attachment);

        $this->entityManager->persist($clientAddressAttachment);
        $this->entityManager->flush($clientAddressAttachment);
    }

    /**
     * Method to change to private once RUN-3156 is closed and all French addresses have COG value
     * The return can then be void.
     * @param ClientAddress|CompanyAddress $address
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCogToLenderAddress($address): bool
    {
        if ($address instanceof ClientAddress && false === $address->getIdClient()->isLender()) {
            throw new \InvalidArgumentException('Address is no lender address');
        }

        if ($address instanceof CompanyAddress && false === $address->getIdCompany()->getIdClientOwner()->isLender()) {
            throw new \InvalidArgumentException('Address is no lender address');
        }

        if ($address->getIdCountry()->getIdPays() === Pays::COUNTRY_FRANCE || in_array($address->getIdCountry()->getIdPays(), Pays::FRANCE_DOM_TOM)) {
            $inseeCog = $this->locationManager->getInseeCog($address->getZip(), $address->getCity());

            if (null !== $inseeCog) {
                $address->setCog($inseeCog);
                $this->entityManager->flush($address);

                return true;
            }
        }

        return false;
    }
}
