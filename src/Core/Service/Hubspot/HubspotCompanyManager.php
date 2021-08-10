<?php

declare(strict_types=1);

namespace KLS\Core\Service\Hubspot;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\HubspotCompany;
use KLS\Core\Repository\CompanyRepository;
use KLS\Core\Repository\HubspotCompanyRepository;
use KLS\Core\Service\Hubspot\Client\HubspotClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HubspotCompanyManager
{
    private LoggerInterface $logger;
    private HubspotCompanyRepository $hubspotCompanyRepository;
    private CompanyRepository $companyRepository;
    private HubspotClient $hubspotClient;

    public function __construct(
        LoggerInterface $logger,
        HubspotCompanyRepository $hubspotCompanyRepository,
        CompanyRepository $companyRepository,
        HubspotClient $hubspotClient
    ) {
        $this->logger                   = $logger;
        $this->hubspotCompanyRepository = $hubspotCompanyRepository;
        $this->companyRepository        = $companyRepository;
        $this->hubspotClient            = $hubspotClient;
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
    public function synchronizeCompanies(int $lastCompanyId = 0): array
    {
        $content = $this->fetchCompanies($lastCompanyId);

        if (!\array_key_exists('results', $content)) {
            $this->logger->info('No company found on hubspot');

            return [];
        }

        $companiesAddedNb = 0;

        foreach ($content['results'] as $arrCompany) {
            $userHubspotCompany = $this->hubspotCompanyRepository->findOneBy(['companyId' => (int) $arrCompany['id']]);

            if (true === $userHubspotCompany instanceof HubspotCompany) { // if the company found has already a company id
                continue;
            }

            $company = $this->companyRepository->findOneBy(['companyName' => $arrCompany['properties']['name'], 'emailDomain' => $arrCompany['properties']['domain']]);

            if (false === $company instanceof Company) { //If the company does not exist in our database
                continue;
            }

            $hubspotCompany = new HubspotCompany($company, $arrCompany['id']);

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
}
