<?php

declare(strict_types=1);

namespace KLS\Core\DataTransformer\Team;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use KLS\Core\DTO\Team\CreateTeam;
use KLS\Core\Entity\Team;

class CreateTeamDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param CreateTeam $object
     */
    public function transform($object, string $to, array $context = [])
    {
        $this->validator->validate($object);

        return Team::createTeam($object->name, $object->parent);
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return false === $data instanceof Team && Team::class === $to;
    }
}
