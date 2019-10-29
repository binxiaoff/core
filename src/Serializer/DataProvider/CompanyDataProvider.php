<?php

declare(strict_types=1);

namespace Unilend\Serializer\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use Unilend\Entity\Companies;
use Unilend\Repository\CompaniesRepository;
use Unilend\Service\Company\CompanySearchManager;

class CompanyDataProvider implements RestrictedDataProviderInterface, ItemDataProviderInterface
{
    /**
     * @var CompaniesRepository
     */
    private $companiesRepository;
    /**
     * @var CompanySearchManager
     */
    private $companySearchManager;
    /**
     * @var ItemDataProviderInterface
     */
    private $dataProvider;

    /**
     * @param CompaniesRepository       $companiesRepository
     * @param CompanySearchManager      $companySearchManager
     * @param ItemDataProviderInterface $dataProvider
     */
    public function __construct(
        CompaniesRepository $companiesRepository,
        CompanySearchManager $companySearchManager,
        ItemDataProviderInterface $dataProvider
    ) {
        $this->companiesRepository  = $companiesRepository;
        $this->companySearchManager = $companySearchManager;
        $this->dataProvider         = $dataProvider;
    }

    /**
     * Retrieves an item.
     *
     * @param string           $resourceClass
     * @param array|int|string $id
     * @param string|null      $operationName
     * @param array            $context
     *
     * @throws ResourceClassNotSupportedException
     *
     * @return object|null
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        $siren = str_pad((string) $id, 9, '0', STR_PAD_LEFT);

        return $this->companiesRepository->findOneBy(['siren' => $siren])
            ?? $this->companySearchManager->searchCompanyBySiren($siren)
            ?? $this->dataProvider->getItem($resourceClass, $id, $operationName, $context);
    }

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     * @param array       $context
     *
     * @return bool
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        $restriction = true;

        if ($this->dataProvider instanceof RestrictedDataProviderInterface) {
            $restriction = $this->dataProvider->supports($resourceClass, $operationName, $context);
        }

        return Companies::class === $resourceClass && $restriction;
    }
}
