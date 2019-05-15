<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\{Attachment, Clients};
use Unilend\Repository\{AttachmentSignatureRepository, ProjectAttachmentRepository};
use Unilend\Traits\ConstantsAwareTrait;

class AttachmentVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_DOWNLOAD = 'download';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;
    /** @var AttachmentSignatureRepository */
    private $attachmentSignatureRepository;
    /** @var ProjectAttachmentRepository */
    private $projectAttachmentRepository;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AttachmentSignatureRepository $attachmentSignatureRepository
     * @param ProjectAttachmentRepository   $projectAttachmentRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AttachmentSignatureRepository $attachmentSignatureRepository,
        ProjectAttachmentRepository $projectAttachmentRepository
    ) {
        $this->authorizationChecker          = $authorizationChecker;
        $this->attachmentSignatureRepository = $attachmentSignatureRepository;
        $this->projectAttachmentRepository   = $projectAttachmentRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        $attributes = self::getConstants('ATTRIBUTE_');

        if (false === in_array($attribute, $attributes)) {
            return false;
        }

        if (false === $subject instanceof Attachment) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $attachment, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_DOWNLOAD:
                return $this->canDownload($attachment, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param Attachment $attachment
     * @param Clients    $user
     *
     * @return bool
     */
    private function canDownload(Attachment $attachment, Clients $user): bool
    {
        if ($attachment->getClientOwner() === $user || $attachment->getCompanyOwner() === $user->getCompany()) {
            return true;
        }

        $signature = $this->attachmentSignatureRepository->findOneBy([
            'attachment' => $attachment,
            'signatory'  => $user,
        ]);

        if ($signature) {
            return true;
        }

        $projectAttachments = $this->projectAttachmentRepository->findBy(['attachment' => $attachment]);

        if ($projectAttachments) {
            foreach ($projectAttachments as $projectAttachment) {
                if ($this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $projectAttachment->getProject())) {
                    return true;
                }
            }
        }

        return false;
    }
}
