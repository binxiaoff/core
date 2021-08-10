<?php

declare(strict_types=1);

namespace KLS\Syndication\Serializer\Normalizer\ProjectParticipationTranche;

use KLS\Core\Entity\User;
use KLS\Syndication\Entity\ProjectParticipationTranche;
use KLS\Syndication\Entity\ProjectStatus;
use KLS\Syndication\Security\Voter\ProjectParticipationTrancheVoter;
use KLS\Syndication\Security\Voter\ProjectVoter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;

class ProjectParticipationTrancheDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_TRANCHE_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supportsDenormalization($data, $type, string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipationTranche::class === $type;
    }

    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        /** @var ProjectParticipationTranche $projectParticipationTranche */
        $projectParticipationTranche = $this->extractObjectToPopulate(ProjectParticipationTranche::class, $context);
        if ($projectParticipationTranche) {
            $context[AbstractNormalizer::GROUPS] = \array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipationTranche));
        }

        $context[self::ALREADY_CALLED] = true;

        /** @var User $user */
        $user = $this->security->getUser();

        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationTranche::class]['addedBy'] = $user->getCurrentStaff();

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    private function getAdditionalDenormalizerGroups(ProjectParticipationTranche $projectParticipationTranche): array
    {
        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        $groups = [];

        $currentUser = $this->security->getUser();

        $currentStaff = $currentUser instanceof User ? $currentUser->getCurrentStaff() : null;

        if ($currentStaff) {
            $project = $projectParticipation->getProject();

            $currentStatus = $project->getCurrentStatus()->getStatus();

            switch ($currentStatus) {
                case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                    if ($this->security->isGranted(ProjectParticipationTrancheVoter::ATTRIBUTE_EDIT, $projectParticipationTranche)) {
                        $groups[] = 'projectParticipationTranche:owner:participantReply:write';
                    }

                    break;

                case ProjectStatus::STATUS_ALLOCATION:
                    if ($this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
                        $groups[] = 'projectParticipationTranche:arranger:allocation:write';

                        if ($projectParticipation->isArrangerParticipation()) {
                            $groups[] = 'projectParticipationTranche:arrangerOwner:allocation:write';
                        }
                    }

                    break;
            }
        }

        return $groups;
    }
}
