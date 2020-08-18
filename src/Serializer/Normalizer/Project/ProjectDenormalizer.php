<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Project;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer,
    ContextAwareDenormalizerInterface,
    DenormalizerAwareInterface,
    DenormalizerAwareTrait,
    ObjectToPopulateTrait};
use Unilend\Entity\{Clients, Project, ProjectParticipation, ProjectStatus, Staff};
use Unilend\Security\Voter\ProjectParticipationVoter;
use Unilend\Security\Voter\ProjectVoter;

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
        return !isset($context[self::ALREADY_CALLED]) && Project::class === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var Clients $user */
        $user = $this->security->getUser();

        $project = $this->extractObjectToPopulate(Project::class, $context);

        if ($project && isset($data['currentStatus']) && \is_array($data['currentStatus'])) {
            unset($data['currentStatus']['project']);
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectStatus::class]['project'] = $project;
        }

        /** @var Project $denormalized */
        $denormalized = $this->denormalizer->denormalize($data, $type, $format, $context);

        $dataProjectParticipations = $data['projectParticipations'] ?? [];

        // Put here because the class CurrentStaff already do it for
        $defaultContext[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][ProjectParticipation::class]['addedBy'] = $user->getCurrentStaff();

        foreach ($dataProjectParticipations as $dataProjectParticipation) {
            if (isset($dataProjectParticipation['@id'])) {
                $this->updateProjectParticipation($dataProjectParticipation);
            } else {
                $this->createProjectParticipation($dataProjectParticipation, $denormalized);
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
        $participation = $this->denormalizer->denormalize($projectParticipation, ProjectParticipation::class, 'array', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $participation,
            // @todo set group according to project status ?
            AbstractNormalizer::GROUPS => ['projectParticipation:create', 'offerWithFee:write', 'nullableMoney:write', 'offer:write'],
        ]);

        $this->validator->validate($participation);

        return $participation;
    }

    /**
     * @param array   $data
     * @param Project $project
     *
     * @param array   $context
     *
     * @return ProjectParticipation
     *
     * @throws ExceptionInterface
     */
    private function createProjectParticipation(array $data, Project $project, array $context = []): ProjectParticipation
    {
        $data['project'] = $this->iriConverter->getIriFromItem($project);
        /** @var ProjectParticipation $participation */
        $participation = $this->denormalizer->denormalize($data, ProjectParticipation::class, 'array', array_merge($context, [
            AbstractNormalizer::GROUPS => ['projectParticipation:create', 'blameable:read'],
        ]));
        $this->validator->validate($participation);

        return $participation;
    }
}
