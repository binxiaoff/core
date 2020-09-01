<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipation;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationMember;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Security\Voter\ProjectParticipationMemberVoter;
use Unilend\Security\Voter\ProjectParticipationVoter;

class ProjectParticipationDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;

    /**
     * @param Security              $security
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(Security $security, IriConverterInterface $iriConverter)
    {
        $this->security = $security;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipation::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var ProjectParticipation $projectParticipation */
        $projectParticipation = $this->extractObjectToPopulate(ProjectParticipation::class, $context);
        if ($projectParticipation) {
            $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipation));
            if (isset($data['currentStatus']) && \is_array($data['currentStatus'])) {
                unset($data['currentStatus']['projectParticipation']);
                $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationStatus::class]['projectParticipation'] = $projectParticipation;
            }
        }

        /** @var Clients $user */
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
                AbstractNormalizer::OBJECT_TO_POPULATE =>
                    isset($projectParticipationTranche['@id']) ? $this->iriConverter->getItemFromIri($projectParticipationTranche['@id']) : null,
                // @todo set group according to project status ?
                // These group should be analog to ProjectParticipationTranche::post operation and ProjectParticipationTranche:patch operation
                 AbstractNormalizer::GROUPS => isset($projectParticipationTranche['@id']) ? ['offer:write', 'nullableMoney:write'] : ['projectParticipationTranche:create'],
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

            /** @var ProjectParticipationMember $denormalized */
            $denormalized = $this->denormalizer->denormalize($projectParticipationMember, ProjectParticipationMember::class, 'array', [
                AbstractNormalizer::OBJECT_TO_POPULATE =>
                    isset($projectParticipationMember['@id']) ? $this->iriConverter->getItemFromIri($projectParticipationMember['@id']) : null,
                AbstractNormalizer::GROUPS =>
                    // These group should be analog to ProjectParticipationMember::post operation and ProjectParticipationMember:patch operation
                    isset($projectParticipationMember['@id']) ? ['projectParticipationMember:create', 'projectParticipationMember:write'] : ['projectParticipationMember:create'],
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

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return array
     */
    private function getAdditionalDenormalizerGroups(ProjectParticipation $projectParticipation): array
    {
        $groups = [];

        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_ARRANGER_INTEREST_COLLECTION_EDIT, $projectParticipation)) {
            $groups[] = ProjectParticipation::SERIALIZER_GROUP_ARRANGER_INTEREST_COLLECTION_WRITE;
        }

        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_ARRANGER_OFFER_NEGOTIATION_EDIT, $projectParticipation)) {
            $groups[] = ProjectParticipation::SERIALIZER_GROUP_ARRANGER_OFFER_NEGOTIATION_WRITE;
        }

        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_PARTICIPATION_OWNER_EDIT, $projectParticipation)) {
            $groups[] = ProjectParticipation::SERIALIZER_GROUP_PARTICIPATION_OWNER_WRITE;
        }

        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_PARTICIPATION_OWNER_INTEREST_COLLECTION_EDIT, $projectParticipation)) {
            $groups[] = ProjectParticipation::SERIALIZER_GROUP_PARTICIPATION_OWNER_INTEREST_COLLECTION_WRITE;
        }

        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_PARTICIPATION_OWNER_OFFER_NEGOTIATION_EDIT, $projectParticipation)) {
            $groups[] = ProjectParticipation::SERIALIZER_GROUP_PARTICIPATION_OWNER_OFFER_NEGOTIATION_WRITE;
        }

        return $groups;
    }
}
