<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\CreditGuaranty\FEI\Entity\Reporting;
use KLS\CreditGuaranty\FEI\Repository\ReportingRepository;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingFileBuilder;

class ReportingDataPersister implements DataPersisterInterface
{
    private ReportingFileBuilder $reportingFileBuilder;
    private ReportingRepository $reportingRepository;

    public function __construct(ReportingFileBuilder $reportingFileBuilder, ReportingRepository $reportingRepository)
    {
        $this->reportingFileBuilder = $reportingFileBuilder;
        $this->reportingRepository  = $reportingRepository;
    }

    public function supports($data): bool
    {
        return $data instanceof Reporting;
    }

    /**
     * @param Reporting $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist($data): Reporting
    {
        $file = $this->reportingFileBuilder->build(
            $data->getReportingTemplate(),
            $data->getFilters(),
            $data->getAddedBy()
        );
        $data->setFile($file);

        $this->reportingRepository->save($data);

        return $data;
    }

    public function remove($data)
    {
    }
}
