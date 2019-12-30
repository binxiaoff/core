<?php

declare(strict_types=1);

namespace Unilend\Service\Staff;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Entity\{ClientStatus, Clients, Staff};
use Unilend\Exception\Client\ClientNotFoundException;
use Unilend\Exception\Staff\StaffNotFoundException;
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
     * @throws ClientNotFoundException
     * @throws StaffNotFoundException
     *
     * @return Staff
     */
    public function getStaffByEmail(string $email): Staff
    {
        $company = $this->companyManager->getCompanyByEmail($email);

        $client = $this->clientsRepository->findOneBy(['email' => $email]);

        if (null === $client) {
            throw new ClientNotFoundException(sprintf('The client with %s is not found.', $email));
        }

        $staff = $this->staffRepository->findOneBy(['company' => $company, 'client' => $client]);

        if (null === $staff) {
            throw new StaffNotFoundException(sprintf('The staff with %s is not found in company %s.', $email, $company->getName()));
        }

        return $staff;
    }

    /**
     * @param string $email
     *
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     *
     * @return Staff
     */
    public function addStaffFromEmail(string $email): Staff
    {
        $company = $this->companyManager->getCompanyByEmail($email);

        $client = $this->clientsRepository->findOneBy(['email' => $email]);

        if (null === $client) {
            $client = new Clients($email);
            $client
                ->setCurrentStatus(ClientStatus::STATUS_INVITED)
                ->setEmail($email)
                ->addRoles([Clients::ROLE_USER])
            ;
            $this->clientsRepository->save($client);
        }

        $staff = $company->addStaff($client, Staff::DUTY_STAFF_OPERATOR);
        $this->companiesRepository->save($company);

        return $staff;
    }
}
