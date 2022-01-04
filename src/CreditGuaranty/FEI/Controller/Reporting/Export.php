<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reporting;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use DateTimeImmutable;
use KLS\Core\Entity\User;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Security\Voter\ProgramVoter;
use KLS\CreditGuaranty\FEI\Service\Reporting\ReportingFileBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class Export
{
    private const XLSX_EXTENSION = 'xlsx';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function __invoke(
        Request $request,
        ReportingTemplate $data,
        Security $security,
        ReportingFileBuilder $reportingFileBuilder
    ): string {
        $user = $security->getUser();

        if (
            false === ($user instanceof User) || false === $security->isGranted(
                ProgramVoter::ATTRIBUTE_REPORTING,
                $data->getProgram()
            )
        ) {
            throw new AccessDeniedException();
        }

        $date     = new DateTimeImmutable();
        $filename = $user->getId() . '_' . $data->getProgram()->getName() . '_' . $data->getName() . '_' .
            $date->getTimestamp() . '.' . self::XLSX_EXTENSION;

        return $reportingFileBuilder->createFile($data, $request->query->all());
        // todo: send this file to S3
    }
}
