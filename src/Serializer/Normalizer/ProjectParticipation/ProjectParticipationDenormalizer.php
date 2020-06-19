<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipation;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait, ObjectToPopulateTrait};
use Unilend\Entity\ProjectParticipation;
use Unilend\Security\Voter\ProjectParticipationVoter;

class ProjectParticipationDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipation::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        /** @var ProjectParticipation $projectParticipation */
        $projectParticipation = $this->extractObjectToPopulate(ProjectParticipation::class, $context);
        if ($projectParticipation) {
            $context['groups'] = array_merge($context['groups'] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipation));
        }

        $context[self::ALREADY_CALLED] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
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

        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_ARRANGER_CONTRACT_NEGOTIATION_EDIT, $projectParticipation)) {
            $groups[] = ProjectParticipation::SERIALIZER_GROUP_ARRANGER_CONTRACT_NEGOTIATION_WRITE;
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

        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_PARTICIPATION_OWNER_CONTRACT_NEGOTIATION_EDIT, $projectParticipation)) {
            $groups[] = ProjectParticipation::SERIALIZER_GROUP_PARTICIPANT_CONTRACT_NEGOTIATION_OWNER_WRITE;
        }

        return $groups;
    }
}
