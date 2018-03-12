<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\AddressType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyAddress;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;

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
     * @param string              $address
     * @param string              $zip
     * @param string              $city
     * @param int                 $idCountry
     * @param null|CompanyAddress $companyAddress
     * @param int|null            $idCompany
     * @param null|string         $type
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveCompanyAddress(string $address, string $zip, string $city, int $idCountry, ?CompanyAddress $companyAddress, ?int $idCompany, ?string $type): void
    {
        if (null === $companyAddress) {
            $companyAddress = new CompanyAddress();
        }

        if (null !== $idCompany) {
            $company  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($idCompany);
            if (null === $company) {
                throw new \InvalidArgumentException('The company ' . $idCompany . ' does not exist.');
            }
            $companyAddress->setIdCompany($company);
        }

        if (null !== $type) {
            $typeEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AddressType')->findOneBy(['label' => $type]);
            if (null === $type) {
                throw new \InvalidArgumentException('The address ' . $type . ' does not exist');
            }
        }

        $country = $this->entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($idCountry);
        if (null === $country) {
            throw new \InvalidArgumentException('The country id ' . $idCountry . ' does not exist');
        }

        if (null === $companyAddress->getDateValidated()) {
            $companyAddress
                ->setAddress($address)
                ->setZip($zip)
                ->setCity($city)
                ->setIdCountry($country)
                ->setIdType($typeEntity);

            if (false === $this->entityManager->contains($companyAddress)) {
                $this->entityManager->persist($companyAddress);
            }

            $this->addLatitudeAndLongitudeToCompanyAddress($companyAddress);
            $this->entityManager->flush($companyAddress);
        } elseif (
            $address != $companyAddress->getAddress()
            || $zip != $companyAddress->getZip()
            || $city != $companyAddress->getCity()
            || $idCountry != $companyAddress->getIdCountry()->getIdPays()
        ) {
            $companyAddress->setDateArchived(new \DateTime('NOW'));
            $this->entityManager->flush($companyAddress);

            $this->createCompanyAddress($companyAddress->getIdCompany(), $address, $zip, $city, $country, $typeEntity);
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
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createCompanyAddress(Companies $company, string $address, string $zip, string $city, PaysV2 $country, AddressType $type): bool
    {
        $companyAddress = new CompanyAddress();
        $companyAddress
            ->setIdCompany($company)
            ->setAddress($address)
            ->setZip($zip)
            ->setCity($city)
            ->setIdCountry($country)
            ->setIdType($type);

        $this->addLatitudeAndLongitudeToCompanyAddress($companyAddress);

        $this->entityManager->persist($companyAddress);
        $this->entityManager->flush($companyAddress);

        return true;
    }

    /**
     * @param CompanyAddress $companyAddress
     * @param int            $idProject
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function validateBorrowerCompanyAddress(CompanyAddress $companyAddress, int $idProject): void
    {
        $kbis = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')
            ->getProjectAttachmentByType($idProject, \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::KBIS);

        $companyAddress
            ->setDateValidated(new \DateTime('NOW'))
            ->setIdAttachment($kbis);

        $this->entityManager->flush($companyAddress);
    }

    /**
     * @param CompanyAddress $address
     */
    public function addLatitudeAndLongitudeToCompanyAddress(CompanyAddress $address)
    {
        $coordinates = $this->locationManager->getCompanyCoordinates($address);

        if ($coordinates) {
            $address->setLatitude($coordinates['latitude']);
            $address->setLongitude($coordinates['longitude']);
        }
    }
}
