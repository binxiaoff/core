<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Security\Voter;

use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\FileDownloadVoterInterface;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Agency\Repository\TermRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FileDownloadTermVoter implements FileDownloadVoterInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private TermRepository $termRepository;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TermRepository $termRepository)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->termRepository       = $termRepository;
    }

    public function supports(FileDownload $fileDownload): bool
    {
        return Term::FILE_TYPE_BORROWER_DOCUMENT === $fileDownload->getType();
    }

    public function canCreate(FileDownload $fileDownload, User $user): bool
    {
        $term = $this->termRepository->findOneBy(['borrowerDocument' => $fileDownload->getFileVersion()->getFile()]);

        return $term && $this->authorizationChecker->isGranted(TermVoter::ATTRIBUTE_VIEW, $term);
    }
}
