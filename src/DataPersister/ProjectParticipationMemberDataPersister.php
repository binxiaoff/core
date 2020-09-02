<?php

declare(strict_types=1);

namespace Unilend\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Unilend\Entity\ProjectParticipationMember;
use Unilend\Repository\ProjectParticipationMemberRepository;

class ProjectParticipationMemberDataPersister implements DataPersisterInterface
{
    /** @var ProjectParticipationMemberRepository */
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    /**
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     */
    public function __construct(ProjectParticipationMemberRepository $projectParticipationMemberRepository)
    {
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function supports($data): bool
    {
        return $data instanceof ProjectParticipationMember;
    }

    /**
     * @inheritDoc
     */
    public function persist($data, array $context = [])
    {
        if (isset($context['collection_operation_name']) && $context['collection_operation_name'] === 'post') {
            $this->projectParticipationMemberRepository->persist($data->getStaff());
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function remove($data, array $context = [])
    {
        return $data;
    }
}
