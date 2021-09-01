<?php

declare(strict_types=1);

namespace KLS\Core\Service\Hubspot;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Entity\HubspotCompany;
use KLS\Core\Repository\CompanyRepository;
use KLS\Core\Repository\HubspotCompanyRepository;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\Hubspot\Client\HubspotClient;
use KLS\Syndication\Agency\Repository\ProjectRepository as ProjectAgencyRepository;
use KLS\Syndication\Arrangement\Repository\ProjectRepository as ProjectArrangementRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HubspotCompanyManager
{
    private CompanyRepository $companyRepository;
    private HubspotCompanyRepository $hubspotCompanyRepository;
    private HubspotClient $hubspotClient;
    private UserRepository $userRepository;
    private ProjectAgencyRepository $projectAgencyRepository;
    private ProjectArrangementRepository $projectArrangementRepository;
    private LoggerInterface $logger;

    public function __construct(
        CompanyRepository $companyRepository,
        HubspotCompanyRepository $hubspotCompanyRepository,
        HubspotClient $hubspotClient,
        UserRepository $userRepository,
        ProjectAgencyRepository $projectAgencyRepository,
        ProjectArrangementRepository $projectArrangementRepository,
        LoggerInterface $logger
    ) {
        $this->companyRepository            = $companyRepository;
        $this->hubspotCompanyRepository     = $hubspotCompanyRepository;
        $this->hubspotClient                = $hubspotClient;
        $this->userRepository               = $userRepository;
        $this->projectAgencyRepository      = $projectAgencyRepository;
        $this->projectArrangementRepository = $projectArrangementRepository;
        $this->logger                       = $logger;
    }

    /**
     * @param mixed $lastCompanyId
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \JsonException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function importCompaniesFromHubspot(int $lastCompanyId = 0): array
    {
        $content = $this->fetchCompanies($lastCompanyId);

        if (!\array_key_exists('results', $content) || ((isset($content['results'])) && 0 === \count($content['results']))) {
            $this->logger->info('There is an error on the request URI or no company found on Hubspot');

            return [];
        }

        $companiesAddedNb = 0;

        foreach ($content['results'] as $companyProperties) {
            $userHubspotCompany = $this->hubspotCompanyRepository->findOneBy(['hubspotCompanyId' => (int) $companyProperties['id']]);

            if (true === $userHubspotCompany instanceof HubspotCompany) { // if the company found has already a company id
                continue;
            }

            $company = $this->companyRepository->findOneBy(['shortCode' => $companyProperties['properties']['kls_short_code']]);

            if (false === $company instanceof Company) { //If the company does not exist in our database
                continue;
            }

            $hubspotCompany = new HubspotCompany($company, $companyProperties['id']);

            $this->hubspotCompanyRepository->persist($hubspotCompany);
            ++$companiesAddedNb;
        }

        $this->hubspotCompanyRepository->flush();

        if (\array_key_exists('paging', $content)) {
            $lastCompanyId = $content['paging']['next']['after'];
        }

        return [
            'lastCompanyId'  => $lastCompanyId,
            'companyAddedNb' => $companiesAddedNb,
        ];
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \JsonException
     */
    public function exportCompaniesToHubspot(int $limit): array
    {
        $dataReturn       = [];
        $companiesCreated = 0;
        $companiesUpdated = 0;

        //Get all companies when a corresponding hubspot id does not exist
        $companies = $this->companyRepository->findCompaniesToCreateOnHubspot($limit);

        if ($companies) {
            foreach ($companies as $company) {
                if (!$this->createCompanyOnHubspot($company)) {
                    continue;
                }
                ++$companiesCreated;
            }
        }

        //Get all companies when a corresponding hubspot id exist
        $companies = $this->companyRepository->findCompaniesToUpdateOnHubspot($limit);

        if ($companies) {
            foreach ($companies as $company) {
                $hubspotCompany = $this->hubspotCompanyRepository->findOneBy(['company' => $company]);

                if (false === $hubspotCompany instanceof HubspotCompany) {
                    continue;
                }

                if ($this->updateCompanyOnHubspot($hubspotCompany, $this->formatData($company))) {
                    ++$companiesUpdated;
                }
            }
        }

        $this->hubspotCompanyRepository->flush();

        $dataReturn['companiesCreated'] = $companiesCreated;
        $dataReturn['companiesUpdated'] = $companiesUpdated;

        return $dataReturn;
    }

    /**
     * @throws \JsonException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function fetchCompanies(?int $lastCompanyId = null): array
    {
        $response = $this->hubspotClient->fetchAllCompanies($lastCompanyId);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $this->logger->error(\sprintf('There is an error while fetching %s', $response->getInfo()['url']));

            return [];
        }

        return \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createCompanyOnHubspot(Company $company): bool
    {
        // Create company on hubspot
        $response = $this->hubspotClient->postNewCompany($this->formatData($company));

        if (Response::HTTP_CREATED !== $response->getStatusCode()) {
            $this->logger->info($response->getContent(false));

            return false;
        }
        $content = \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $hubspotCompany = new HubspotCompany($company, (string) $content['id']);
        $this->hubspotCompanyRepository->persist($hubspotCompany);

        return true;
    }

    private function updateCompanyOnHubspot(HubspotCompany $hubspotCompany, array $data): bool
    {
        $response = $this->hubspotClient->updateCompany($hubspotCompany->getHubspotCompanyId(), $data);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $this->logger->info($response->getContent(false));

            return false;
        }

        $hubspotCompany->synchronize();

        return true;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function formatData(Company $company): array
    {
        $status = null;

        $activeUsersPercentage = $this->userRepository->findActiveUsersPerCompany($company);

        if ($company->getCurrentStatus()) {
            switch ($company->getCurrentStatus()->getStatus()) {
                case CompanyStatus::STATUS_PROSPECT:
                    $status = 'Non signé';

                    break;

                case CompanyStatus::STATUS_SIGNED:
                    $status = 'Signé';

                    break;

                case CompanyStatus::STATUS_REFUSED:
                    $status = 'Refusé';

                    break;
            }
        }

        return [
            'properties' => [
                'name'                     => $company->getDisplayName(),
                'domain'                   => $company->getEmailDomain(),
                'kls_short_code'           => $company->getShortCode(),
                'kls_bank_group'           => $company->getCompanyGroup() ? $company->getCompanyGroup()->getName() : null,
                'kls_company_status'       => $status,
                'kls_user_init_percentage' => $activeUsersPercentage['kls_user_init_percentage'] ?? null,
                'kls_active_modules'       => \implode(', ', $company->getActivatedModules()),
                'kls_agency_projects'      => $this->projectAgencyRepository->countProjectsByCompany($company),
                'kls_arrangement_projects' => $this->projectArrangementRepository->countProjectsByCompany($company),
            ],
        ];
    }
}
