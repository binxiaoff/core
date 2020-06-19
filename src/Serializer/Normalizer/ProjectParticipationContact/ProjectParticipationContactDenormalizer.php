<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipationContact;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait, ObjectToPopulateTrait};
use Unilend\Entity\ProjectParticipationContact;
use Unilend\Security\Voter\ProjectParticipationContactVoter;

class ProjectParticipationContactDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_CONTACT_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

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
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipationContact::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $projectParticipationContact = $this->extractObjectToPopulate(ProjectParticipationContact::class, $context);

        if ($projectParticipationContact) {
            $context['groups'] = array_merge($context['groups'] ?? [], $this->getAdditionalDenormalizerGroups($projectParticipationContact));
        }

        $context[self::ALREADY_CALLED] = true;
    }

    /**
     * @param ProjectParticipationContact $participationContact
     *
     * @return array
     */
    private function getAdditionalDenormalizerGroups(ProjectParticipationContact $participationContact): array
    {
        $groups = [];

        if ($this->security->isGranted(ProjectParticipationContactVoter::ATTRIBUTE_ARCHIVE, $participationContact)) {
            $groups[] = 'archivable:write';
        }

        if ($this->security->isGranted(ProjectParticipationContactVoter::ATTRIBUTE_ACCEPT_NDA, $participationContact)) {
            $groups[] = ProjectParticipationContact::SERIALIZER_GROUP_PROJECT_PARTICIPATION_CONTACT_OWNER_WRITE;
        }

        return $groups;
    }
}
