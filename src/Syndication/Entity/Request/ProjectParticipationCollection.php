<?php

declare(strict_types=1);

namespace Unilend\Syndication\Entity\Request;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipation;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "projectParticipationCollection:read",
 *         "projectParticipation:read",
 *         ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ,
 *         ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ,
 *         "projectParticipationMember:read",
 *         "projectParticipationTranche:read",
 *         "projectParticipationStatus:read",
 *         "company:read",
 *         "companyStatus:read",
 *         "nullableMoney:read",
 *         "money:read",
 *         "rangedOfferWithFee:read",
 *         "offerWithFee:read",
 *         "offer:read",
 *         "archivable:read",
 *         "timestampable:read"
 *     }},
 *     collectionOperations={
 *         "post": {"security_post_denormalize": "is_granted('create', object)"}
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     }
 * )
 */
class ProjectParticipationCollection
{
    /**
     * @var ArrayCollection|ProjectParticipation[]
     *
     * @Assert\Count(min=1)
     * @Assert\Valid
     *
     * @Groups({"projectParticipationCollection:read"})
     */
    private ArrayCollection $projectParticipations;

    private Project $project;

    public function __construct(ArrayCollection $projectParticipations, Project $project)
    {
        $this->projectParticipations = $projectParticipations;
        $this->project               = $project;
    }

    /**
     * @return ArrayCollection|ProjectParticipation[]
     */
    public function getProjectParticipations(): ArrayCollection
    {
        return $this->projectParticipations;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * API Platform need an identifier to show the result of POST. We add here a fake id.
     *
     * @ApiProperty(identifier=true)
     */
    public function getId(): string
    {
        return 'not_an_id';
    }

    /**
     * @Assert\Callback
     */
    public function checkParticipationUniqueness(ExecutionContextInterface $context): void
    {
        $companyIds = [];
        foreach ($this->projectParticipations as $participation) {
            $companyIds[] = $participation->getParticipant()->getId();
        }

        if (\count($companyIds) !== \count(\array_unique($companyIds))) {
            $context->buildViolation('Syndication.ProjectParticipationCollection.projectParticipations.duplicated')
                ->atPath('projectParticipations')
                ->addViolation()
            ;
        }
    }
}
