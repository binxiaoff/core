<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipationMember;

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
use Unilend\Entity\Staff;
use Unilend\Security\Voter\ProjectParticipationMemberVoter;

class ProjectParticipationMemberDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_MEMBER_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

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
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipationMember::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        /** @var ProjectParticipationMember $projectParticipationMember */
        $projectParticipationMember = $this->extractObjectToPopulate(ProjectParticipationMember::class, $context);

        if ($projectParticipationMember) {
            $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipationMember));
        }

        $context[self::ALREADY_CALLED] = true;

        /** @var Clients $user */
        $user = $this->security->getUser();

        /** @var ProjectParticipation $participation */
        $participation = $projectParticipationMember
            ? $projectParticipationMember->getProjectParticipation()
            : $this->iriConverter->getItemFromIri($data['projectParticipation'], [AbstractNormalizer::GROUPS => []]);

        // permit to create staff if POST method and for an external bank
        if (null === $projectParticipationMember && false === $participation->getParticipant()->isCAGMember()) {
            $context[AbstractNormalizer::GROUPS] = array_merge($context[AbstractNormalizer::GROUPS] ?? [], ["role:write", "staff:create", "client:create"]);
        }

        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipationMember::class]['addedBy'] = $user->getCurrentStaff();
        $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][Staff::class]['company'] = $participation->getParticipant();

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @param ProjectParticipationMember $projectParticipationMember
     *
     * @return array
     */
    private function getAdditionalDenormalizerGroups(ProjectParticipationMember $projectParticipationMember): array
    {
        $groups = [];

        if ($this->security->isGranted(ProjectParticipationMemberVoter::ATTRIBUTE_ACCEPT_NDA, $projectParticipationMember)) {
            $groups[] = ProjectParticipationMember::SERIALIZER_GROUP_PROJECT_PARTICIPATION_MEMBER_OWNER_WRITE;
        }

        return $groups;
    }
}
