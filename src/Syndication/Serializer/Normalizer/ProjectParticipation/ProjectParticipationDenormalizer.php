<?php

declare(strict_types=1);

namespace KLS\Syndication\Serializer\Normalizer\ProjectParticipation;

use ApiPlatform\Core\Api\IriConverterInterface;
use KLS\Core\Entity\User;
use KLS\Syndication\Entity\ProjectParticipation;
use KLS\Syndication\Entity\ProjectParticipationMember;
use KLS\Syndication\Entity\ProjectParticipationStatus;
use KLS\Syndication\Entity\ProjectParticipationTranche;
use KLS\Syndication\Entity\ProjectStatus;
use KLS\Syndication\Security\Voter\ProjectParticipationMemberVoter;
use KLS\Syndication\Security\Voter\ProjectParticipationVoter;
use KLS\Syndication\Security\Voter\ProjectVoter;
use KLS\Syndication\Service\Project\ProjectManager;
use KLS\Syndication\Service\ProjectParticipation\ProjectParticipationManager;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;

class ProjectParticipationDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    private Security $security;

    private IriConverterInterface $iriConverter;

    private ProjectParticipationManager $projectParticipationManager;

    private ProjectManager $projectManager;

    public function __construct(Security $security, IriConverterInterface $iriConverter, ProjectManager $projectManager, ProjectParticipationManager $projectParticipationManager)
    {
        $this->security       = $security;
        $this->iriConverter   = $iriConverter;
        $this->projectManager = $projectManager;
    }

    public function supportsDenormalization($data, $type, string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipation::class === $type;
    }

    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var ProjectParticipation $projectParticipation */
        $projectParticipation = $this->extractObjectToPopulate(ProjectParticipation::class, $context);
        if ($projectParticipation) {
            $context[AbstractNormalizer::GROUPS] = \array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipation));
            if (isset($data['currentStatus']) && \is_array($data['currentStatus'])) {
                unset($data['currentStatus']['projectParticipation']);
                $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationStatus::class]['projectParticipation'] = $projectParticipation;
            }
        }

        /** @var User $user */
        $user = $this->security->getUser();

        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipation::class]['addedBy'] = $user->getCurrentStaff();

        $projectParticipation = $this->denormalizer->denormalize($data, $type, $format, $context);

        $projectParticipationTranches = $data['projectParticipationTranches'] ?? [];

        foreach ($projectParticipationTranches as $projectParticipationTranche) {
            // Disallow requestData to set projectParticipation
            unset($projectParticipationTranche['projectParticipation']);

            // Ignore creation after projectStatus allocation
            // TODO See if there is a better way to do this
            // TODO Move this condition to ProjectParticipationTranche
            if (false === isset($projectParticipationTranche['@id']) && $projectParticipation->getProject()->getCurrentStatus()->getStatus() >= ProjectStatus::STATUS_ALLOCATION) {
                continue;
            }

            /** @var ProjectParticipationTranche $denormalized */
            $denormalized = $this->denormalizer->denormalize($projectParticipationTranche, ProjectParticipationTranche::class, 'array', [
                AbstractNormalizer::OBJECT_TO_POPULATE => isset($projectParticipationTranche['@id']) ? $this->iriConverter->getItemFromIri($projectParticipationTranche['@id']) : null,
                // @todo set group according to project status ?
                // These group should be analog to ProjectParticipationTranche::post operation and ProjectParticipationTranche:patch operation
                AbstractNormalizer::GROUPS => isset($projectParticipationTranche['@id']) ?
                    ['offer:write', 'nullableMoney:write'] : // PATCH
                    ['projectParticipationTranche:create'], // POST
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    ProjectParticipationTranche::class => [
                        'projectParticipation' => $projectParticipation,
                    ],
                ],
            ]);
            // It is odd to add an updated participationTranche
            // but the method check if the object is already in the ProjectParticipation::projectParticipationTranches arrayCollection
            // TODO See if indexed association would be more proper
            $projectParticipation->addProjectParticipationTranche($denormalized);
        }

        $projectParticipationMembers = $data['projectParticipationMembers'] ?? [];

        foreach ($projectParticipationMembers as $projectParticipationMember) {
            // Disallow requestData to set projectParticipation
            unset($projectParticipationMember['projectParticipation']);

            // Disallow requestData to set staff company when its an array
            if (isset($projectParticipationMember['staff']) && \is_array($projectParticipationMember['staff'])) {
                unset($projectParticipationMember['staff']['company']);
            }

            /** @var ProjectParticipationMember $denormalized */
            $denormalized = $this->denormalizer->denormalize($projectParticipationMember, ProjectParticipationMember::class, 'array', [
                AbstractNormalizer::OBJECT_TO_POPULATE => isset($projectParticipationMember['@id']) ? $this->iriConverter->getItemFromIri($projectParticipationMember['@id']) : null,
                AbstractNormalizer::GROUPS             => // These group should be analog to ProjectParticipationMember::post operation and ProjectParticipationMember:patch operation and Staff::post operation
                    isset($projectParticipationMember['@id'])
                        ? ['archivable:write', 'permission:write'] // PATCH
                        : ['projectParticipationMember:create', 'projectParticipationMember:write', 'permission:write'], // POST
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    ProjectParticipationMember::class => [
                        'projectParticipation' => $projectParticipation,
                    ],
                ],
            ]);

            // Forbid creation in case voter returns false
            // TODO See if tis possible to factor this with ApiPlatform metadata
            // TODO Duplicate with ProjectParticipationMember post operation security attribute making this lines
            if (false === isset($data['@id']) && false === $this->security->isGranted(ProjectParticipationMemberVoter::ATTRIBUTE_CREATE, $denormalized)) {
                throw new AccessDeniedException();
            }

            // It is odd to add an updated participationMember
            // but the method check if the object is already in the ProjectParticipation::projectParticipationMembers arrayCollection
            // TODO See if indexed association would be more proper
            $projectParticipation->addProjectParticipationMember($denormalized);
        }

        return $projectParticipation;
    }

    private function getAdditionalDenormalizerGroups(ProjectParticipation $projectParticipation): array
    {
        $groups = [];

        $currentUser = $this->security->getUser();

        $currentStaff = $currentUser instanceof User ? $currentUser->getCurrentStaff() : null;

        if ($currentStaff) {
            $project = $projectParticipation->getProject();

            $currentStatus = $project->getCurrentStatus()->getStatus();

            if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $projectParticipation)) {
                switch ($currentStatus) {
                    case ProjectStatus::STATUS_INTEREST_EXPRESSION:
                        $groups[] = 'projectParticipation:owner:interestExpression:write';

                        break;

                    case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                        $groups[] = 'projectParticipation:owner:participantReply:write';

                        break;
                }
            }

            if ($this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
                switch ($currentStatus) {
                    case ProjectStatus::STATUS_DRAFT:
                        $groups[] = 'projectParticipation:arranger:draft:write';
                        $groups[] = $project->isInterestExpressionEnabled() ?
                            'projectParticipation:arranger:interestExpression:write' : 'projectParticipation:arranger:participantReply:write';

                        break;

                    case ProjectStatus::STATUS_INTEREST_EXPRESSION:
                        $groups[] = 'projectParticipation:arranger:interestExpression:write';

                        break;

                    case ProjectStatus::STATUS_PARTICIPANT_REPLY:
                        $groups[] = 'projectParticipation:arranger:participantReply:write';

                        break;

                    case ProjectStatus::STATUS_ALLOCATION:
                        if ($projectParticipation->isArrangerParticipation()) {
                            $groups[] = 'projectParticipation:arrangerOwner:allocation:write';
                        }

                        break;
                }
            }
        }

        return $groups;
    }
}
