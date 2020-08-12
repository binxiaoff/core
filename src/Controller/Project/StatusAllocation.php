<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Exception\Staff\StaffNotFoundException;

class StatusAllocation
{
    /**
     * @var IriConverterInterface
     */
    private IriConverterInterface $iriConverter;
    /**
     * @var Security
     */
    private Security $security;
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $manager;
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;
    /**
     * @var ValidatorInterface
     */
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
        $this->security = $security;
        $this->manager = $manager;
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    /**
     * @param Project $data
     * @param Request $request
     *
     * @return Project
     *
     * @throws StaffNotFoundException
     */
    public function __invoke(Project $data, Request $request)
    {
        $staff = $this->security->getUser()->getCurrentStaff();
        $client = $this->security->getUser();

        // @todo mettre ca dans voter
        if (false === $client instanceof Clients || null === $staff = $client->getCurrentStaff()) {
            throw new StaffNotFoundException('not found');
        }

        if ($data->getCurrentStatus()->getStatus() !== ProjectStatus::STATUS_DRAFT) {
            throw new BadRequestException('Status incorrect');
        }

        $content = json_decode($request->getContent(), true);

        $projectParticipations = $content['projectParticipations'] ?? [];

        foreach ($data->getProjectParticipations() as $participation) {
            $key = array_search($this->iriConverter->getIriFromItem($participation), array_column($projectParticipations, '@id'));

            if ($requestParticipation = $projectParticipations[$key]) {
                /** @var ProjectParticipation $participation */
                $participation = $this->serializer->denormalize($requestParticipation, ProjectParticipation::class, 'array', [
                    AbstractNormalizer::OBJECT_TO_POPULATE => $participation,
                    AbstractNormalizer::GROUPS => ["projectParticipation:create", "offerWithFee:write", "nullableMoney:write", "offer:write"],
                ]);

                // @todo ensure invitationRequest is filled
                $this->validator->validate($participation);

                $requestTranches = $requestParticipation['tranches'] ?? [];

                if (false === is_array($requestTranches) || empty($requestTranches)) {
                    throw new BadRequestException('Il manque les tranches cibles pour une participation');
                }

                foreach ($data->getTranches() as $tranche) {
                    $trancheIri = $this->iriConverter->getIriFromItem($tranche);
                    $key = array_search($trancheIri, $requestTranches);
                    if (false !== $key) {
                        /** @var ProjectParticipationTranche $projectParticipationTranche */
                        $projectParticipationTranche = $this->serializer->denormalize([
                            'projectParticipation' => $requestParticipation['@id'],
                            'tranche' => $requestTranches[$key],
                            'addedBy' => $this->iriConverter->getIriFromItem($staff),
                        ], ProjectParticipationTranche::class, 'array', [ AbstractNormalizer::GROUPS => ['projectParticipationTranche:create', 'blameable:read']]);
                        $this->validator->validate($projectParticipationTranche);
                        $this->manager->persist($projectParticipationTranche);
                        $participation->addProjectParticipationTranche($projectParticipationTranche);
                    }
                }

                if ($participation->getProjectParticipationTranches()->isEmpty()) {
                    throw new BadRequestException('Il faut ajouter des tranches valides');
                }
            }
        }
        // @todo ensure participantReplyDeadline is filled

        $data->setCurrentStatus(new ProjectStatus($data, ProjectStatus::STATUS_ALLOCATION, $staff));

//        $this->manager->persist($data);

        return $data;
    }
}
