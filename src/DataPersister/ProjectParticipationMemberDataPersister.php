<?php

declare(strict_types=1);

namespace Unilend\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Unilend\Entity\ProjectParticipationMember;

class ProjectParticipationMemberDataPersister implements ContextAwareDataPersisterInterface
{
    private $decorated;

    /**
     * @param ContextAwareDataPersisterInterface $decorated
     */
    public function __construct(ContextAwareDataPersisterInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @inheritDoc
     */
    public function supports($data, array $context = []): bool
    {
        return $this->decorated->supports($data, $context);
    }

    /**
     * @inheritDoc
     */
    public function persist($data, array $context = [])
    {
        if ($data instanceof ProjectParticipationMember && isset($context['collection_operation_name']) && $context['collection_operation_name'] === 'post') {
            $this->decorated->persist($data->getStaff());
        }

        return $this->decorated->persist($data, $context);
    }

    /**
     * @inheritDoc
     */
    public function remove($data, array $context = [])
    {
        return $this->decorated->remove($data, $context);
    }
}
