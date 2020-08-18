<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipation;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationMember;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
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
    /** @var ValidatorInterface */
    private ValidatorInterface $validator;

    /**
     * @param Security              $security
     * @param IriConverterInterface $iriConverter
     * @param ValidatorInterface    $validator
     */
    public function __construct(Security $security, IriConverterInterface $iriConverter, ValidatorInterface $validator)
    {
        $this->security = $security;
        $this->iriConverter = $iriConverter;
        $this->validator = $validator;
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
        }

        /** @var Clients $user */
        $user = $this->security->getUser();

        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipation::class]['addedBy'] = $user->getCurrentStaff();

        $projectParticipation = $this->denormalizer->denormalize($data, $type, $format, $context);

        $projectParticipationTranches = $data['projectParticipationTranches'] ?? [];

        foreach ($projectParticipationTranches as $projectParticipationTranche) {
            if (false === isset($projectParticipationTranche['@id'])) {
                $projectParticipationTranche['projectParticipation'] = $this->iriConverter->getIriFromItem($projectParticipation);
            }

            // Disable creation after projectStatus allocation
            // TODO See if there is a better way to do this
            if (isset($projectParticipationTranche['@id']) && $projectParticipation->getProject()->getCurrentStatus()->getStatus() < ProjectStatus::STATUS_ALLOCATION) {
                continue;
            }

            /** @var ProjectParticipationTranche $denormalized */
            $denormalized = $this->denormalizer->denormalize($projectParticipationTranche, ProjectParticipationTranche::class, 'array', [
                AbstractNormalizer::OBJECT_TO_POPULATE =>
                    isset($projectParticipationTranche['@id']) ? $this->iriConverter->getItemFromIri($projectParticipationTranche['@id']) : null,
                // @todo set group according to project status ?
                // These group should be analog to ProjectParticipationTranche::post operation and ProjectParticipationTranche:patch operation
                 AbstractNormalizer::GROUPS => isset($projectParticipationTranche['@id']) ? ['offer:write', 'nullableMoney:write'] : ['projectParticipationTranche:create'],
            ]);
            // It is odd to add an updated participationTranche
            // but the method check if the object is already in the ProjectParticipation::projectParticipationTranches arrayCollection
            // TODO See if indexed association would be more proper
            $projectParticipation->addProjectParticipationTranche($denormalized);
        }


        $projectParticipationMembers = $data['projectParticipationMembers'] ?? [];

        foreach ($projectParticipationMembers as $projectParticipationMember) {
            if (false === isset($projectParticipationTranche['@id'])) {
                $projectParticipationMember['projectParticipation'] = $this->iriConverter->getIriFromItem($projectParticipation);
            }

            /** @var ProjectParticipationMember $denormalized */
            $denormalized = $this->denormalizer->denormalize($projectParticipationMember, ProjectParticipationMember::class, 'array', [
                AbstractNormalizer::OBJECT_TO_POPULATE =>
                    isset($projectParticipationMember['@id']) ? $this->iriConverter->getItemFromIri($projectParticipationMember['@id']) : null,
                AbstractNormalizer::GROUPS =>
                    // These group should be analog to ProjectParticipationMember::post operation and ProjectParticipationMember:patch operation
                    isset($projectParticipationMember['@id']) ? ['projectParticipationMember:create'] : ['projectParticipationMember:create', 'projectParticipationMember:write'],
            ]);
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
