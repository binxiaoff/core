<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Security\Voter\ProjectVoter;

class SendInvitations
{
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;
    /** @var Security */
    private Security $security;
    /** @var EntityManagerInterface */
    private EntityManagerInterface $manager;
    /** @var SerializerInterface */
    private SerializerInterface $serializer;
    /** @var ValidatorInterface */
    private ValidatorInterface $validator;

    /**
     * @param IriConverterInterface  $iriConverter
     * @param Security               $security
     * @param EntityManagerInterface $manager
     * @param SerializerInterface    $serializer
     * @param ValidatorInterface     $validator
     */
    public function __construct(
        IriConverterInterface $iriConverter,
        Security $security,
        EntityManagerInterface $manager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->iriConverter = $iriConverter;
        $this->security     = $security;
        $this->manager      = $manager;
        $this->validator    = $validator;
        $this->serializer   = $serializer;
    }

    /**
     * @param Project $data
     * @param Request $request
     *
     * @return Project
     *
     * @throws \Exception
     */
    public function __invoke(Project $data, Request $request)
    {
        $staff   = $this->security->getUser()->getCurrentStaff();
        $content = json_decode($request->getContent(), true);

        $data->setCurrentStatus(new ProjectStatus($data, ProjectStatus::STATUS_PARTICIPANT_REPLY, $staff));

        $projectParticipations = $content['projectParticipations'] ?? [];

        foreach ($data->getProjectParticipations() as $participation) {
            $key = array_search($this->iriConverter->getIriFromItem($participation), array_column($projectParticipations, '@id'));

            if (false !== $key) {
                $requestParticipation = $projectParticipations[$key];
                /** @var ProjectParticipation $participation */
                $participation = $this->serializer->denormalize($requestParticipation, ProjectParticipation::class, 'array', [
                    AbstractNormalizer::OBJECT_TO_POPULATE => $participation,
                    AbstractNormalizer::GROUPS => ["projectParticipation:create", "offerWithFee:write", "nullableMoney:write", "offer:write"],
                ]);

                $requestTranches = $requestParticipation['tranches'] ?? [];

                if (false === is_array($requestTranches) || empty($requestTranches)) {
                    throw new BadRequestException('Un tableau de tranche doit être indiqué pour chacun des participants.');
                }

                foreach ($data->getTranches() as $tranche) {
                    $trancheIri = $this->iriConverter->getIriFromItem($tranche);
                    if (false !== $key = array_search($trancheIri, $requestTranches)) {
                        $participation->addProjectParticipationTranche($tranche, $staff);
                    }
                }
            }
        }

        return $data;
    }
}
