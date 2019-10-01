<?php

declare(strict_types=1);

namespace Unilend\Service\Staff;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use DomainException;
use Unilend\Entity\{Clients, ClientsStatus, MarketSegment, Staff};
use Unilend\Repository\{ClientsRepository, CompaniesRepository, StaffRepository};
use Unilend\Service\Company\CompanyManager;

class StaffManager
{
    /** @var CompanyManager */
    private $companyManager;
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var CompaniesRepository */
    private $companiesRepository;
    /** @var StaffRepository */
    private $staffRepository;

    /**
     * @param CompanyManager      $companyManager
     * @param ClientsRepository   $clientsRepository
     * @param CompaniesRepository $companiesRepository
     * @param StaffRepository     $staffRepository
     */
    public function __construct(CompanyManager $companyManager, ClientsRepository $clientsRepository, CompaniesRepository $companiesRepository, StaffRepository $staffRepository)
    {
        $this->companyManager      = $companyManager;
        $this->clientsRepository   = $clientsRepository;
        $this->companiesRepository = $companiesRepository;
        $this->staffRepository     = $staffRepository;
    }

    /**
     * @param string $email
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Staff
     */
    public function getStaffByEmail(string $email): Staff
    {
        $company = $this->companyManager->getCompanyByEmail($email);

        if (null === $company) {
            throw new DomainException(sprintf('The email .%s is not one of our partners.', $email));
        }

        $client = $this->clientsRepository->findOneBy(['email' => $email]);

        if (null === $client) {
            $client = new Clients();
            $client
                ->setCurrentStatus(ClientsStatus::STATUS_INVITED)
                ->setEmail($email)
                ->addRoles([Clients::ROLE_USER])
            ;
            $this->clientsRepository->save($client);
        }

        $staff = $this->staffRepository->findOneBy(['company' => $company, 'client' => $client]);

        if (null === $staff) {
            $staff = $company->addStaff($client, Staff::ROLE_STAFF_OPERATOR);
            $this->companiesRepository->save($company);
        }

        return $staff;
    }

    /**
     * @param MarketSegment $marketSegment
     *
     * @return array
     */
    public function getConcernedRoles(MarketSegment $marketSegment): array
    {
        $marketRoles        = Staff::getMarketSegmentRoles();
        $marketSegmentLabel = $marketSegment->getLabel();

        return array_values(array_filter($marketRoles, static function ($marketRole) use ($marketSegmentLabel) {
            return mb_substr($marketRole, -mb_strlen($marketSegmentLabel)) === mb_strtoupper($marketSegmentLabel);
        }));
    }
}
