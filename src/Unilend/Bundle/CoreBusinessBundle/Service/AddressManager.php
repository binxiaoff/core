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

        if ($company->getIdClientOwner()->isLender()) {
            $this->saveLenderCompanyAddress($company, $address, $zip, $city, $country, $addressType);

            return;
        }

        $this->saveAndValidateCompanyAddress($company, $address, $zip, $city, $country, $addressType);
    }

    /**
     * @param Companies   $company
     * @param string      $address
     * @param string      $zip
     * @param string      $city
     * @param PaysV2      $country
     * @param AddressType $type
     * @param bool
     *
     * @throws \Exception
     */
    private function saveAndValidateCompanyAddress(Companies $company, string $address, string $zip, string $city, PaysV2 $country, AddressType $type)
    {
        $this->entityManager->beginTransaction();

        try {
            $lastModifiedAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findLastModifiedCompanyAddressByType($company, $type);
            $companyAddress      = AddressType::TYPE_MAIN_ADDRESS === $type->getLabel() ? $company->getIdAddress() : $company->getIdPostalAddress();

            if (
                null === $companyAddress && null === $lastModifiedAddress
                || (null === $companyAddress && null !== $lastModifiedAddress && $this->addressDataIsDifferent($lastModifiedAddress, $address, $zip, $city, $country))
            ) {
                $newAddress = $this->createCompanyAddress($company, $address, $zip, $city, $country, $type);


            } elseif (null !== $companyAddress && $this->addressDataIsDifferent($companyAddress, $address, $zip, $city, $country)) {
                $companyAddress->setDateArchived(new \DateTime('NOW'));
                $this->entityManager->flush($companyAddress);

                $newAddress = $this->createCompanyAddress($company, $address, $zip, $city, $country, $type);
            }

            $this->validateCompanyAddress($newAddress);
            $this->archivePendingCompanyAddress($company);

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
     */
    private function saveLenderCompanyAddress(Companies $company, string $address, string $zip, string $city, PaysV2 $country, AddressType $type)
    {
        // TODO TECH-393
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
    private function addressDataIsDifferent(CompanyAddress $companyAddress, string $address, string $zip, string $city, PaysV2 $country)
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
            $this->use($address);

            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    /**
     * @param Companies $company
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function archivePendingCompanyAddress(Companies $company): void
    {
        $pendingAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findBy(['idCompany' => $company, 'dateValidated' => null, 'dateArchived' => null]);
        foreach ($pendingAddress as $addressToArchive) {
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
    private function use(CompanyAddress $address): void
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
}
