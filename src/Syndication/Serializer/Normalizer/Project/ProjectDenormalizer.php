<?php

declare(strict_types=1);

namespace Unilend\Syndication\Serializer\Normalizer\Project;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectStatus;

class ProjectDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_DENORMALIZER_ALREADY_CALLED';

    private IriConverterInterface $iriConverter;

    private ValidatorInterface $validator;

    public function __construct(IriConverterInterface $iriConverter, ValidatorInterface $validator)
    {
        $this->iriConverter = $iriConverter;
        $this->validator    = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && Project::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $project = $this->extractObjectToPopulate(Project::class, $context);

        if ($project && isset($data['currentStatus']) && \is_array($data['currentStatus'])) {
            unset($data['currentStatus']['project']);
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectStatus::class]['project'] = $project;
        }

        /** @var Project $denormalized */
        $denormalized = $this->denormalizer->denormalize($data, $type, $format, $context);

        $projectParticipations = $data['projectParticipations'] ?? [];

        foreach ($projectParticipations as $projectParticipation) {
            if (isset($projectParticipation['@id'])) {
                $projectParticipation = $this->updateProjectParticipation($projectParticipation);
            // Avoid projectParticipation creation after allocation
            } elseif ($denormalized->getCurrentStatus()->getStatus() < ProjectStatus::STATUS_ALLOCATION) {
                $projectParticipation = $this->createProjectParticipation($projectParticipation, $denormalized);
            }
            // It is odd to add an updated participation but the method check if the object is already in the Project::projectParticipations arrayCollection
            // TODO See if indexed association would be more proper
            if ($projectParticipation) {
                $denormalized->addProjectParticipation($projectParticipation);
            }
        }

        if (false === $denormalized->isSubParticipation() && null !== $denormalized->getRiskType()) {
            $denormalized->setRiskType(null);
        }

        return $denormalized;
    }

    /**
     * @throws ExceptionInterface
     */
    private function updateProjectParticipation(array $projectParticipation): ProjectParticipation
    {
        $participation = $this->iriConverter->getItemFromIri($projectParticipation['@id'], [AbstractNormalizer::GROUPS => []]);

        /** @var ProjectParticipation $participation */
        $participation = $this->denormalizer->denormalize(
            $projectParticipation,
            ProjectParticipation::class,
            'array',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $participation,
                // @todo set group according to project status ?
                AbstractNormalizer::GROUPS => ['projectParticipation:create', 'offerWithFee:write', 'nullableMoney:write', 'offer:write', 'rangedOfferWithFee:write'],
            ]
        );

        // TODO See if we can use a @Assert\Valid instead (this would permit to have more explicit validation errors)
        $this->validator->validate($participation);

        return $participation;
    }

    /**
     * @throws ExceptionInterface
     */
    private function createProjectParticipation(array $data, Project $project): ProjectParticipation
    {
        unset($data['project']);
        /** @var ProjectParticipation $participation */
        $participation = $this->denormalizer->denormalize(
            $data,
            ProjectParticipation::class,
            'array',
            [
                AbstractNormalizer::GROUPS                        => ['projectParticipation:create', 'offerWithFee:write', 'nullableMoney:write', 'offer:write', 'rangedOfferWithFee:write'],
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    ProjectParticipation::class => [
                        'project' => $project,
                    ],
                ],
            ]
        );
        // TODO See if we can use a @Assert\Valid instead (this would permit to have more explicit validation errors)
        $this->validator->validate($participation);

        return $participation;
    }
}
