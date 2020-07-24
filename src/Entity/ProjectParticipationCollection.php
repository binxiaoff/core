<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiProperty, ApiResource};
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

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
 *         "nullableMoney:read",
 *         "money:read",
 *         "rangedOfferWithFee:read",
 *         "offerWithFee:read",
 *         "offer:read",
 *         "archivable:read",
 *         "timestampable:read"
 *     }},
 *     collectionOperations={
 *         "post": {
 *             "controller": "Unilend\Controller\ProjectParticipation\ProjectParticipationCollectionCreate",
 *             "path": "/project_participation_collection"
 *         }
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
     * @Groups({"projectParticipationCollection:read"})
     */
    private ArrayCollection $projectParticipations;

    /**
     * @param ArrayCollection $projectParticipations
     */
    public function __construct(ArrayCollection $projectParticipations)
    {
        $this->projectParticipations = $projectParticipations;
    }

    /**
     * @return ArrayCollection|ProjectParticipation[]
     */
    public function getProjectParticipations(): ArrayCollection
    {
        return $this->projectParticipations;
    }

    /**
     * API Platform need an identifier to show the result of POST. We add here a fake id.
     *
     * @ApiProperty(identifier=true)
     *
     * @return string
     */
    public function getId(): string
    {
        return 'not_an_id';
    }
}
