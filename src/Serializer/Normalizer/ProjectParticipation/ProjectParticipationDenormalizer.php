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

        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationTranche::class]['addedBy'] = $user->getCurrentStaff();

        $projectParticipation = $this->denormalizer->denormalize($data, $type, $format, $context);

        $projectParticipationTranches = $data['projectParticipationTranches'] ?? [];

        foreach ($projectParticipationTranches as $projectParticipationTranche) {
            $projectParticipationTranche['projectParticipation'] = $this->iriConverter->getItemFromIri($projectParticipationTranche);

            /** @var ProjectParticipationTranche $denormalized */
            $denormalized = $this->denormalizer->denormalize($projectParticipationTranches, ProjectParticipationTranche::class, 'array', [
                AbstractNormalizer::OBJECT_TO_POPULATE =>
                    isset($projectParticipationTranche['@id']) ? $this->iriConverter->getIriFromItem($projectParticipationTranches['@id']) : null,
                // @todo set group according to project status ?
                 AbstractNormalizer::GROUPS => isset($projectParticipationTranche['@id']) ? ['offer:write', 'nullableMoney:write'] : ['projectParticipationTranche:create'],
            ]);
            $projectParticipation->addProjectParticipationTranche($denormalized);
        }


        $projectParticipationMembers = $data['projectParticipationMembers'] ?? [];

        foreach ($projectParticipationMembers as $projectParticipationMember) {
            $projectParticipationMember['projectParticipation'] = $this->iriConverter->getItemFromIri($projectParticipationMember);

            /** @var ProjectParticipationMember $denormalized */
            $denormalized = $this->denormalizer->denormalize($projectParticipationMembers, ProjectParticipationMember::class, 'array', [
                AbstractNormalizer::OBJECT_TO_POPULATE =>
                    isset($projectParticipationMember['@id']) ? $this->iriConverter->getIriFromItem($projectParticipationMembers['@id']) : null,
                AbstractNormalizer::GROUPS =>
                    isset($projectParticipationMember['@id']) ? ['projectParticipationMember:create'] : ['projectParticipationMember:create', 'projectParticipationMember:write'],
            ]);
            $projectParticipation->addProjectParticipationMember($denormalized);
        }

        $this->validator->validate($projectParticipation);

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
