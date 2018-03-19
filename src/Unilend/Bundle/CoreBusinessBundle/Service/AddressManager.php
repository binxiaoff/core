<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, AttachmentType, Companies, CompanyAddress, PaysV2
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
            $companyAddress = AddressType::TYPE_MAIN_ADDRESS === $type ? $company->getIdAddress(): $company->getIdPostalAddress();

            if (null === $companyAddress) {
                $companyAddress = new CompanyAddress();
                $companyAddress->setIdCompany($company);
            }

            if (null === $companyAddress->getDateValidated()) {
                $companyAddress
                    ->setAddress($address)
                    ->setZip($zip)
                    ->setCity($city)
                    ->setIdCountry($country)
                    ->setIdType($addressType);

                if (false === $this->entityManager->contains($companyAddress)) {
                    $this->entityManager->persist($companyAddress);
                }

                $this->addLatitudeAndLongitude($companyAddress);
                $this->entityManager->flush($companyAddress);

                $this->use($companyAddress);
            } elseif (
                $address !== $companyAddress->getAddress()
                || $zip !== $companyAddress->getZip()
                || $city !== $companyAddress->getCity()
                || $idCountry !== $companyAddress->getIdCountry()->getIdPays()
            ) {
                $companyAddress->setDateArchived(new \DateTime('NOW'));
                $this->entityManager->flush($companyAddress);

                $this->createCompanyAddress($companyAddress->getIdCompany(), $address, $zip, $city, $country, $addressType);
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
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createCompanyAddress(Companies $company, string $address, string $zip, string $city, PaysV2 $country, AddressType $type): void
    {
        $companyAddress = new CompanyAddress();
        $companyAddress
            ->setIdCompany($company)
            ->setAddress($address)
            ->setZip($zip)
            ->setCity($city)
            ->setIdCountry($country)
            ->setIdType($type);

        $this->addLatitudeAndLongitude($companyAddress);

        $this->entityManager->persist($companyAddress);
        $this->entityManager->flush($companyAddress);

        $this->use($companyAddress);
    }

    /**
     * @param CompanyAddress $address
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function use(CompanyAddress $address): void
    {
        if (AddressType::TYPE_MAIN_ADDRESS === $address->getIdType()->getLabel()) {
            $address->getIdCompany()->setIdAddress($address);
        }

        if (AddressType::TYPE_POSTAL_ADDRESS === $address->getIdType()->getLabel()) {
            $address->getIdCompany()->setIdPostalAddress($address);
        }

        $this->entityManager->flush($address->getIdCompany());
    }

    /**
     * @param CompanyAddress $companyAddress
     * @param int            $projectId
     *
     * @throws \Exception
     */
    public function validateBorrowerCompanyAddress(CompanyAddress $companyAddress, int $projectId): void
    {
        $kbis = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')
            ->getProjectAttachmentByType($projectId, AttachmentType::KBIS);

        if (null === $kbis) {
            throw new \InvalidArgumentException('Project ' . $projectId . ' has no valid KBIS. Address can not be validated');
        }

        $this->entityManager->beginTransaction();
        try {
            $currentAddress = $companyAddress->getIdCompany()->getIdAddress();
            if ($currentAddress !== $companyAddress) {
                if ($currentAddress) {
                    $currentAddress->setDateArchived(new \DateTime('NOW'));
                    $this->entityManager->flush($currentAddress);
                }

                $this->validateCompanyAddress($companyAddress, $kbis);
                $this->use($companyAddress);
            }
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
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
     * @param CompanyAddress $companyAddress
     * @param Attachment     $kbis
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function validateCompanyAddress(CompanyAddress $companyAddress, Attachment $kbis)
    {
        $companyAddress
            ->setDateValidated(new \DateTime('NOW'))
            ->setIdAttachment($kbis);

        $company = $companyAddress->getIdCompany();

        $this->entityManager->flush([$companyAddress, $company]);
    }
}
