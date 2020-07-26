<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\ProjectParticipation;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use RuntimeException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\{ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait, ObjectToPopulateTrait};
use Unilend\Entity\{Clients, ProjectParticipation, ProjectParticipationCollection};

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
        $client            = $this->security->getUser();
        $connectedStaff    = $client instanceof Clients ? $client->getCurrentStaff() : null;
        $connectedStaffIRI = $this->iriConverter->getIriFromItem($connectedStaff);

        $participations = new ArrayCollection();
        $project        = null;

        if (false === empty($data['projectParticipations'])) {
            foreach ($data['projectParticipations'] as $projectParticipationData) {
                $projectParticipationData['addedBy'] = $connectedStaffIRI;
                /** @var ProjectParticipation $projectParticipation */
                $projectParticipation = $this->denormalizer->denormalize($projectParticipationData, ProjectParticipation::class, 'array', $context);
                if (null === $project) {
                    $project = $projectParticipation->getProject();
                }

                if ($project && $project !== $projectParticipation->getProject()) {
                    throw new RuntimeException('You cannot add at a time the participations for different projects.');
                }

                if (false === empty($projectParticipationData['projectParticipationMembers'])) {
                    foreach ($projectParticipationData['projectParticipationMembers'] as $projectParticipationMemberData) {
                        if ($projectParticipationMemberData['staff']) {
                            $participantStaff = $this->iriConverter->getItemFromIri($projectParticipationMemberData['staff']);
                            $projectParticipation->addProjectParticipationMember($participantStaff, $connectedStaff);
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
