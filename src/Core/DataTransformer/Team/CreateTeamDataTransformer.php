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

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @var CreateTeam $object
     *
     * @inheritDoc
     */
    public function transform($object, string $to, array $context = [])
    {
        $this->validator->validate($object);

        $team = Team::createTeam($object->name, $object->parent);

        foreach ($object->companyGroupTags as $companyGroupTag) {
            $team->addCompanyGroupTag($companyGroupTag);
        }

        return $team;
    }

    /**
     * @inheritDoc
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return false === $data instanceof Team && Team::class === $to;
    }
}
