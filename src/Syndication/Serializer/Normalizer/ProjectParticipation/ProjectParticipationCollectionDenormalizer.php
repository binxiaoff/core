<?php

declare(strict_types=1);

namespace Unilend\Syndication\Serializer\Normalizer\ProjectParticipation;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer, ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait, ObjectToPopulateTrait};
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Syndication\Entity\{ProjectParticipation,
    ProjectParticipationMember,
    Request\ProjectParticipationCollection
};

class ProjectParticipationCollectionDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_PARTICIPATION_COLLECTION_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

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
        $this->security     = $security;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && ProjectParticipationCollection::class === $type;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function denormalize($data, $type, ?string $format = null, array $context = [])
    {
        $user              = $this->security->getUser();
        $connectedStaff    = $user instanceof User ? $user->getCurrentStaff() : null;
        $connectedStaffIRI = $this->iriConverter->getIriFromItem($connectedStaff);

        $participations = new ArrayCollection();
        $project        = null;

        if (false === empty($data['projectParticipations']) && false === empty($data['project'])) {
            foreach ($data['projectParticipations'] as $projectParticipationData) {
                $projectParticipationData['addedBy'] = $connectedStaffIRI;
                $projectParticipationData['project'] = $data['project'];
                /** @var ProjectParticipation $projectParticipation */
                $projectParticipation = $this->denormalizer->denormalize($projectParticipationData, ProjectParticipation::class, 'array', $context);
                if (null === $project) {
                    $project = $projectParticipation->getProject();
                }

                if (false === empty($projectParticipationData['projectParticipationMembers'])) {
                    foreach ($projectParticipationData['projectParticipationMembers'] as $staffIRI) {
                        /** @var Staff $participantStaff */
                        $participantStaff = $this->iriConverter->getItemFromIri($staffIRI, [AbstractNormalizer::GROUPS => []]);
                        if ($participantStaff) {
                            $projectParticipationMember = new ProjectParticipationMember($projectParticipation, $participantStaff, $connectedStaff);
                            $projectParticipation->addProjectParticipationMember($projectParticipationMember);
                        }
                    }
                }

                $participations->add($projectParticipation);
            }
        }

        $context[self::ALREADY_CALLED] = true;

        return new ProjectParticipationCollection($participations, $project);
    }
}
