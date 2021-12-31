<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\FileInput;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use KLS\CreditGuaranty\FEI\Service\FinancingObjectUpdater;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileInputFinancingObjectUploader
{
    private FinancingObjectUpdater $financingObjectUpdater;

    public function __construct(FinancingObjectUpdater $financingObjectUpdater)
    {
        $this->financingObjectUpdater = $financingObjectUpdater;
    }

    /**
     * @throws IOException
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReaderNotOpenedException
     */
    public function upload(UploadedFile $uploadedFile): array
    {
        $data = $this->decode($uploadedFile->getPathname());

        return $this->financingObjectUpdater->update($data);
    }

    /**
     * @throws ReaderNotOpenedException
     * @throws IOException
     */
    private function decode(string $filePath): array
    {
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($filePath);

        $sheetIterator = $reader->getSheetIterator();
        $sheetIterator->rewind();

        $sheet = $sheetIterator->current();

        $rowIterator = $sheet->getRowIterator();

        $rowIterator->rewind();

        $header = $rowIterator->current();

        $rowIterator->next();

        $header = \array_map('strval', $header->getCells());

        $result = [];
        while ($rowIterator->valid()) {
            $rowResult = \array_map('strval', $rowIterator->current()->getCells());
            $result[]  = \array_combine($header, $rowResult);

            $rowIterator->next();
        }

        return $result;
    }
}
