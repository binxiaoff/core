<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Service\File;

use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\User;
use KLS\Core\Service\File\FileDownloadPermissionCheckerInterface;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Agency\Repository\TermRepository;
use KLS\Syndication\Agency\Security\Voter\TermVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FileDownloadTermPermissionChecker implements FileDownloadPermissionCheckerInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private TermRepository $termRepository;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TermRepository $termRepository)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->termRepository       = $termRepository;
    }

    public function check(FileDownload $fileDownload, User $user): bool
    {
        if (false === $this->supports($fileDownload)) {
            return false;
        }

        $term = $this->termRepository->findOneBy(['borrowerDocument' => $fileDownload->getFileVersion()->getFile()]);

        return $term && $this->authorizationChecker->isGranted(TermVoter::ATTRIBUTE_VIEW, $term);
    }

    private function supports(FileDownload $fileDownload): bool
    {
        return Term::FILE_TYPE_BORROWER_DOCUMENT === $fileDownload->getType();
    }
}
