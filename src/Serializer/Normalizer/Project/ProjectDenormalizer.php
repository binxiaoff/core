<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Project;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Entity\{Clients, Project, ProjectParticipation, ProjectStatus};

class ProjectDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROJECT_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;
    /** @var ValidatorInterface */
    private ValidatorInterface $validator;

    /**
     * @param IriConverterInterface $iriConverter
     * @param ValidatorInterface    $validator
     * @param Security              $security
     */
    public function __construct(IriConverterInterface $iriConverter, ValidatorInterface $validator, Security $security)
    {
        $this->iriConverter = $iriConverter;
        $this->validator = $validator;
        $this->security = $security;
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
        $isCreateRequest = (
            isset($context['collection_operation_name'], $context['resource_class']) &&
            $context['collection_operation_name'] === 'post' &&
            $context['resource_class'] === Project::class
        );

        // Hydrates the privileged contact from the authenticated https://lafabriquebyca.atlassian.net/browse/CALS-2354
        if ($isCreateRequest) {
            $user = $this->security->getUser();
            if ($user instanceof Clients) {
                $data['privilegedContactPerson'] = [
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                    'occupation' => $user->getJobFunction(),
                ];
            }
        }

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
     * @param array $projectParticipation
     *
     * @return ProjectParticipation
     *
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
                AbstractNormalizer::GROUPS => ['projectParticipation:create', 'offerWithFee:write', 'nullableMoney:write', 'offer:write'],
            ]
        );

        // TODO See if we can use a @Assert\Valid instead (this would permit to have more explicit validation errors)
        $this->validator->validate($participation);

        return $participation;
    }

    /**
     * @param array   $data
     * @param Project $project
     *
     * @return ProjectParticipation
     *
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
                AbstractNormalizer::GROUPS => ['projectParticipation:create', 'offerWithFee:write', 'nullableMoney:write', 'offer:write'],
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
