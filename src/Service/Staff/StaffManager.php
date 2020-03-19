<?php

declare(strict_types=1);

namespace Unilend\Service\Staff;

use Unilend\Entity\{ClientStatus, Clients, Staff};
use Unilend\Exception\Client\ClientNotFoundException;
use Unilend\Exception\Staff\StaffNotFoundException;
use Unilend\Repository\{ClientsRepository, CompanyRepository, StaffRepository};
use Unilend\Service\Company\CompanyManager;

class StaffManager
{
    /** @var CompanyManager */
    private $companyManager;
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var CompanyRepository */
    private $companyRepository;
    /** @var StaffRepository */
    private $staffRepository;

    /**
     * @param CompanyManager    $companyManager
     * @param ClientsRepository $clientsRepository
     * @param CompanyRepository $companyRepository
     * @param StaffRepository   $staffRepository
     */
    public function __construct(CompanyManager $companyManager, ClientsRepository $clientsRepository, CompanyRepository $companyRepository, StaffRepository $staffRepository)
    {
        $this->companyManager    = $companyManager;
        $this->clientsRepository = $clientsRepository;
        $this->companyRepository = $companyRepository;
        $this->staffRepository   = $staffRepository;
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
}
