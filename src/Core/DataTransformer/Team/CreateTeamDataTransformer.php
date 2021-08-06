<?php

declare(strict_types=1);

namespace Unilend\Core\DataTransformer\Team;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Unilend\Core\DTO\Team\CreateTeam;
use Unilend\Core\Entity\Team;

class CreateTeamDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @var CreateTeam
     *
     * {@inheritDoc}
     */
    public function transform($object, string $to, array $context = [])
    {
        $this->validator->validate($object);

        return Team::createTeam($object->name, $object->parent);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return false === $data instanceof Team && Team::class === $to;
    }
}
