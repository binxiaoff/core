<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Companies, CompanyAddress, PaysV2
};

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
        if (null === $type) {
            throw new \InvalidArgumentException('The address ' . $type . ' does not exist');
        }

        $country = $this->entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($idCountry);
        if (null === $country) {
            throw new \InvalidArgumentException('The country id ' . $idCountry . ' does not exist');
        }

        $this->entityManager->beginTransaction();

        try {

            if ($company->getIdClientOwner()->isLender()) {
                $this->saveLenderCompanyAddress($company, $address, $zip, $city, $country, $addressType);
            } else {
                $companyAddress = $this->saveNonLenderCompanyAddress($company, $address, $zip, $city, $country, $addressType);

                if (null !== $companyAddress) {
                    $this->validateCompanyAddress($companyAddress);
                    $this->useCompanyAddress($companyAddress);
                    $this->archivePreviousCompanyAddress($company, $type);
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
     * @param PaysV2      $country
     * @param AddressType $type
     *
     * @return CompanyAddress|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveNonLenderCompanyAddress(Companies $company, string $address, string $zip, string $city, PaysV2 $country, AddressType $type): ?CompanyAddress
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
     * @param PaysV2      $country
     * @param AddressType $type
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function saveLenderCompanyAddress(Companies $company, string $address, string $zip, string $city, PaysV2 $country, AddressType $type)
    {
        $companyAddress      = AddressType::TYPE_MAIN_ADDRESS === $type->getLabel() ? $company->getIdAddress() : $company->getIdPostalAddress();
        $lastModifiedAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findLastModifiedNotArchivedAddressByType($company, $type);

        if (
            null === $companyAddress && null === $lastModifiedAddress
            || (null === $companyAddress && null !== $lastModifiedAddress && $this->isAddressDataDifferent($lastModifiedAddress, $address, $zip, $city, $country))
            || (null !== $companyAddress && $this->isAddressDataDifferent($companyAddress, $address, $zip, $city, $country) && $this->isAddressDataDifferent($lastModifiedAddress, $address, $zip, $city, $country))
        ) {
            $newAddress = $this->createCompanyAddress($company, $address, $zip, $city, $country, $type);
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
     * @param CompanyAddress $companyAddress
     * @param string         $address
     * @param string         $zip
     * @param string         $city
     * @param PaysV2         $country
     *
     * @return bool
     */
    private function isAddressDataDifferent(CompanyAddress $companyAddress, string $address, string $zip, string $city, PaysV2 $country)
    {
        return
            $address !== $companyAddress->getAddress()
            || $zip !== $companyAddress->getZip()
            || $city !== $companyAddress->getCity()
            || $country !== $companyAddress->getIdCountry();
    }

    /**
     * @param Companies   $company
     * @param string      $address
     * @param string      $zip
     * @param string      $city
     * @param PaysV2      $country
     * @param AddressType $type
     *
     * @return CompanyAddress
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createCompanyAddress(Companies $company, string $address, string $zip, string $city, PaysV2 $country, AddressType $type): CompanyAddress
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
     * @param CompanyAddress $address
     *
     * @throws \Exception
     */
    public function validateCompanyAddress(CompanyAddress $address): void
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
}
