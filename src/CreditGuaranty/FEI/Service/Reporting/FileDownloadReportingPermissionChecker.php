<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service\Reporting;

use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\User;
use KLS\Core\Service\File\FileDownloadPermissionCheckerInterface;
use KLS\CreditGuaranty\FEI\Entity\Reporting;
use KLS\CreditGuaranty\FEI\Repository\ReportingRepository;
use KLS\CreditGuaranty\FEI\Security\Voter\ReportingTemplateVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FileDownloadReportingPermissionChecker implements FileDownloadPermissionCheckerInterface
{
    private ReportingRepository $reportingRepository;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        ReportingRepository $reportingRepository,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->reportingRepository  = $reportingRepository;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function check(FileDownload $fileDownload, User $user): bool
    {
        if (false === $this->supports($fileDownload)) {
            return false;
        }

        $file = $fileDownload->getFileVersion()->getFile();

        $reporting = $this->reportingRepository->findOneBy(['file' => $file]);

        if (false === $reporting instanceof Reporting) {
            return false;
        }

        return $this->authorizationChecker->isGranted(
            ReportingTemplateVoter::ATTRIBUTE_VIEW,
            $reporting->getReportingTemplate()
        );
    }

    private function supports(FileDownload $fileDownload): bool
    {
        return Reporting::FILE_TYPE_CREDIT_GUARANTY_REPORTING === $fileDownload->getType();
    }
}
