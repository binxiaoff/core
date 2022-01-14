<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\CreditGuaranty\FEI\Entity\Reporting;
use KLS\CreditGuaranty\FEI\Repository\ReportingRepository;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingFileBuilder;
use League\Flysystem\FilesystemException;

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
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     * @throws EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\IOException
     * @throws FilesystemException
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
