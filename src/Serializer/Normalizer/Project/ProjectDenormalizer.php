<?php

declare(strict_types=1);

namespace Unilend\Serializer\Normalizer\Project;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\{AbstractNormalizer, ContextAwareDenormalizerInterface, DenormalizerAwareInterface, DenormalizerAwareTrait};
use Unilend\Entity\{Clients, Project, ProjectParticipation};
use Unilend\Security\Voter\ProjectParticipationVoter;
use Unilend\Security\Voter\ProjectVoter;

class ProjectDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = 'PROJECT_DENORMALIZER_ALREADY_CALLED';

    /** @var Security */
    private Security $security;
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;
    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;
    /** @var ValidatorInterface */
    private ValidatorInterface $validator;

    /**
     * @param Security               $security
     * @param IriConverterInterface  $iriConverter
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface     $validator
     */
    public function __construct(Security $security, IriConverterInterface $iriConverter, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->security = $security;
        $this->iriConverter = $iriConverter;
        $this->entityManager = $entityManager;
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

        /** @var Project $project */
        $project = $this->denormalizer->denormalize($data, $type, $format, $context);
        $user = $this->security->getUser();

        $dataProjectParticipations = $data['projectParticipations'] ?? [];

        $projectParticipationsIris = $this->getCollectionIris($project->getProjectParticipations());

        foreach ($dataProjectParticipations as $dataProjectParticipation) {
            if (isset($dataProjectParticipation['@id'])) {
                if (in_array($dataProjectParticipation['@id'], $projectParticipationsIris)) {
                    $this->updateProjectParticipation($dataProjectParticipation, $user, $project);
                }
            } elseif (isset($dataProjectParticipation['participant']) && $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
                $this->createProjectParticipation($dataProjectParticipation, $user, $project);
            }
        }

        if (false === $project->isSubParticipation() && $project->getRiskType() !== null) {
            $project->setRiskType(null);
        }

        return $project;
    }

    /**
     * @param Collection $collection
     *
     * @return array
     */
    public function getCollectionIris(Collection $collection): array
    {
        return array_map(function ($item) {
            return $this->iriConverter->getIriFromItem($item);
        }, $collection->toArray());
    }

    /**
     * @param array   $dataProjectParticipation
     * @param Clients $user
     * @param Project $project
     *
     * @throws ExceptionInterface
     */
    private function updateProjectParticipation(array $dataProjectParticipation, Clients $user, Project $project): void
    {
        $participation = $this->iriConverter->getItemFromIri($dataProjectParticipation['@id'], [AbstractNormalizer::GROUPS => []]);

        /** @var ProjectParticipation $participation */
        $participation = $this->denormalizer->denormalize($dataProjectParticipation, ProjectParticipation::class, 'array', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $participation,
            // @todo set group according to project status ?
            AbstractNormalizer::GROUPS => ["projectParticipation:create", "offerWithFee:write", "nullableMoney:write", "offer:write"],
        ]);

        // how to better check permissions for PP ?
        if ($this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_ARRANGER_EDIT, $participation)) {
            $dataprojectParticipationTranches = $dataProjectParticipation['projectParticipationTranches'] ?? [];
            $tranchesIris = $this->getCollectionIris($project->getTranches());

            foreach ($dataprojectParticipationTranches as $dataprojectParticipationTranche) {
                if (isset($dataprojectParticipationTranche['tranche']) && in_array($dataprojectParticipationTranche['tranche'], $tranchesIris)) {
                    $participation->addProjectParticipationTranche(
                        $this->iriConverter->getItemFromIri($dataprojectParticipationTranche['tranche'], [AbstractNormalizer::GROUPS => []]),
                        $user->getCurrentStaff()
                    );
                }
            }

            $this->validator->validate($participation);
            $this->entityManager->persist($participation);
        }
    }

    /**
     * @param array   $dataProjectParticipation
     * @param Clients $user
     * @param Project $project
     *
     * @throws ExceptionInterface
     */
    private function createProjectParticipation(array $dataProjectParticipation, Clients $user, Project $project): void
    {
        $dataProjectParticipation['project'] = $this->iriConverter->getIriFromItem($project);
        $dataProjectParticipation['addedBy'] = $this->iriConverter->getIriFromItem($user->getCurrentStaff());
        /** @var ProjectParticipation $participation */
        $participation = $this->denormalizer->denormalize($dataProjectParticipation, ProjectParticipation::class, 'array', [
            AbstractNormalizer::GROUPS => ["projectParticipation:create", "blameable:read"],
        ]);
        $this->validator->validate($participation);
        $this->entityManager->persist($participation);
        $project->addProjectParticipation($participation);
    }
}
